@extends('layouts.admin')
@section('panel-title', 'Caja')
@section('sidebar')@include('partials.sidebar')@endsection
@section('title', 'Movimiento de Caja')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="mb-4">
            <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Movimiento de Efectivo</h2>
            <p class="text-muted small mb-0">
                Caja del {{ $caja->fecha->format('d/m/Y') }} &middot; dinero que no viene de una venta.
            </p>
        </div>

        <div class="table-card">
            <form method="POST" action="{{ route(auth()->user()->panel().'.caja.movimiento.store', $caja) }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold d-block">Tipo de movimiento</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo" id="tipo_entrada"
                                   value="entrada" {{ old('tipo', 'entrada') === 'entrada' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="tipo_entrada">
                                <span class="text-success fw-semibold">Entrada</span>
                                <span class="d-block small text-muted">Dinero que entra a la caja</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo" id="tipo_salida"
                                   value="salida" {{ old('tipo') === 'salida' ? 'checked' : '' }}>
                            <label class="form-check-label" for="tipo_salida">
                                <span class="text-danger fw-semibold">Salida</span>
                                <span class="d-block small text-muted">Retiro a bóveda o gasto del día</span>
                            </label>
                        </div>
                    </div>
                    @error('tipo')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="monto" class="form-label fw-semibold">Monto (Bs.)</label>
                    <input type="number" step="0.01" min="0.01"
                           class="form-control @error('monto') is-invalid @enderror"
                           id="monto" name="monto" value="{{ old('monto') }}" required>
                    @error('monto')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="concepto" class="form-label fw-semibold">Concepto</label>
                    <input type="text" maxlength="150"
                           class="form-control @error('concepto') is-invalid @enderror"
                           id="concepto" name="concepto" value="{{ old('concepto') }}"
                           placeholder="Ej.: retiro a bóveda por manque de insumos" required>
                    <div class="form-text">Queda registrado en el arqueo de la caja.</div>
                    @error('concepto')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-ross-primary">
                        <i class="bi bi-check-lg me-1"></i> Registrar Movimiento
                    </button>
                    <a href="{{ route(auth()->user()->panel().'.caja.show', $caja) }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>

        <div class="p-3 rounded-3 border bg-light mt-4 small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Usa movimientos cuando el dinero entra o sale de la caja por un motivo que
            <strong>no es una venta</strong>. Si no se registrara, el arqueo compararía
            el dinero contado contra las ventas y aparecería un descuadre falso.
        </div>
    </div>
</div>
@endsection