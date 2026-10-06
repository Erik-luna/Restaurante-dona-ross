@extends('layouts.admin')
@section('panel-title', 'Caja')
@section('sidebar')@include('partials.sidebar')@endsection
@section('title', 'Cajas del Día')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Cajas</h2>
        <p class="text-muted small mb-0">
            {{ auth()->user()->esAdministrador()
                ? 'Histórico de cajas de todo el restaurante.'
                : 'Histórico de tus cajas y arqueos.' }}
        </p>
    </div>
    <a href="{{ route(auth()->user()->panel().'.caja.create') }}" class="btn btn-ross-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Abrir Caja
    </a>
</div>

<div class="table-card p-0">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th style="width: 70px;">#</th>
                    <th>Fecha</th>
                    @if(auth()->user()->esAdministrador())
                        <th>Abrió</th>
                    @endif
                    <th>Inicial</th>
                    <th>Ventas</th>
                    <th>Cobros</th>
                    <th>Esperado</th>
                    <th>Contado</th>
                    <th>Diferencia</th>
                    <th>Estado</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cajas as $caja)
                    @php $arqueo = $caja->arqueo(); @endphp
                    <tr>
                        <td class="fw-bold text-muted">#{{ $caja->id }}</td>
                        <td>{{ $caja->fecha->format('d/m/Y') }}</td>
                        @if(auth()->user()->esAdministrador())
                            <td>{{ $caja->user?->name ?? 'Eliminado' }}</td>
                        @endif
                        <td>Bs. {{ number_format((float) $caja->monto_inicial, 2) }}</td>
                        <td>Bs. {{ number_format($arqueo['ventas'], 2) }}</td>
                        <td>Bs. {{ number_format($arqueo['cobros'], 2) }}</td>
                        <td>Bs. {{ number_format($arqueo['esperado'], 2) }}</td>
                        @if($caja->estaAbierta())
                            <td class="text-muted">-</td>
                            <td class="text-muted">-</td>
                        @else
                            <td>Bs. {{ number_format($arqueo['declarado'], 2) }}</td>
                            <td>
                                @if($caja->cuadra())
                                    <span class="badge rounded-pill px-3 py-1 bg-success-subtle text-success border border-success">
                                        Cuadró
                                    </span>
                                @else
                                    <span class="fw-bold text-danger">
                                        {{ $arqueo['diferencia'] > 0 ? '+' : '' }} Bs. {{ number_format($arqueo['diferencia'], 2) }}
                                    </span>
                                @endif
                            </td>
                        @endif
                        <td>
                            <span class="badge rounded-pill px-3 py-1 {{ $caja->estaAbierta() ? 'bg-success-subtle text-success border border-success' : 'bg-secondary-subtle text-secondary border' }}">
                                {{ $caja->estaAbierta() ? 'Abierta' : 'Cerrada' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route(auth()->user()->panel().'.caja.show', $caja) }}" class="btn btn-sm btn-ross-primary rounded-pill px-3">
                                <i class="bi bi-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->esAdministrador() ? 10 : 9 }}" class="text-center text-muted py-5">
                            Todavía no hay cajas registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center">
    {{ $cajas->links('pagination::bootstrap-5') }}
</div>
@endsection