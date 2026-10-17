@extends('layouts.admin')
@section('panel-title', 'Administración')
@section('sidebar')@include('partials.sidebar')@endsection
@section('title', 'Control de Stock')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Control de Stock</h2>
        <p class="text-muted small mb-0">
            Portciones de hoy ({{ now()->format('d/m/Y') }}). El disponible sale de
            <code class="small">cargadas + repuestas &minus; mermas &minus; vendidas</code>.
        </p>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="table-card p-0 mb-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th style="width: 70px;"># ID</th>
                    <th>Platillo</th>
                    <th class="text-center">Cargadas</th>
                    <th class="text-center">Repuestas</th>
                    <th class="text-center">Mermas</th>
                    <th class="text-center">Vendidas</th>
                    <th>Disponible hoy</th>
                    <th style="width: 330px;">Ajuste</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productos as $p)
                    @php $disponible = (int) ($p->disponible ?? 0); @endphp
                <tr>
                    <td class="fw-bold text-muted">#{{ $p->id }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ $p->imagen_url ?: asset('img/platos/plato1.jpeg') }}"
                                 onerror="this.onerror=null;this.src='{{ asset('img/platos/plato1.jpeg') }}';"
                                 alt="{{ $p->nombre }}"
                                 class="rounded-3 shadow-sm"
                                 style="width: 44px; height: 44px; object-fit: cover;">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">{{ $p->nombre }}</h6>
                                <small class="text-muted">{{ $p->categoria->nombre ?? 'Menú General' }} &bull; Bs. {{ number_format($p->precio, 2) }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center fw-semibold">{{ (int) ($p->cargado ?? 0) }}</td>
                    <td class="text-center fw-semibold">{{ (int) ($p->reposto ?? 0) }}</td>
                    <td class="text-center fw-semibold {{ (int) ($p->perdido ?? 0) > 0 ? 'text-danger' : '' }}">{{ (int) ($p->perdido ?? 0) }}</td>
                    <td class="text-center fw-semibold">{{ (int) ($p->vendido ?? 0) }}</td>
                    <td>
                        @if($disponible > 10)
                            <span class="badge bg-success-subtle text-success border border-success px-3 py-1">
                                <i class="bi bi-check-circle me-1"></i> {{ $disponible }} disponibles
                            </span>
                        @elseif($disponible > 0)
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-3 py-1">
                                <i class="bi bi-exclamation-triangle me-1"></i> Stock Bajo ({{ $disponible }} u.)
                            </span>
                        @elseif($disponible < 0)
                            <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-1">
                                <i class="bi bi-bug me-1"></i> Negativo ({{ $disponible }} u.)
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-1">
                                <i class="bi bi-x-circle me-1"></i> Agotado (0 u.)
                            </span>
                        @endif

                        @if(is_null($p->cargado))
                            <small class="d-block text-muted fst-italic">Sin carga registrada hoy.</small>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route(auth()->user()->panel().'.stock.update', $p) }}" method="POST" class="d-flex flex-wrap align-items-center gap-2">
                            @csrf @method('PUT')

                            <select name="accion" class="form-select form-select-sm" style="width: 130px;" aria-label="Tipo de ajuste">
                                <option value="cargar">Cargar día</option>
                                <option value="reponer">Reponer</option>
                                <option value="merma">Merma</option>
                            </select>

                            <input type="number" name="cantidad" value="1" min="1" max="100000" required
                                   class="form-control form-control-sm text-center fw-bold" style="width: 84px;"
                                   aria-label="Cantidad de porciones">

                            <input type="text" name="motivo" maxlength="200" placeholder="Motivo (merma)"
                                   class="form-control form-control-sm" style="width: 140px;" aria-label="Motivo">

                            <button type="submit" class="btn btn-sm btn-ross-secondary rounded-pill px-3">
                                <i class="bi bi-save"></i> Aplicar
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">No hay productos en inventario.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
        <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i> Cómo se lee esta pantalla</h6>
        <ul class="small text-muted mb-0 ps-3">
            <li><strong>Cargar día</strong> fija cuántas porción(es) se preparan hoy. Si ya habías cargado mal un número, volver a cargar lo corrige.</li>
            <li><strong>Reponer</strong> suma porción(es) que llegaron del proveedor durante el servicio.</li>
            <li><strong>Merma</strong> resta porción(es) perdidas sin venderse y pide un motivo.</li>
            <li>Las ventas y los pedidos de delivery descuentan solos; por eso no hay un campo para hacerlo a mano.</li>
        </ul>
    </div>
</div>
@endsection