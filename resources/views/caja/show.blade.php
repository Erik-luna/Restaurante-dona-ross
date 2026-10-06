@extends('layouts.admin')
@section('panel-title', 'Caja')
@section('sidebar')@include('partials.sidebar')@endsection
@section('title', 'Caja #'.$caja->id)

@section('content')
@php $resumen = $caja->arqueo(); @endphp

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">
            Caja del {{ $caja->fecha->format('d/m/Y') }}
        </h2>
        <p class="text-muted small mb-0">
            Abierta por {{ $caja->user?->name ?? 'usuario eliminado' }}
            @if($caja->apertura_at)
                el {{ $caja->apertura_at->format('d/m/Y \a \l\a\s H:i') }}
            @endif
            @if($caja->cierre_at)
                &middot; cerrada el {{ $caja->cierre_at->format('d/m/Y H:i') }}
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        @if($caja->estaAbierta())
            <a href="{{ route(auth()->user()->panel().'.caja.movimiento.create', $caja) }}" class="btn btn-ross-secondary btn-sm">
                <i class="bi bi-arrow-left-right me-1"></i> Movimiento
            </a>
            <a href="{{ route(auth()->user()->panel().'.caja.cierre', $caja) }}" class="btn btn-ross-primary btn-sm">
                <i class="bi bi-calculator me-1"></i> Cerrar y Arquear
            </a>
        @else
            <span class="badge rounded-pill px-3 py-2 {{ $caja->cuadra() ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger' }}">
                {{ $caja->cuadra() ? 'Arqueo cerrado: cuadró' : 'Arqueo cerrado: hubo diferencia' }}
            </span>
        @endif
    </div>
</div>

{{-- Los números que resumen el turno. Las ventas de salón y los cobros de
     delivery van separados porque son operaciones distintas: si el arqueo no
     cuadra, hay que poder saber de qué lado vino el problema. --}}
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card-stat d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Monto inicial</span>
                <h3 class="fw-bold mb-0" style="color: var(--ross-primary);">Bs. {{ number_format((float) $caja->monto_inicial, 2) }}</h3>
                <small class="text-muted">Con lo que se abrió la caja</small>
            </div>
            <div class="card-stat-icon" style="background: var(--ross-orange-light); color: var(--ross-orange);">
                <i class="bi bi-safe"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card-stat d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Ventas de salón</span>
                <h3 class="fw-bold mb-0" style="color: var(--ross-primary);">Bs. {{ number_format($resumen['ventas'], 2) }}</h3>
                <small class="text-muted">{{ $ventas->total() }} operaciones</small>
            </div>
            <div class="card-stat-icon" style="background: var(--ross-gold-light); color: #B45309;">
                <i class="bi bi-receipt"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card-stat d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Cobros delivery</span>
                <h3 class="fw-bold mb-0" style="color: var(--ross-primary);">Bs. {{ number_format($resumen['cobros'], 2) }}</h3>
                <small class="text-muted">{{ $cobros->total() }} pedidos cobrados</small>
            </div>
            <div class="card-stat-icon" style="background: var(--ross-orange-light); color: var(--ross-orange);">
                <i class="bi bi-bicycle"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card-stat d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Esperado en caja</span>
                <h3 class="fw-bold mb-0" style="color: var(--ross-primary);">Bs. {{ number_format($resumen['esperado'], 2) }}</h3>
                <small class="text-muted">
                    Ingresos Bs. {{ number_format($resumen['ingresos'], 2) }}
                    + Bs. {{ number_format($resumen['entradas'], 2) }} entradas
                    &minus; Bs. {{ number_format($resumen['salidas'], 2) }} salidas
                </small>
            </div>
            <div class="card-stat-icon" style="background: #EFF6FF; color: #3B82F6;">
                <i class="bi bi-calculator"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card-stat d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Diferencia</span>
                @if($caja->estaAbierta())
                    <h3 class="fw-bold mb-0 text-muted">Pendiente</h3>
                    <small class="text-muted">Se cuenta al cerrar</small>
                @else
                    <h3 class="fw-bold mb-0 {{ $caja->cuadra() ? 'text-success' : 'text-danger' }}">
                        {{ $resumen['diferencia'] > 0 ? '+' : '' }} Bs. {{ number_format($resumen['diferencia'], 2) }}
                    </h3>
                    <small class="text-muted">
                        Contado: Bs. {{ number_format($resumen['declarado'], 2) }}
                    </small>
                @endif
            </div>
            <div class="card-stat-icon" style="background: var(--ross-orange-light); color: var(--ross-orange);">
                <i class="bi bi-scale"></i>
            </div>
        </div>
    </div>
