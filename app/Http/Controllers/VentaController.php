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

        $caja = Caja::abiertaDeHoy(auth()->id());

        return view('ventas.create', compact('productos', 'clientes', 'caja'));
    }
    public function store(VentaLocalRequestWeb $request, StockService $stock)
    {
        $data = $request->validated();
        $ventaId = null;
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
                $metodo = (string) $request->input('metodo_pago', Venta::PAGO_EFECTIVO);
                $total = round($total, 2);
                $recibido = $request->filled('monto_recibido')
                    ? round((float) $request->input('monto_recibido'), 2)
                    : $total;
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
        $ruta = auth()->user()->esAdministrador() ? 'admin.ventas.index' : 'cajero.ventas.index';

        return redirect()->route($ruta)->with('success', 'Venta registrada correctamente.');
    }

    public function show(Venta $venta)
    {
        $venta->load(['user', 'items.producto', 'atendidoPor', 'caja.user']);

        return view('ventas.show', compact('venta'));
    }
}