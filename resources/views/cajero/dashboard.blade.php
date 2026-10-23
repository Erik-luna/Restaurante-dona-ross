@extends('layouts.admin')
@section('panel-title', 'Cajero')
@section('sidebar')@include('partials.sidebar')@endsection
@section('title', 'Panel del Cajero')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Panel de Ventas del Salón</h2>
        <p class="text-muted small mb-0">Registra ventas presenciales y consulta los pedidos por cobrar.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($caja)
            <a href="{{ route('cajero.caja.show', $caja) }}" class="btn btn-ross-secondary btn-sm">
                <i class="bi bi-safe me-1"></i> Mi Caja
            </a>
        @else
            <a href="{{ route('cajero.caja.create') }}" class="btn btn-ross-primary btn-sm">
                <i class="bi bi-unlock me-1"></i> Abrir Caja
            </a>
        @endif
        <a href="{{ route('cajero.ventas.create') }}" class="btn btn-ross-primary btn-sm">
            <i class="bi bi-cart-plus-fill me-1"></i> Registrar Venta
        </a>
        <a href="{{ route('cajero.pedidos.index') }}" class="btn btn-ross-secondary btn-sm">
            <i class="bi bi-list-check me-1"></i> Pedidos Online
        </a>
    </div>
</div>



@if(! $caja)
    <div class="p-4 rounded-4 border border-warning bg-warning-subtle mb-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
            <div>
                <strong class="d-block">Todavía no abriste tu caja de hoy</strong>
                <span class="small">
                    Las ventas del salón y los cobros de delivery necesitan una caja abierta
                    para poder registrar el dinero.
                </span>
            </div>
        </div>
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card-stat d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Ventas de Hoy</span>
                <h3 class="fw-bold mb-0" style="color: var(--ross-primary);">Bs. {{ number_format($stats['ventas_hoy'], 2) }}</h3>
                <small class="text-success fw-semibold"><i class="bi bi-graph-up-arrow me-1"></i> Cobrado por ti hoy</small>
            </div>
            <div class="card-stat-icon" style="background: var(--ross-orange-light); color: var(--ross-orange);">
                <i class="bi bi-cash-coin"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4">
        <div class="card-stat d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Ventas Registradas</span>
                <h3 class="fw-bold mb-0" style="color: var(--ross-primary);">{{ $stats['pedidos_hoy'] }}</h3>
                <small class="text-muted">Órdenes de salón de hoy</small>
            </div>
            <div class="card-stat-icon" style="background: var(--ross-gold-light); color: #B45309;">
                <i class="bi bi-check2-circle"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4">
        <div class="card-stat d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Pedidos por Cobrar</span>
                <h3 class="fw-bold mb-0" style="color: var(--ross-primary);">{{ $stats['por_cobrar'] }}</h3>
                <small class="text-muted">Bs. {{ number_format($stats['por_cobrar_monto'], 2) }} pendientes</small>
            </div>
            <div class="card-stat-icon" style="background: #EFF6FF; color: #3B82F6;">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
    </div>
</div>

<div class="table-card p-0">
    <div class="p-4 pb-0">
        <h5 class="fw-bold mb-3 font-serif" style="color: var(--ross-primary);">
            <i class="bi bi-lightning-charge-fill text-warning me-2"></i> Acciones Rápidas
        </h5>
    </div>
    <div class="row g-3 p-4 pt-2">
        <div class="col-md-6">
            <div class="p-3 rounded-3 border bg-light d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                <div>
                    <h6 class="fw-bold mb-1">Venta en Mostrador / Mesa</h6>
                    <p class="text-muted small mb-0">Registrar un nuevo pedido local o para llevar.</p>
                </div>
                <a href="{{ route('cajero.ventas.create') }}" class="btn btn-ross-primary btn-sm px-3 flex-shrink-0">
                    <i class="bi bi-plus-lg"></i> Iniciar
                </a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded-3 border bg-light d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                <div>
                    <h6 class="fw-bold mb-1">Consultar Pedidos Online</h6>
                    <p class="text-muted small mb-0">Ver lo que viene de delivery y su estado de cobro.</p>
                </div>
                <a href="{{ route('cajero.pedidos.index') }}" class="btn btn-ross-secondary btn-sm px-3 flex-shrink-0">
                    <i class="bi bi-list-check"></i> Ver
                </a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded-3 border bg-light d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                <div>
                    <h6 class="fw-bold mb-1">{{ $caja ? 'Movimiento de caja' : 'Abrir caja del turno' }}</h6>
                    <p class="text-muted small mb-0">
                        {{ $caja ? 'Entradas y salidas de efectivo que no son ventas.' : 'Registra el monto con el que arrancas.' }}
                    </p>
                </div>
                <a href="{{ route('cajero.caja.index') }}" class="btn btn-ross-secondary btn-sm px-3 flex-shrink-0">
                    <i class="bi bi-safe"></i> Caja
                </a>
            </div>
        </div>
    </div>
</div>

@if($ultimasVentas->isNotEmpty())
    <div class="table-card p-0 mt-4">
        <div class="p-4 pb-2">
            <h5 class="fw-bold mb-0 font-serif" style="color: var(--ross-primary);">
                <i class="bi bi-clock-history text-warning me-2"></i> Tus Últimas Ventas
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ultimasVentas as $venta)
                        <tr>
                            <td class="fw-bold text-muted">#{{ $venta->id }}</td>
                            <td>{{ $venta->user?->name ?? 'Cliente de salón' }}</td>
                            <td class="text-muted small">{{ $venta->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-end fw-bold" style="color: var(--ross-primary);">
                                Bs. {{ number_format($venta->total, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection