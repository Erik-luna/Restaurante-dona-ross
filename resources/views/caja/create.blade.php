@extends('layouts.admin')
@section('panel-title', 'Caja')
@section('sidebar')@include('partials.sidebar')@endsection
@section('title', 'Abrir Caja')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="mb-4">
            <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Apertura de Caja</h2>
            <p class="text-muted small mb-0">
                Registra con cuánto dinero arranca tu turno. Ese monto es la base del
                arqueo: al cerrar la caja el sistema comparará el dinero contado con
                lo que debería haber.
            </p>
        </div>

        <div class="table-card">
            <form method="POST" action="{{ route(auth()->user()->panel().'.caja.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="monto_inicial" class="form-label fw-semibold">Monto inicial (Bs.)</label>
                    <input type="number" step="0.01" min="0"
                           class="form-control @error('monto_inicial') is-invalid @enderror"
                           id="monto_inicial" name="monto_inicial" value="{{ old('monto_inicial', '0.00') }}" required>
                    @error('monto_inicial')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="observacion_apertura" class="form-label fw-semibold">
                        Observación <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <textarea class="form-control @error('observacion_apertura') is-invalid @enderror"
                              id="observacion_apertura" name="observacion_apertura" rows="3"
                              maxlength="500" placeholder="Ej.: se abre con el cambio de la mañana.">{{ old('observacion_apertura') }}</textarea>
                    <div class="form-text">Queda guardado en el registro del turno.</div>
                    @error('observacion_apertura')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-ross-primary">
                        <i class="bi bi-unlock me-1"></i> Abrir Caja
                    </button>
                    <a href="{{ route(auth()->user()->panel().'.caja.index') }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>

        <div class="p-3 rounded-3 border bg-light mt-4 small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Se puede abrir <strong>una sola caja por día</strong>. Al cerrarla no se
            pueden registrar más ventas ni movimientos en ella: para el siguiente
            turno se abre una caja nueva.
        </div>
    </div>
</div>
@endsection