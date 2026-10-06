<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Carrito de compra y confirmación del pedido de delivery.
 *
 * El carrito se guarda en la sesión como un array simple: [id_producto => cantidad].
 * No hay tabla de carrito en la base de datos.
 */
class CarritoController extends Controller
{
    /** Lee el carrito de la sesión. */
    private function getCarrito(): array
    {
        return session('carrito', []);
    }

    /** Guarda el carrito en la sesión. */
    private function saveCarrito(array $carrito): void
    {
        session(['carrito' => $carrito]);
    }

    public function index()
    {
        $items = [];
        $total = 0.0;

        foreach ($this->getCarrito() as $productoId => $cantidad) {
            $producto = Producto::find($productoId);

            // Si el producto ya no existe o está desactivado, se omite del carrito.
            if ($producto && $producto->activo) {
                $subtotal = (float) $producto->precio * (int) $cantidad;
                $items[] = compact('producto', 'cantidad', 'subtotal');
                $total += $subtotal;
            }
        }

        return view('cliente.carrito', compact('items', 'total'));
    }

    public function add(Request $request, Producto $producto)
    {
        // El método del modelo es `esCliente()`, no `isCliente()`. Con el nombre
        // equivocado esta línea lanzaba un error 500 en vez de redirigir, y sólo
        // aparecía al pulsar "agregar al carrito", que es justo lo que nadie
        // ejecutaba en las pruebas.
        if (! auth()->check() || ! auth()->user()->esCliente()) {
            return redirect()->route('login.cliente')
                ->with('error', 'Debes iniciar sesión o registrarte para agregar platillos al carrito.');
        }

        if (! $producto->activo || $producto->disponibleEn() < 1) {
            return back()->with('error', 'Platillo no disponible actualmente.');
        }

        $carrito = $this->getCarrito();
        $cantidadActual = (int) ($carrito[$producto->id] ?? 0);

        if ($cantidadActual + 1 > $producto->disponibleEn()) {
            return back()->with('error', 'No hay más unidades disponibles de este platillo.');
        }

        $carrito[$producto->id] = $cantidadActual + 1;
        $this->saveCarrito($carrito);

        return back()->with('success', '"'.$producto->nombre.'" agregado a tu pedido.');
    }

    public function update(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:50'],
        ], [
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad mínima es 1.',
            'cantidad.max' => 'Para pedidos mayores a 50 unidades, contacta directamente con el restaurante.',
        ]);

        $cantidad = (int) $data['cantidad'];

        if ($cantidad > $producto->disponibleEn()) {
            return back()->with('error', 'Stock insuficiente para esa cantidad solicitada.');
        }

        $carrito = $this->getCarrito();
        $carrito[$producto->id] = $cantidad;
        $this->saveCarrito($carrito);

        return back()->with('success', 'Carrito actualizado con éxito.');
    }

    public function remove(Producto $producto)
    {
        $carrito = $this->getCarrito();
        unset($carrito[$producto->id]);
        $this->saveCarrito($carrito);

        return back()->with('success', 'Platillo retirado de tu pedido.');
    }

    /**
     * Confirma el pedido: crea el registro del pedido con sus detalles y descuenta
     * el stock de cada platillo en la jornada de hoy.
     *
     * Tres detalles importantes:
     *
     * 1. Los importes NUNCA se reciben del formulario. El total se vuelve a calcular
     *    leyendo el precio desde la base de datos, así que nadie puede cambiarlo
     *    modificando el HTML desde el navegador.
     *
     * 2. El stock ya no vive en la tabla de productos sino en stock_jornadas, una fila
     *    por producto y día. Por eso el bloqueo que evita la sobreventa se hace
     *    dentro de StockService, sobre la fila de la jornada y no sobre el producto.
     *
     * 3. Todo se guarda dentro de una transacción. Si algo falla, se deshace entera y
     *    no queda un pedido con la mitad de los productos descontados.
     */
    public function checkout(CheckoutRequest $request, StockService $stock)
    {
        $carrito = $this->getCarrito();

        if ($carrito === []) {
            return redirect()
                ->route('cliente.carrito')
                ->with('error', 'Tu carrito está vacío. Agrega platillos antes de continuar.');
        }

        // El total se calcula dentro de la transacción, pero se necesita después para
        // el registro en el log, así que se declara aquí y se actualiza por referencia.
        $total = 0.0;

        try {
            DB::transaction(function () use ($carrito, $request, $stock, &$total) {
                $itemsData = [];

                // Primero se revisan todos los platillos y se calcula el total.
                foreach ($carrito as $productoId => $cantidad) {
                    $producto = Producto::find($productoId);

                    if (! $producto || ! $producto->activo) {
                        throw new RuntimeException('Uno de los platillos seleccionados ya no se encuentra disponible.');
                    }

                    $cantidad = (int) $cantidad;

                    $subtotal = (float) $producto->precio * $cantidad;
                    $total += $subtotal;

                    $itemsData[] = compact('producto', 'cantidad') + ['subtotal' => $subtotal];
                }

                $pedido = Pedido::create([
                    'user_id' => $request->user()->id,
                    'estado' => Pedido::ESTADO_PENDIENTE,
                    'total' => $total,
                    'direccion_entrega' => $request->validated('direccion_entrega'),
                    'telefono_contacto' => $request->validated('telefono_contacto'),
                    'notas' => $request->validated('notas'),
                ]);

                // Se guardan las líneas del pedido y se descuenta el stock de la
                // jornada. El servicio comprueba disponibilidad y bloquea la fila,
                // así que aquí no se toca `productos.stock`: esa columna ya no existe.
                foreach ($itemsData as $item) {
                    PedidoItem::create([
                        'pedido_id' => $pedido->id,
                        'producto_id' => $item['producto']->id,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['producto']->precio,
                        'subtotal' => $item['subtotal'],
                    ]);
                }

                $stock->descontar($itemsData);
            });
        } catch (RuntimeException $e) {
            // El mensaje es de negocio (stock, disponibilidad), así que se puede
            // mostrar tal cual al cliente.
            return back()->withInput()->with('error', $e->getMessage());
        }

        Log::info('Pedido de delivery confirmado.', [
            'usuario_id' => $request->user()->id,
            'total' => $total,
            'ip' => $request->ip(),
        ]);

        $this->saveCarrito([]);

        return redirect()
            ->route('cliente.pedidos')
            ->with('success', '¡Pedido realizado con éxito en Doña Ross! Ya estamos preparándolo.');
    }
}