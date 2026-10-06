<?php

namespace App\Http\Controllers;

use App\Http\Requests\VentaLocalRequestWeb;
use App\Models\Caja;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Ventas presenciales (consumo en el salón).
 *
 * La venta de salón es una entidad propia, con su tabla (ventas) y sus líneas
 * (venta_items). Antes vivía en `pedidos` con una columna `tipo` que obligaba a
 * acordarse de filtrar en cada consulta; ahora cada consulta dice sola qué pide.
 *
 * Cada venta queda atada a la caja abierta en el momento de registrarla. Eso
 * permite que el arqueo del turno sume lo que realmente entró al cajón, y no
 * permite registrar una venta sin caja: si se pudiera vender sin abrir caja, el
 * dinero sería real pero no constaría en ningún arqueo.
 */
class VentaController extends Controller
{
    public function index()
    {
        $ventas = Venta::with(['user', 'atendidoPor', 'caja'])
            ->latest('id')
            ->paginate(15);

        return view('ventas.index', compact('ventas'));
    }

    public function create()
    {
        $productos = Producto::where('activo', true)->conDisponible()->orderBy('nombre')->get();
        $clientes = User::where('role', User::ROL_CLIENTE)->where('activo', true)->orderBy('name')->get();

        // Sin caja abierta no se puede cobrar: se avisa antes de llenar el
        // formulario, no después de enviarlo.
        $caja = Caja::abiertaDeHoy(auth()->id());

        return view('ventas.create', compact('productos', 'clientes', 'caja'));
    }

    /**
     * Registro de venta presencial.
     *
     * Nota sobre el `strip_tags` que esta versión usaba: se eliminó. No es
     * sanitización XSS — `strip_tags('<script>alert(1)</script>')` devuelve
     * `alert(1)`, es decir, destruye el marcado pero conserva el contenido, y
     * además mutaba el payload de la petición DESPUÉS de validar. La protección
     * real es la validación por reglas (`notas` es texto plano acotado a 500
     * caracteres) más el escapado de Blade en la vista al mostrar las notas.
     *
     * El precio nunca se recibe del cliente: se lee del producto dentro de la
     * transacción. Y el stock tampoco se descuenta desde aquí: lo baja StockService
     * bloqueando la fila de la jornada del día, que es donde vive la cuenta.
     */
    public function store(VentaLocalRequestWeb $request, StockService $stock)
    {
        $data = $request->validated();
        $ventaId = null;

        // Se comprueba la caja ANTES de la transacción. Si no hay ninguna abierta
        // la venta no se registra: es preferible rechazarla a guardar una venta
        // cuyo dinero no se puede atribuir a ninguna caja.
        $caja = Caja::abiertaDeHoy(auth()->id());

        if ($caja === null) {
            return redirect()
                ->route(auth()->user()->panel().'.caja.create')
                ->with('error', 'Primero abre tu caja del día para poder registrar ventas.');
        }

        try {
            DB::transaction(function () use ($data, &$ventaId, $caja, $request, $stock) {
                $total = 0;
                $itemsData = [];

                foreach ($data['items'] as $item) {
                    $producto = Producto::find($item['producto_id']);

                    if (! $producto || ! $producto->activo) {
                        throw new RuntimeException('Uno de los productos seleccionados no se encuentra activo.');
                    }

                    $subtotal = $producto->precio * $item['cantidad'];
                    $total += $subtotal;
                    $itemsData[] = [
                        'producto' => $producto,
                        'cantidad' => $item['cantidad'],
                        'subtotal' => $subtotal,
                    ];
                }

                // El pago de una venta de salón se registra junto con la venta: el
                // cliente paga en el mostrador, así que no hay un paso de cobro
                // posterior como en el delivery. La venta nace 'pagada'.
                $metodo = (string) $request->input('metodo_pago', Venta::PAGO_EFECTIVO);
                $total = round($total, 2);
                $recibido = $request->filled('monto_recibido')
                    ? round((float) $request->input('monto_recibido'), 2)
                    : $total;

                // Entregar menos de lo que vale la venta no es un cobro. Se
                // comprueba aquí porque el total depende de los precios de la base
                // de datos y el formulario no puede conocerlo de antemano.
                if ($metodo === Venta::PAGO_EFECTIVO && $recibido < $total) {
                    throw new RuntimeException(
                        'El dinero recibido (Bs. '.number_format($recibido, 2)
                        .') es menor que el total de la venta (Bs. '.number_format($total, 2).').'
                    );
                }

                $venta = Venta::create([
                    'user_id' => $data['user_id'] ?? null,
                    'caja_id' => $caja->id,
                    'estado' => Venta::PAGADA,
                    'total' => $total,
                    'notas' => $data['notas'] ?? null,
                    'atendido_por' => auth()->id(),
                    'metodo_pago' => $metodo,
                    'monto_recibido' => $metodo === Venta::PAGO_EFECTIVO ? $recibido : null,
                    'cambio' => $metodo === Venta::PAGO_EFECTIVO ? round($recibido - $total, 2) : null,
                ]);

                $ventaId = $venta->id;

                foreach ($itemsData as $item) {
                    VentaItem::create([
                        'venta_id' => $venta->id,
                        'producto_id' => $item['producto']->id,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['producto']->precio,
                        'subtotal' => $item['subtotal'],
                    ]);
                }

                // El consumo va después de guardar la venta: si el stock no alcanza,
                // la excepción revierte también la venta y no queda una venta pagada
                // por productos que nunca salieron de la cocina.
                $stock->descontar($itemsData);
            });
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        Log::info('Venta presencial registrada.', [
            'venta_id' => $ventaId,
            'caja_id' => $caja->id,
            'usuario_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);

        // Cada rol vuelve a su propio listado: el cajero al suyo y el
        // administrador al del panel de administración.
        $ruta = auth()->user()->esAdministrador() ? 'admin.ventas.index' : 'cajero.ventas.index';

        return redirect()->route($ruta)->with('success', 'Venta registrada correctamente.');
    }

    public function show(Venta $venta)
    {
        $venta->load(['user', 'items.producto', 'atendidoPor', 'caja.user']);

        return view('ventas.show', compact('venta'));
    }
}