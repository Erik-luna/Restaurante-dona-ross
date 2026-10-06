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

/**
 * Módulo de caja: apertura, movimientos, cobro y cierre con arqueo.
 *
 * La misma clase sirve al panel del cajero y al del administrador. Lo único que
 * cambia entre los dos es el alcance: el cajero sólo ve su caja, el administrador
 * ve todas. Esa diferencia se aplica en puedeVer(), que se consulta en cada
 * acción, en lugar de crear dos controladores idénticos.
 *
 * Todos los importes los calcula el servidor a partir de los pedidos y los
 * movimientos guardados. El único número que acepta del formulario es el dinero
 * que el cajero contó a mano en el cierre, porque es una observación y no un dato
 * del sistema: si se aceptara como referencia, un descuadre desaparecería de la
 * pantalla en vez de quedar documentado.
 */
class CajaController extends Controller
{
    /**
     * Listado de cajas.
     *
     * El cajero ve su propio histórico; el administrador, todas las cajas del
     * restaurante.
     */
    public function index(Request $request)
    {
        $cajas = Caja::with('user')
            ->when($request->user()->esCajero(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('fecha')
            ->latest('id')
            ->paginate(15);

        return view('caja.index', compact('cajas'));
    }

    /** Apertura de caja: la caja del turno, vacía y recién abierta. */
    public function create()
    {
        $caja = Caja::abiertaDeHoy(auth()->id());

        // Si ya tiene una caja abierta no se le ofrece abrir otra: el dinero del
        // turno quedaría partido en dos arqueos.
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

    /**
     * Detalle de una caja: cuánto entró, cuánto salió y cómo va el turno.
     *
     * Se listan las dos fuentes de dinero por separado porque son operaciones
     * distintas: las ventas se pagaron aquí mismo, y los cobros de delivery son
     * pedidos que entraron por la web y se pagaron al entregar.
     */
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

    /** Formulario de entrada o salida de efectivo. */
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

    /**
     * Confirmación de cobro de un pedido.
     *
     * Es la operación que hace que un pedido de delivery deje de estar "por
     * cobrar": el dinero entra a la caja abierta y se anota el método de pago y
     * el cambio.
     *
     * El cambio lo calcula el servidor como recibido - total. Si se aceptara del
     * formulario, alguien podría pedir que el sistema le devolviera más dinero del
     * que entregó.
     */
    public function cobrar(PedidoCobroRequest $request, Pedido $pedido)
    {
        $caja = $request->cajaAbierta();
        $metodo = $request->validated('metodo_pago');

        // El total y el pedido se releen dentro de la transacción para que el cobro
        // use el importe real, no el que el navegador enviara hace un segundo.
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

    /** Pantalla de arqueo: el sistema dice cuánto debería haber y el usuario cuenta. */
    public function cerrar(Request $request, Caja $caja)
    {
        abort_unless($this->puedeVer($request, $caja), 403);

        return view('caja.cierre', compact('caja'));
    }

    /**
     * Cierre con arqueo.
     *
     * Se congela el resultado dentro de la fila: ventas, cobros, entradas,
     * salidas, esperado, declarado y diferencia. A partir de ese momento el arqueo
     * es un dato histórico y no se vuelve a recalcular, aunque después se cobre un
     * pedido antiguo: ese cobro irá a la caja del día en que se cobre, nunca a una
     * caja ya cerrada.
     */
    public function storeCierre(CajaCierreRequest $request, Caja $caja)
    {
        $declarado = (float) $request->validated('total_declarado');

        $cajaCerrada = DB::transaction(function () use ($caja, $declarado, $request) {
            // Se bloquea la caja para que dos cierres simultáneos no puedan grabarla
            // los dos con números distintos.
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

        // Un descuadre no se bloquea ni avisa por correo: se cierra la caja y se
        // muestra el número. El arqueo documenta lo que pasó; esconderlo para que
        // la pantalla quede bonita sería falsear el registro.
        $mensaje = $diferencia === 0.0
            ? 'Caja cerrada. El arqueo cuadró exactamente.'
            : 'Caja cerrada con una diferencia de Bs. '.number_format(abs($diferencia), 2).'.'
                .($diferencia > 0 ? ' Sobró dinero.' : ' Faltó dinero.');

        return redirect()
            ->route($request->user()->panel().'.caja.show', $cajaCerrada)
            ->with($diferencia === 0.0 ? 'success' : 'warning', $mensaje);
    }

    /**
     * ¿Este usuario puede ver esta caja?
     *
     * El administrador puede ver todas; el cajero, sólo las suyas.
     */
    private function puedeVer(Request $request, Caja $caja): bool
    {
        return $request->user()->esAdministrador() || $caja->user_id === $request->user()->id;
    }
}