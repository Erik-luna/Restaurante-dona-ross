@extends(auth()->check() && auth()->user()->esCliente() ? 'layouts.app' : 'layouts.admin')
@section('title', 'Comprobante de Pedido #'.$pedido->id)

@section('content')
<style>
@media print {
    body { background: #fff !important; color: #000 !important; }
    .no-print, header, nav, footer, .navbar, .sidebar, .btn, .main-footer, .alert { display: none !important; }
    .card-ross { border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; }
    .container { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
    .badge { border: 1px solid #000 !important; color: #000 !important; }
}
</style>

<div class="container py-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
        <div>
            <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Comprobante de Pedido #{{ $pedido->id }}</h2>
            <p class="text-muted small mb-0">Emitido el {{ $pedido->created_at->format('d/m/Y \a \l\a\s H:i') }} hrs</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-printer me-1"></i> Imprimir comprobante
            </button>
            @if(auth()->user()->esCliente())
                <a href="{{ route('cliente.pedidos.show', $pedido) }}" class="btn btn-ross-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Detalle
                </a>
            @else
                <a href="{{ route('pedidos.show', $pedido) }}" class="btn btn-ross-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Volver a Pedidos
                </a>
            @endif
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-ross p-4 p-md-5 shadow-lg" style="border-top: 5px solid var(--ross-orange) !important;">
                <div class="text-center pb-4 mb-4 border-bottom">
                    <img src="{{ asset('img/logo.jpeg') }}" alt="Logo" class="brand-logo-img mb-2" style="width: 50px; height: 50px;">
                    <h4 class="fw-bold font-serif mb-0" style="color: var(--ross-primary);">Doña Ross Restaurant</h4>
                    <p class="text-muted small mb-1">Sabor Tradicional & Especialidades</p>
                    @if(!empty($contenido['direccion']) || !empty($contenido['telefono']))
                        <p class="text-muted small mb-1">
                            @if(!empty($contenido['direccion'])) {{ $contenido['direccion'] }} @endif
                            @if(!empty($contenido['telefono'])) &bull; Tel: {{ $contenido['telefono'] }} @endif
                        </p>
                    @endif
                    <span class="badge badge-ross-gold text-dark px-3 py-1 fw-bold">COMPROBANTE DE PEDIDO #{{ $pedido->id }}</span>
                </div>

                <div class="row g-3 mb-4 small">
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Cliente:</span>
                        <strong class="fs-6 text-dark">{{ $pedido->user?->name ?? 'Cliente' }}</strong>
                        <div class="text-muted">{{ $pedido->user?->email ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <span class="text-muted d-block">Fecha y hora del pedido:</span>
                        <strong class="text-dark">{{ $pedido->created_at->format('d/m/Y H:i') }} hrs</strong>
                        <div class="text-muted">Estado: <span class="fw-semibold text-capitalize">{{ $pedido->estado }}</span></div>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Dirección de entrega:</span>
                        <strong class="text-dark">{{ $pedido->direccion_entrega ?: 'Entrega en salón / mostrador' }}</strong>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <span class="text-muted d-block">Teléfono de contacto:</span>
                        <strong class="text-dark">{{ $pedido->telefono_contacto ?: ($pedido->user?->telefono ?: 'No especificado') }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Método / Estado de pago:</span>
                        <strong class="text-dark">
                            @if($pedido->estaCobrado())
                                <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Pagado</span>
                                @if($pedido->metodo_pago) ({{ ucfirst($pedido->metodo_pago) }}) @endif
                            @else
                                <span class="text-warning-emphasis"><i class="bi bi-clock-history me-1"></i> Pendiente de cobro</span>
                                ({{ $pedido->metodo_pago ? ucfirst($pedido->metodo_pago) : 'Pago contra entrega / QR' }})
                            @endif
                        </strong>
                        @if($pedido->cambio !== null && (float) $pedido->cambio > 0)
                            <div class="text-muted">Cambio: Bs. {{ number_format((float) $pedido->cambio, 2) }}</div>
                        @endif
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <span class="text-muted d-block">Tipo de pedido:</span>
                        <strong class="text-dark text-capitalize">{{ $pedido->tipo ?? 'online' }}</strong>
                        @if($pedido->atendidoPor)
                            <div class="text-muted">Atendido por: {{ $pedido->atendidoPor->name }}</div>
                        @endif
                    </div>
                    @if($pedido->notas)
                    <div class="col-12">
                        <div class="p-2 bg-light rounded-2 border">
                            <strong class="text-muted">Observaciones / Notas:</strong> {{ $pedido->notas }}
                        </div>
                    </div>
                    @endif
                </div>

                <div class="table-responsive mb-4">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0">Platillo / Ítem</th>
                                <th class="border-0 text-center">Cantidad</th>
                                <th class="border-0 text-end">Precio Unit.</th>
                                <th class="border-0 text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pedido->items as $item)
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark">{{ $item->producto->nombre }}</span>
                                </td>
                                <td class="text-center fw-bold">{{ $item->cantidad }}</td>
                                <td class="text-end text-muted">Bs. {{ number_format($item->precio_unitario, 2) }}</td>
                                <td class="text-end fw-bold" style="color: var(--ross-primary);">
                                    Bs. {{ number_format($item->subtotal, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end border-0 pt-3 fs-5 font-serif">Total del Pedido:</th>
                                <th class="text-end border-0 pt-3 fs-4 fw-bold" style="color: var(--ross-primary-dark);">
                                    Bs. {{ number_format($pedido->total, 2) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-center pt-3 border-top text-muted small">
                    <p class="mb-0">¡Gracias por tu preferencia! Doña Ross - Sabor Casero y Tradición.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