</div>

@if($caja->observacion_apertura)
    <div class="p-3 rounded-3 border bg-light mb-4 small">
        <strong>Apertura:</strong> {{ $caja->observacion_apertura }}
    </div>
@endif

@if(! $caja->estaAbierta() && $caja->observacion_cierre)
    <div class="p-3 rounded-3 border bg-light mb-4 small">
        <strong>Cierre:</strong> {{ $caja->observacion_cierre }}
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="table-card p-0">
            <div class="p-4 pb-2">
                <h5 class="fw-bold mb-0 font-serif" style="color: var(--ross-primary);">
                    <i class="bi bi-receipt-cutoff text-warning me-2"></i> Ventas de salón
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Atendió</th>
                            <th>Pago</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventas as $venta)
                            <tr>
                                <td class="fw-bold text-muted">#{{ $venta->id }}</td>
                                <td>{{ $venta->user?->name ?? 'Cliente de salón' }}</td>
                                <td>{{ $venta->atendidoPor?->name ?? '-' }}</td>
                                <td class="text-capitalize">{{ $venta->metodo_pago ?? '-' }}</td>
                                <td class="text-end fw-bold" style="color: var(--ross-primary);">
                                    Bs. {{ number_format((float) $venta->total, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Esta caja todavía no registra ventas de salón.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $ventas->links('pagination::bootstrap-5') }}
        </div>

        <div class="table-card p-0 mt-4">
            <div class="p-4 pb-2">
                <h5 class="fw-bold mb-0 font-serif" style="color: var(--ross-primary);">
                    <i class="bi bi-bicycle text-warning me-2"></i> Cobros de pedidos delivery
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Cobrado</th>
                            <th>Pago</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cobros as $cobro)
                            <tr>
                                <td class="fw-bold text-muted">#{{ $cobro->id }}</td>
                                <td>{{ $cobro->user?->name ?? '-' }}</td>
                                <td>{{ $cobro->pagado_at?->format('d/m H:i') }}</td>
                                <td class="text-capitalize">{{ $cobro->metodo_pago ?? '-' }}</td>
                                <td class="text-end fw-bold" style="color: var(--ross-primary);">
                                    Bs. {{ number_format((float) $cobro->total, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Esta caja todavía no cobra ningún pedido de delivery.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $cobros->links('pagination::bootstrap-5') }}
        </div>
    </div>

    <div class="col-lg-5">
        <div class="table-card p-0">
            <div class="p-4 pb-2">
                <h5 class="fw-bold mb-0 font-serif" style="color: var(--ross-primary);">
                    <i class="bi bi-arrow-left-right text-warning me-2"></i> Movimientos de efectivo
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th>Registro</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($caja->movimientos->sortByDesc('id') as $movimiento)
                            <tr>
                                <td>
                                    {{ $movimiento->concepto }}
                                    <span class="badge rounded-pill ms-1 {{ $movimiento->esEntrada() ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                        {{ $movimiento->esEntrada() ? 'Entrada' : 'Salida' }}
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    {{ $movimiento->user?->name ?? '-' }}<br>
                                    {{ $movimiento->created_at?->format('d/m H:i') }}
                                </td>
                                <td class="text-end fw-bold {{ $movimiento->esEntrada() ? 'text-success' : 'text-danger' }}">
                                    {{ $movimiento->esEntrada() ? '+' : '-' }} Bs. {{ number_format((float) $movimiento->monto, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Sin movimientos de efectivo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($caja->estaAbierta())
            <div class="p-3 rounded-3 border bg-light mt-4 small text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Los cobros de los pedidos de delivery pendientes se registran desde la
                ficha del pedido, y el dinero entra a esta caja automáticamente.
            </div>
        @endif
    </div>
</div>
@endsection