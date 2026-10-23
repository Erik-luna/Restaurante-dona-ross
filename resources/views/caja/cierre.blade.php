@extends('layouts.admin')
@section('panel-title', 'Caja')
@section('sidebar')@include('partials.sidebar')@endsection
@section('title', 'Cierre y Arqueo')

@section('content')
@php $resumen = $caja->resumen(); @endphp

<div class="mb-4">
    <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Cierre de Caja y Arqueo</h2>
    <p class="text-muted small mb-0">
        Cuenta el dinero del cajón y compáralo con lo que el sistema dice que debería haber.
    </p>
</div>

<div class="row g-4">

    <div class="col-lg-6">
        <div class="table-card">
            <h5 class="fw-bold font-serif mb-3" style="color: var(--ross-primary);">
                <i class="bi bi-calculator text-warning me-2"></i> Lo que dice el sistema
            </h5>

            <table class="table align-middle mb-0">
                <tbody>
                    <tr>
                        <td>Monto inicial</td>
                        <td class="text-end fw-semibold">Bs. {{ number_format((float) $caja->monto_inicial, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Ventas de salón</td>
                        <td class="text-end fw-semibold text-success">+ Bs. {{ number_format($resumen['ventas'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Cobros de pedidos delivery</td>
                        <td class="text-end fw-semibold text-success">+ Bs. {{ number_format($resumen['cobros'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Entradas de efectivo</td>
                        <td class="text-end fw-semibold text-success">+ Bs. {{ number_format($resumen['entradas'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Salidas de efectivo</td>
                        <td class="text-end fw-semibold text-danger">&minus; Bs. {{ number_format($resumen['salidas'], 2) }}</td>
                    </tr>
                    <tr class="table-light">
                        <td class="fw-bold">Debería haber en la caja</td>
                        <td class="text-end fw-bold" style="color: var(--ross-primary); font-size: 1.1rem;">
                            Bs. {{ number_format($resumen['esperado'], 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="p-3 rounded-3 border bg-light mt-4 small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            El monto contado <strong>solo tú lo decides</strong>: es una observación de
            lo que hay en el cajón, no un dato del sistema. Si no cuadra, el arqueo guarda
            la diferencia tal cual, sin corregirla.
        </div>
    </div>


    <div class="col-lg-6">
        <div class="table-card">
            <h5 class="fw-bold font-serif mb-3" style="color: var(--ross-primary);">
                <i class="bi bi-hand-index-thumb text-warning me-2"></i> Lo que contaste
            </h5>

            <form method="POST" action="{{ route(auth()->user()->panel().'.caja.cierre.store', $caja) }}">
                @csrf

                <div class="mb-3">
                    <label for="total_declarado" class="form-label fw-semibold">Dinero contado en el cajón (Bs.)</label>
                    <input type="number" step="0.01" min="0"
                           class="form-control form-control-lg @error('total_declarado') is-invalid @enderror"
                           id="total_declarado" name="total_declarado"
                           value="{{ old('total_declarado', number_format($resumen['esperado'], 2)) }}" required>
                    <div class="form-text">Conta el efectivo y anótalo tal cual, sin redondear.</div>
                    @error('total_declarado')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="observacion_cierre" class="form-label fw-semibold">
                        Observación <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <textarea class="form-control @error('observacion_cierre') is-invalid @enderror"
                              id="observacion_cierre" name="observacion_cierre" rows="3" maxlength="500"
                              placeholder="Ej.: falta un billete de 10 que no se encontró">{{ old('observacion_cierre') }}</textarea>
                    @error('observacion_cierre')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="p-3 rounded-3 border bg-warning-subtle mb-4 small">
                    <strong>Aviso:</strong> al cerrar la caja ya no se pueden registrar
                    ventas ni movimientos en ella. Si te equivocaste al contar, la caja
                    quedará cerrada con esa diferencia documentada.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-ross-primary">
                        <i class="bi bi-lock me-1"></i> Cerrar Caja
                    </button>
                    <a href="{{ route(auth()->user()->panel().'.caja.show', $caja) }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection