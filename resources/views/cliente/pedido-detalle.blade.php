@extends('layouts.app')

@section('title', 'Detalle de Pedido #' . $pedido->id)

@section('content')
<div class="container pb-5">
    <div class="mb-4">
        <a href="{{ route('cliente.pedidos') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver a Mis Pedidos
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-ross p-4 p-md-5 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                    <div>
                        <span class="fs-4 fw-bold font-serif" style="color: var(--ross-primary);">
                            Pedido #{{ $pedido->id }}
                        </span>
                        <span class="text-muted small d-block">
                            Registrado el {{ $pedido->created_at->format('d/m/Y \a \l\a\s H:i') }} hrs
                        </span>
                    </div>

                    @php
                        $statusBadges = [
                            'pendiente' => 'bg-warning-subtle text-warning-emphasis border border-warning',
                            'confirmado' => 'bg-info-subtle text-info-emphasis border border-info',
                            'preparando' => 'bg-primary-subtle text-primary border border-primary',
                            'enviado' => 'bg-purple-subtle text-primary border border-primary',
                            'entregado' => 'bg-success-subtle text-success border border-success',
                            'cancelado' => 'bg-danger-subtle text-danger border border-danger',
                        ];
                    @endphp
                    <span class="badge rounded-pill px-3 py-2 fw-semibold {{ $statusBadges[$pedido->estado] ?? 'bg-secondary' }}">
                        Estado: {{ ucfirst($pedido->estado) }}
                    </span>
                </div>

                <h5 class="fw-bold mb-3 font-serif" style="color: var(--ross-primary);">
                    <i class="bi bi-receipt-cutoff text-danger me-2"></i> Artículos Ordenados
                </h5>

                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Platillo</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio Unitario</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pedido->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->producto->nombre }}</div>
                                    <small class="text-muted">{{ Str::limit($item->producto->descripcion, 60) }}</small>
                                </td>
                                <td class="text-center fw-bold">{{ $item->cantidad }}</td>
                                <td class="text-end">Bs. {{ number_format($item->precio_unitario, 2) }}</td>
                                <td class="text-end fw-bold" style="color: var(--ross-primary-dark);">
                                    Bs. {{ number_format($item->subtotal, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="3" class="text-end fs-5">Total a Pagar:</th>
                                <th class="text-end fs-5 fw-bold" style="color: var(--ross-primary);">
                                    Bs. {{ number_format($pedido->total, 2) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($qrPagoUrl)
                <div class="card border-0 bg-light p-3 rounded-3 mb-4 text-center">
                    <h6 class="fw-bold mb-2 font-serif" style="color: var(--ross-primary);">
                        <i class="bi bi-qr-code text-danger me-1"></i> Paga por QR
                    </h6>
                    <div class="d-flex justify-content-center my-2">
                        <img src="{{ $qrPagoUrl }}" alt="Código QR de pago de Doña Ross" class="img-thumbnail rounded-3 shadow-sm" style="max-width: 180px; max-height: 180px; object-fit: contain; background: #fff;">
                    </div>
                    <p class="text-muted small mb-0">
                        Escanea este código desde la aplicación móvil de tu banco para pagar tu pedido.
                    </p>
                </div>
                @endif

                <h5 class="fw-bold mb-3 font-serif" style="color: var(--ross-primary);">
                    <i class="bi bi-geo-alt-fill text-danger me-2"></i> Información de Entrega y Observaciones
                </h5>

                <div class="bg-light p-3 rounded-3 mb-3">
                    <p class="mb-2">
                        <strong>Dirección de Entrega:</strong> {{ $pedido->direccion_entrega ?: 'Entrega en mostrador / local' }}
                    </p>
                    <p class="mb-2">
                        <strong>Teléfono de Contacto:</strong> {{ $pedido->telefono_contacto ?: 'No especificado' }}
                    </p>
                    @if($pedido->notas)
                    <p class="mb-0">
                        <strong>Notas para la Cocina:</strong> <em>{{ $pedido->notas }}</em>
                    </p>
                    @endif
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <span class="text-muted small">
                        <i class="bi bi-shield-lock-fill text-success"></i> Acceso seguro verificado mediante IDOR Protection
                    </span>
                    <a href="{{ route('cliente.pedidos') }}" class="btn btn-ross-primary btn-sm px-4">
                        Regresar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
