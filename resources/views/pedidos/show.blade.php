@extends('layouts.admin')
@section('panel-title', 'Administración')
@section('sidebar')@include('partials.sidebar')@endsection
@section('title', 'Detalle de Pedido #'.$pedido->id)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Pedido #{{ $pedido->id }}</h2>
        <p class="text-muted small mb-0">Registrado el {{ $pedido->created_at->format('d/m/Y \a \l\a\s H:i') }} hrs</p>
    </div>
    <a href="{{ route(auth()->user()->panel().'.pedidos.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Volver al listado
    </a>
</div>

<div class="row g-4">
    <!-- Order Items Column -->
    <div class="col-lg-8">
        <div class="card-ross p-4 mb-4">
            <h5 class="fw-bold mb-3 font-serif" style="color: var(--ross-primary);">
                <i class="bi bi-basket3 text-danger me-2"></i> Platillos Ordenados
            </h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">Platillo</th>
                            <th class="border-0 text-center">Cantidad</th>
                            <th class="border-0 text-end">Precio Unit.</th>
                            <th class="border-0 text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pedido->items as $item)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $item->producto->nombre }}</div>
                                <small class="text-muted">{{ $item->producto->categoria->nombre ?? 'Especialidad' }}</small>
                            </td>
                            <td class="text-center fw-bold">{{ $item->cantidad }}</td>
                            <td class="text-end text-muted">Bs. {{ number_format($item->precio_unitario, 2) }}</td>
                            <td class="text-end fw-bold" style="color: var(--ross-primary);">Bs. {{ number_format($item->subtotal, 2) }}</td>
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
        </div>
    </div>

    <!-- Client & Status Column -->
    <div class="col-lg-4">
        {{-- Estado: sólo el administrador y la cocina lo mueven. El cajero tiene
             acceso a esta misma vista de consulta, pero NO existe una ruta
             'cajero.pedidos.estado': por eso el formulario se esconde en lugar de
             apuntar a una ruta que no está definida. --}}
        @if(auth()->user()->esAdministrador() || auth()->user()->esCocina())
            <div class="card-ross p-4 mb-4" style="border-top: 5px solid var(--ross-orange) !important;">
                <h5 class="fw-bold mb-3 font-serif" style="color: var(--ross-primary);">
                    <i class="bi bi-arrow-repeat text-warning me-1"></i> Estado del Pedido
                </h5>

                <form action="{{ route(auth()->user()->panel().'.pedidos.estado', $pedido) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label small text-muted">Estado Actual:</label>
                        <select name="estado" class="form-select fw-bold">
                            @foreach(\App\Models\Pedido::ESTADOS as $e)
                                <option value="{{ $e }}" {{ $pedido->estado == $e ? 'selected' : '' }}>
                                    {{ ucfirst($e) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-ross-primary w-100 py-2">
                        <i class="bi bi-check2-circle me-1"></i> Actualizar Estado
                    </button>
                </form>
            </div>
        @endif

        {{-- Cobro: el dinero entra a la caja abierta del cajero o del
             administrador. La cocina no cobra, así que no ve esta tarjeta. --}}
        @if(! auth()->user()->esCocina())
            <div class="card-ross p-4 mb-4" style="border-top: 5px solid var(--ross-primary) !important;">
                <h5 class="fw-bold mb-3 font-serif" style="color: var(--ross-primary);">
                    <i class="bi bi-cash-coin text-danger me-1"></i> Cobro
                </h5>

                @if($pedido->estaCobrado())
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Estado:</span>
                        <span class="badge rounded-pill px-3 py-1 bg-success-subtle text-success border border-success">
                            Cobrado
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Método:</span>
                        <span class="fw-semibold text-capitalize">{{ $pedido->metodo_pago ?? '-' }}</span>
                    </div>
                    @if($pedido->monto_recibido !== null)
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Recibido:</span>
                            <span class="fw-semibold">Bs. {{ number_format((float) $pedido->monto_recibido, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Cambio:</span>
                            <span class="fw-semibold">Bs. {{ number_format((float) $pedido->cambio, 2) }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Caja:</span>
                        @if($pedido->caja)
                            <a href="{{ route(auth()->user()->panel().'.caja.show', $pedido->caja) }}"
                               class="fw-semibold text-danger text-decoration-none">#{{ $pedido->caja->id }}</a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Cobrado el:</span>
                        <span class="fw-semibold">{{ $pedido->pagado_at?->format('d/m/Y H:i') }}</span>
                    </div>
                @elseif($pedido->estado === \App\Models\Pedido::ESTADO_CANCELADO)
                    <p class="text-muted small mb-0">
                        <i class="bi bi-x-circle me-1"></i>
                        Este pedido está cancelado, así que no hay nada que cobrar.
                    </p>
                @else
                    <p class="text-muted small">
                        Este pedido todavía no se cobró. El importe de
                        <strong>Bs. {{ number_format((float) $pedido->total, 2) }}</strong>
                        sigue pendiente.
                    </p>
                    <form action="{{ route(auth()->user()->panel().'.caja.cobrar', $pedido) }}"
                          method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small text-muted" for="metodo_pago">Método de pago</label>
                            <select name="metodo_pago" id="metodo_pago" class="form-select">
                                <option value="efectivo" selected>Efectivo</option>
                                <option value="qr">QR / Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted" for="monto_recibido">Dinero recibido (Bs.)</label>
                            <input type="number" step="0.01" min="0" name="monto_recibido" id="monto_recibido"
                                   class="form-control" placeholder="Total exacto o más">
                            <div class="form-text">El cambio lo calcula el sistema.</div>
                        </div>
                        <button type="submit" class="btn btn-ross-primary w-100 py-2">
                            <i class="bi bi-check2-circle me-1"></i> Confirmar Cobro
                        </button>
                    </form>
                @endif
            </div>
        @endif

        <!-- Customer Card -->
        <div class="card-ross p-4">
            <h5 class="fw-bold mb-3 font-serif" style="color: var(--ross-primary);">
                <i class="bi bi-person-lines-fill text-danger me-1"></i> Datos del Cliente
            </h5>

            <ul class="list-unstyled mb-0 small">
                <li class="mb-2">
                    <strong class="text-muted d-block">Nombre:</strong>
                    <span class="fs-6 fw-bold text-dark">{{ $pedido->user->name }}</span>
                </li>
                <li class="mb-2">
                    <strong class="text-muted d-block">Email:</strong>
                    <span>{{ $pedido->user->email }}</span>
                </li>
                <li class="mb-2">
                    <strong class="text-muted d-block">Teléfono / WhatsApp:</strong>
                    <span class="fw-semibold text-dark">{{ $pedido->telefono_contacto ?: 'No especificado' }}</span>
                </li>
                <li class="mb-2">
                    <strong class="text-muted d-block">Dirección de Entrega:</strong>
                    <span class="text-dark">{{ $pedido->direccion_entrega ?: 'No especificada' }}</span>
                </li>
                @if($pedido->notas)
                <li class="p-2 bg-light rounded-2 border">
                    <strong class="text-muted d-block">Notas de Cocina:</strong>
                    <em>{{ $pedido->notas }}</em>
                </li>
                @endif
            </ul>
        </div>
    </div>
</div>
@endsection

