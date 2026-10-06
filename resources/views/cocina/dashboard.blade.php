@extends('layouts.admin')
@section('panel-title', 'Cocina')
@section('sidebar')@include('partials.sidebar')@endsection
@section('title', 'Panel de Cocina')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Pedidos de Cocina</h2>
        <p class="text-muted small mb-0">Marcá cada pedido como se vaya preparando y controlá las porciones del día.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('cocina.stock.index') }}" class="btn btn-ross-secondary btn-sm">
            <i class="bi bi-boxes me-1"></i> Porciones
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="card-stat d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Por Preparar</span>
                <h3 class="fw-bold mb-0" style="color: var(--ross-primary);">{{ $stats['por_preparar'] }}</h3>
            </div>
            <div class="card-stat-icon" style="background: var(--ross-orange-light); color: var(--ross-orange);">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card-stat d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">En Cocina</span>
                <h3 class="fw-bold mb-0" style="color: var(--ross-primary);">{{ $stats['preparando'] }}</h3>
            </div>
            <div class="card-stat-icon" style="background: var(--ross-gold-light); color: #B45309;">
                <i class="bi bi-fire"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card-stat d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Listos</span>
                <h3 class="fw-bold mb-0" style="color: var(--ross-primary);">{{ $stats['listos'] }}</h3>
            </div>
            <div class="card-stat-icon" style="background: #EFF6FF; color: #3B82F6;">
                <i class="bi bi-bag-check"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card-stat d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Stock Bajo</span>
                <h3 class="fw-bold mb-0" style="color: var(--ross-primary);">{{ $stats['stock_bajo'] }}</h3>
                <small class="text-muted">Platos con 5 o menos</small>
            </div>
            <div class="card-stat-icon" style="background: #FEE2E2; color: #DC2626;">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
        </div>
    </div>
</div>

{{--
    Los pedidos que la cocina tiene que atender. Cada uno muestra qué se pidió y
    el botón para pasarlo al siguiente estado. El formulario va a la misma ruta
    que usa el administrador: lo que cambia son los permisos, no la operación.
--}}
<div class="table-card p-0">
    <div class="p-4 pb-2">
        <h5 class="fw-bold mb-0 font-serif" style="color: var(--ross-primary);">
            <i class="bi bi-list-check text-warning me-2"></i> Cola de Pedidos
        </h5>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Pedido</th>
                    <th>Recibido</th>
                    <th>Estado</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pedidos as $pedido)
                    <tr>
                        <td class="fw-bold text-muted">#{{ $pedido->id }}</td>
                        <td>
                            <span class="fw-bold text-dark d-block">{{ $pedido->user?->name ?? 'Cliente' }}</span>
                            <small class="text-muted">{{ $pedido->telefono_contacto ?? $pedido->user?->telefono ?? '-' }}</small>
                        </td>
                        <td>
                            @foreach($pedido->items as $item)
                                <span class="badge bg-light text-dark border mb-1">
                                    {{ $item->cantidad }}&times; {{ $item->producto->nombre }}
                                </span>
                            @endforeach
                        </td>
                        <td class="text-muted small">{{ $pedido->created_at?->format('H:i') }}</td>
                        <td>
                            <span class="badge rounded-pill px-3 py-1 text-uppercase"
                                  style="font-size: 0.7rem; background: var(--ross-primary); color: #fff;">
                                {{ $pedido->estado }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('cocina.pedidos.show', $pedido) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                Ver
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            No hay pedidos en cocina en este momento.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($pedidos->hasPages())
        <div class="p-3 border-top">
            {{ $pedidos->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection