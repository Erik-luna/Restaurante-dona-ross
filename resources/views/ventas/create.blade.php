@extends('layouts.admin')
@section('panel-title', auth()->user()->esAdministrador() ? 'Administración' : 'Cajero')
@section('sidebar')@include('partials.sidebar')@endsection
@section('title', 'Registrar Venta Local')

@section('content')
@php $prefix = auth()->user()->panel(); @endphp

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Nueva Venta Local / Salón</h2>
        <p class="text-muted small mb-0">Selecciona el cliente y añade los platillos ordenados.</p>
    </div>
    <a href="{{ route($prefix.'.ventas.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Volver a la lista
    </a>
</div>

@if(! $caja)
    <div class="p-4 rounded-4 border border-danger bg-danger-subtle mb-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-safe fs-3 text-danger"></i>
                <div>
                    <strong class="d-block">No tienes una caja abierta hoy</strong>
                    <span class="small">
                        Ábrela antes de registrar la venta. Si se registrara sin caja, el
                        dinero que entra no aparecería en ningún arqueo.
                    </span>
                </div>
            </div>
            <a href="{{ route($prefix.'.caja.create') }}" class="btn btn-danger btn-sm px-3 flex-shrink-0">
                <i class="bi bi-unlock me-1"></i> Abrir Caja
            </a>
        </div>
    </div>
@endif

