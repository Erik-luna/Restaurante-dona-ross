<?php

namespace App\Http\Controllers;

use App\Http\Requests\CajaAperturaRequest;
use App\Http\Requests\CajaCierreRequest;
use App\Http\Requests\CajaMovimientoRequest;
use App\Http\Requests\PedidoCobroRequest;
use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\Pedido;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CajaController extends Controller
{

    public function index(Request $request)
    {
        $cajas = Caja::with('user')
            ->when($request->user()->esCajero(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('fecha')
            ->latest('id')
            ->paginate(15);

        return view('caja.index', compact('cajas'));
    }

    public function create()
    {
        $caja = Caja::abiertaDeHoy(auth()->id());

        if ($caja !== null) {
            return redirect()
                ->route(auth()->user()->panel().'.caja.show', $caja)
                ->with('info', 'Ya tienes una caja abierta. Ciérrala con su arqueo antes de abrir otra.');
        }

        return view('caja.create');
    }

    public function store(CajaAperturaRequest $request)
    {
        $caja = Caja::create([
            'user_id' => $request->user()->id,
            'fecha' => today(),
            'monto_inicial' => $request->validated('monto_inicial'),
            'estado' => Caja::ABIERTA,
            'apertura_at' => now(),
            'observacion_apertura' => $request->validated('observacion_apertura'),
        ]);

        Log::info('Caja abierta.', [
            'caja_id' => $caja->id,
            'usuario_id' => $request->user()->id,
            'monto_inicial' => $caja->monto_inicial,
            'ip' => $request->ip(),
        ]);

        return redirect()
            ->route($request->user()->panel().'.caja.show', $caja)
            ->with('success', 'Caja abierta con Bs. '.number_format((float) $caja->monto_inicial, 2).'.');
    }

    public function show(Request $request, Caja $caja)
    {
        abort_unless($this->puedeVer($request, $caja), 403);

        $caja->load(['user', 'movimientos.user']);

        $ventas = $caja->ventas()
            ->with('user', 'atendidoPor')
            ->where('estado', Venta::PAGADA)
            ->latest('id')
            ->paginate(15);

        $cobros = $caja->cobros()
            ->with('user')
            ->whereNotNull('pagado_at')
            ->where('estado', '!=', Pedido::ESTADO_CANCELADO)
            ->latest('pagado_at')
            ->paginate(15);

        return view('caja.show', compact('caja', 'ventas', 'cobros'));
    }

    public function crearMovimiento(Request $request, Caja $caja)
    {
        abort_unless($this->puedeVer($request, $caja), 403);

        return view('caja.movimiento', compact('caja'));
    }

    public function storeMovimiento(CajaMovimientoRequest $request, Caja $caja)
    {
        $movimiento = $caja->movimientos()->create([
            'tipo' => $request->validated('tipo'),
            'monto' => $request->validated('monto'),
            'concepto' => $request->validated('concepto'),
            'user_id' => $request->user()->id,
        ]);

        Log::info('Movimiento de caja registrado.', [
            'caja_id' => $caja->id,
            'movimiento_id' => $movimiento->id,
            'tipo' => $movimiento->tipo,
            'monto' => $movimiento->monto,
            'usuario_id' => $request->user()->id,
        ]);

        return redirect()
            ->route($request->user()->panel().'.caja.show', $caja)
            ->with('success', 'Movimiento de caja registrado.');
    }

    public function cobrar(PedidoCobroRequest $request, Pedido $pedido)
    {
        $caja = $request->cajaAbierta();
        $metodo = $request->validated('metodo_pago');
        $cobrado = DB::transaction(function () use ($pedido, $caja, $metodo, $request) {
            $pedido = Pedido::lockForUpdate()->findOrFail($pedido->id);

            $total = (float) $pedido->total;
            $recibido = $metodo === Pedido::PAGO_EFECTIVO
                ? (float) $request->validated('monto_recibido')
                : $total;

            $pedido->forceFill([
                'caja_id' => $caja->id,
                'metodo_pago' => $metodo,
                'monto_recibido' => $metodo === Pedido::PAGO_EFECTIVO ? $recibido : null,
                'cambio' => $metodo === Pedido::PAGO_EFECTIVO ? round($recibido - $total, 2) : null,
                'pagado_at' => now(),
            ])->save();

            return $pedido;
        });

        Log::info('Cobro registrado.', [
            'pedido_id' => $cobrado->id,
            'caja_id' => $caja->id,
            'total' => $cobrado->total,
            'metodo_pago' => $metodo,
            'cambio' => $cobrado->cambio,
            'usuario_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return redirect()
            ->route($request->user()->panel().'.caja.show', $caja)
            ->with('success', 'Cobro de Bs. '.number_format((float) $cobrado->total, 2).' registrado.');
    }

    public function cerrar(Request $request, Caja $caja)
    {
        abort_unless($this->puedeVer($request, $caja), 403);

        return view('caja.cierre', compact('caja'));
    }

    public function storeCierre(CajaCierreRequest $request, Caja $caja)
    {
        $declarado = (float) $request->validated('total_declarado');

        $cajaCerrada = DB::transaction(function () use ($caja, $declarado, $request) {
            $caja = Caja::lockForUpdate()->findOrFail($caja->id);
            $resumen = $caja->resumen();

            $caja->forceFill([
                'estado' => Caja::CERRADA,
                'cierre_at' => now(),
                'total_ventas' => $resumen['ventas'],
                'total_cobros' => $resumen['cobros'],
                'total_entradas' => $resumen['entradas'],
                'total_salidas' => $resumen['salidas'],
                'total_esperado' => $resumen['esperado'],
                'total_declarado' => $declarado,
                'diferencia' => round($declarado - $resumen['esperado'], 2),
                'observacion_cierre' => $request->validated('observacion_cierre'),
            ])->save();

            return $caja;
        });

        $diferencia = (float) $cajaCerrada->diferencia;

        Log::info('Caja cerrada.', [
            'caja_id' => $cajaCerrada->id,
            'esperado' => $cajaCerrada->total_esperado,
            'declarado' => $cajaCerrada->total_declarado,
            'diferencia' => $diferencia,
            'usuario_id' => $request->user()->id,
        ]);

        $mensaje = $diferencia === 0.0
            ? 'Caja cerrada. El arqueo cuadró exactamente.'
            : 'Caja cerrada con una diferencia de Bs. '.number_format(abs($diferencia), 2).'.'
                .($diferencia > 0 ? ' Sobró dinero.' : ' Faltó dinero.');

        return redirect()
            ->route($request->user()->panel().'.caja.show', $cajaCerrada)
            ->with($diferencia === 0.0 ? 'success' : 'warning', $mensaje);
    }

    private function puedeVer(Request $request, Caja $caja): bool
    {
        return $request->user()->esAdministrador() || $caja->user_id === $request->user()->id;
    }
}