<form action="{{ route($prefix.'.ventas.store') }}" method="POST" id="ventaForm">
    @csrf
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card-ross p-4 mb-4" style="border-top: 5px solid var(--ross-primary) !important;">
                <h5 class="fw-bold mb-3 font-serif" style="color: var(--ross-primary);">
                    <i class="bi bi-person-check text-danger me-2"></i> Datos del Comprobante
                </h5>

                <div class="mb-3">
                    <label class="form-label">Cliente *</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Seleccionar cliente...</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}" {{ old('user_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->email }})
                            </option>
                        @endforeach
                    </select>
                    <div class="mt-2">
                        @if(auth()->user()->esAdministrador())
                        <a href="{{ route('admin.clientes.create') }}" class="text-danger small text-decoration-none fw-semibold">
                            <i class="bi bi-person-plus me-1"></i> ¿Cliente nuevo? Registrar aquí
                        </a>
                    @else
                        <span class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Los clientes nuevos los registra el administrador.
                        </span>
                    @endif
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notas u Observaciones</label>
                    <input type="text" name="notas" class="form-control" value="{{ old('notas') }}" placeholder="Ej: Mesa 4, sin picante, etc.">
                </div>

                <div class="p-3 bg-light rounded-3 border mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Atendido por:</span>
                        <span class="fw-bold text-dark">{{ auth()->user()->name }}</span>
                    </div>
                    @if($caja)
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Caja:</span>
                            <a href="{{ route($prefix.'.caja.show', $caja) }}" class="fw-semibold text-danger text-decoration-none">
                                #{{ $caja->id }} · {{ $caja->fecha->format('d/m/Y') }}
                            </a>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Fecha:</span>
                        <span class="fw-semibold text-dark">{{ date('d/m/Y') }}</span>
                    </div>
                </div>

                <h6 class="fw-bold mt-4 mb-3 font-serif" style="color: var(--ross-primary);">
                    <i class="bi bi-cash-coin text-danger me-2"></i> Cobro
                </h6>

                <div class="mb-3">
                    <label class="form-label" for="metodo_pago">Método de pago</label>
                    <select name="metodo_pago" id="metodo_pago" class="form-select">
                        <option value="efectivo" {{ old('metodo_pago', 'efectivo') === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                        <option value="qr" {{ old('metodo_pago') === 'qr' ? 'selected' : '' }}>QR / Transferencia</option>
                        <option value="tarjeta" {{ old('metodo_pago') === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                    </select>
                </div>

                <div class="mb-3" id="campoEfectivo">
                    <label class="form-label" for="monto_recibido">Dinero recibido (Bs.)</label>
                    <input type="number" step="0.01" min="0" name="monto_recibido" id="monto_recibido"
                           class="form-control" value="{{ old('monto_recibido') }}"
                           placeholder="Se calcula el cambio automáticamente">
                    <div class="form-text">Déjalo vacío si el cliente paga el total exacto.</div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-ross-primary btn-lg w-100 py-3 shadow" @disabled(! $caja)>
                        <i class="bi bi-receipt-cutoff me-1"></i> Registrar y Cobrar Venta
                    </button>
                    @if(! $caja)
                        <div class="form-text text-danger text-center mt-2">Abre tu caja para poder registrar la venta.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card-ross p-4" style="border-top: 5px solid var(--ross-orange) !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 font-serif" style="color: var(--ross-primary);">
                        <i class="bi bi-cart3 text-warning me-2"></i> Platillos de la Orden
                    </h5>
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" id="addItem">
                        <i class="bi bi-plus-lg me-1"></i> Agregar Platillo
                    </button>
                </div>

                <div id="itemsContainer" class="mb-3">
                    <div class="p-3 bg-light rounded-3 border item-row mb-2">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-7">
                                <label class="form-label small text-muted mb-1">Platillo / Bebida</label>
                                <select name="items[0][producto_id]" class="form-select producto-select" required>
                                    <option value="">Seleccionar platillo...</option>
                                    @foreach($productos as $p)
                                        <option value="{{ $p->id }}" data-precio="{{ $p->precio }}" data-stock="{{ (int) $p->disponible }}">
                                            {{ $p->nombre }} — Bs. {{ number_format($p->precio, 2) }} (quedan {{ (int) $p->disponible }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Cantidad</label>
                                <input type="number" name="items[0][cantidad]" class="form-control text-center fw-bold cantidad-input" min="1" value="1" required>
                            </div>
                            <div class="col-md-2 text-end pt-4">
                                <button type="button" class="btn btn-outline-secondary btn-remove w-100" disabled>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
let itemIndex = 1;

document.getElementById('addItem').addEventListener('click', function() {
    const firstRow = document.querySelector('.item-row');
    if (!firstRow) return;

    const clone = firstRow.cloneNode(true);
    const select = clone.querySelector('select');
    const input = clone.querySelector('.cantidad-input');
    const removeBtn = clone.querySelector('.btn-remove');

    select.name = `items[${itemIndex}][producto_id]`;
    select.value = '';

    input.name = `items[${itemIndex}][cantidad]`;
    input.value = '1';

    removeBtn.disabled = false;
    removeBtn.className = 'btn btn-outline-danger btn-remove w-100';

    document.getElementById('itemsContainer').appendChild(clone);
    itemIndex++;
});

document.getElementById('itemsContainer').addEventListener('click', function(e) {
    if (e.target.closest('.btn-remove')) {
        const itemRows = document.querySelectorAll('.item-row');
        if (itemRows.length > 1) {
            e.target.closest('.item-row').remove();
        }
    }
});

document.getElementById('itemsContainer').addEventListener('change', function(e) {
    if (!e.target.classList.contains('producto-select')) return;

    const fila = e.target.closest('.item-row');
    const input = fila.querySelector('.cantidad-input');

    aplicarMaximo(e.target, input);
});

const metodoPago = document.getElementById('metodo_pago');
const campoEfectivo = document.getElementById('campoEfectivo');
const montoRecibido = document.getElementById('monto_recibido');

function sincronizarEfectivo() {
    const esEfectivo = metodoPago.value === 'efectivo';
    campoEfectivo.style.display = esEfectivo ? 'block' : 'none';
    montoRecibido.disabled = !esEfectivo;
}

metodoPago.addEventListener('change', sincronizarEfectivo);
sincronizarEfectivo();

function aplicarMaximo(select, input) {
    const opcion = select.options[select.selectedIndex];
    const disponible = opcion && opcion.dataset.stock !== undefined
        ? parseInt(opcion.dataset.stock, 10)
        : null;

    if (disponible === null || disponible < 1) {
        input.removeAttribute('max');
        return;
    }

    input.max = disponible;
    if (parseInt(input.value || '1', 10) > disponible) {
        input.value = disponible;
    }
}

aplicarMaximo(
    document.querySelector('.item-row select'),
    document.querySelector('.item-row .cantidad-input')
);
</script>
@endpush
