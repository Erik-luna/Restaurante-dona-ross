@extends('layouts.app')

@section('title', 'Mi Carrito de Compras')

@section('content')
<div class="container pb-5">
    <div class="section-header text-center mb-4">
        <span class="section-subtitle">Tu Selección</span>
        <h1 class="section-title">Carrito de Compras</h1>
        <div class="section-divider"></div>
    </div>

    @if(count($items))
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-ross p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                    <h5 class="fw-bold mb-0 font-serif" style="color: var(--ross-primary);">
                        <i class="bi bi-basket3-fill text-danger me-2"></i> Platillos Seleccionados ({{ count($items) }})
                    </h5>
                    <a href="{{ route('catalogo') }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 20px;">
                        <i class="bi bi-plus-lg"></i> Agregar más platos
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0">Platillo</th>
                                <th class="border-0">Precio</th>
                                <th class="border-0 text-center" style="width: 140px;">Cantidad</th>
                                <th class="border-0 text-end">Subtotal</th>
                                <th class="border-0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $item['producto']->imagen_url ?: asset('img/platos/plato1.jpeg') }}"
                                             onerror="this.onerror=null;this.src='{{ asset('img/platos/plato1.jpeg') }}';"
                                             class="rounded-3 shadow-sm" 
                                             alt="{{ $item['producto']->nombre }}" 
                                             style="width: 60px; height: 60px; object-fit: cover;">
                                        <div>
                                            <h6 class="fw-bold mb-0">{{ $item['producto']->nombre }}</h6>
                                            <small class="text-muted">{{ $item['producto']->categoria->nombre ?? 'Especialidad' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-semibold text-muted">
                                    Bs. {{ number_format($item['producto']->precio, 2) }}
                                </td>
                                <td>
                                    @php $disponible = $item['producto']->disponibleEn(); @endphp
                                    @if($disponible < 1)
                                        <span class="badge bg-danger-subtle text-danger">Agotado</span>
                                        <small class="d-block text-muted">Ya no hay porciones de este platillo; quítalo para continuar.</small>
                                    @else
                                        <form action="{{ route('cliente.carrito.update', $item['producto']) }}" method="POST" class="d-flex align-items-center justify-content-center gap-1">
                                            @csrf @method('PUT')
                                            <input type="number" name="cantidad" value="{{ $item['cantidad'] }}" min="1" max="{{ $disponible }}" class="form-control form-control-sm text-center fw-bold" style="width:65px; border-radius: 8px;">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Actualizar">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-end fw-bold fs-6" style="color: var(--ross-primary-dark);">
                                    Bs. {{ number_format($item['subtotal'], 2) }}
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('cliente.carrito.remove', $item['producto']) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle" title="Eliminar plato">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-ross p-4 position-sticky" style="top: 100px; border-top: 5px solid var(--ross-orange) !important;">
                <h4 class="fw-bold mb-3 font-serif" style="color: var(--ross-primary);">
                    Resumen de Pedido
                </h4>

                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Subtotal estimado:</span>
                    <span class="fw-semibold">Bs. {{ number_format($total, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Envío Delivery:</span>
                    <span class="text-success fw-bold">GRATIS</span>
                </div>
                <div class="d-flex justify-content-between py-3 mb-4">
                    <span class="fs-5 fw-bold font-serif">Total a Pagar:</span>
                    <span class="fs-4 fw-bold" style="color: var(--ross-primary-dark);">
                        Bs. {{ number_format($total, 2) }}
                    </span>
                </div>

                @php($restauranteAbierto = $horarioHoy !== null && $horarioHoy->estaAbierto())

                @if(! $restauranteAbierto)
                <div class="alert alert-warning border-warning-subtle rounded-3 mb-4" role="alert">
                    <i class="bi bi-clock-fill text-warning me-1"></i>
                    @if($horarioHoy)
                        <strong>El restaurante está cerrado.</strong>
                        Abrimos de {{ \Illuminate\Support\Carbon::parse($horarioHoy->apertura)->format('H:i') }}
                        a {{ \Illuminate\Support\Carbon::parse($horarioHoy->cierre)->format('H:i') }}.
                        Podrás enviar tu pedido dentro de nuestro horario de atención.
                    @else
                        <strong>El restaurante está cerrado hoy.</strong>
                        No hay horario de atención configurado para este día.
                    @endif
                </div>
                @endif

                <h5 class="fw-bold mb-3 font-serif" style="color: var(--ross-primary);">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> Datos de Entrega
                </h5>

                <form action="{{ route('cliente.checkout') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Dirección de entrega *</label>
                        <input type="text" name="direccion_entrega" class="form-control" value="{{ auth()->user()->direccion }}" placeholder="Ej: Av. Principal #456" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Teléfono de contacto *</label>
                        <input type="text" name="telefono_contacto" class="form-control" value="{{ auth()->user()->telefono }}" placeholder="Ej: 70000003" required>
                    </div>

                    <h5 class="fw-bold mb-3 mt-4 font-serif" style="color: var(--ross-primary);">
                        <i class="bi bi-credit-card-2-front-fill text-danger me-1"></i> Método de Pago *
                    </h5>

                    <div class="mb-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="card p-3 h-100 text-center rounded-3 cursor-pointer position-relative metodo-pago-card {{ old('metodo_pago', 'efectivo') === 'efectivo' ? 'border-primary shadow-sm bg-light' : 'border' }}" id="cardPagoEfectivo" style="cursor: pointer;">
                                    <input class="form-check-input position-absolute top-0 end-0 m-2" type="radio" name="metodo_pago" id="metodoEfectivo" value="efectivo" {{ old('metodo_pago', 'efectivo') === 'efectivo' ? 'checked' : '' }}>
                                    <i class="bi bi-cash-stack fs-2 text-success mb-1 d-block"></i>
                                    <strong class="d-block text-dark small">Efectivo</strong>
                                    <span class="text-muted" style="font-size: 0.72rem;">Contra entrega</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="card p-3 h-100 text-center rounded-3 cursor-pointer position-relative metodo-pago-card {{ old('metodo_pago') === 'qr' ? 'border-primary shadow-sm bg-light' : 'border' }}" id="cardPagoQr" style="cursor: pointer;">
                                    <input class="form-check-input position-absolute top-0 end-0 m-2" type="radio" name="metodo_pago" id="metodoQr" value="qr" {{ old('metodo_pago') === 'qr' ? 'checked' : '' }}>
                                    <i class="bi bi-qr-code fs-2 text-danger mb-1 d-block"></i>
                                    <strong class="d-block text-dark small">Pago por QR</strong>
                                    <span class="text-muted" style="font-size: 0.72rem;">Transferencia</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Información dinámica según el método de pago --}}
                    <div id="infoPagoEfectivo" class="alert alert-light border rounded-3 p-3 mb-3 small {{ old('metodo_pago', 'efectivo') === 'efectivo' ? '' : 'd-none' }}">
                        <i class="bi bi-info-circle-fill text-success me-1"></i>
                        Pagas en <strong>efectivo (Bs. {{ number_format($total, 2) }})</strong> al repartidor al momento de recibir tus platillos.
                    </div>

                    <div id="infoPagoQr" class="card border rounded-3 p-3 mb-3 text-center bg-white shadow-sm {{ old('metodo_pago') === 'qr' ? '' : 'd-none' }}">
                        <span class="badge badge-ross-gold text-dark px-3 py-1 fw-bold mx-auto mb-2" style="font-size: 0.75rem;">
                            <i class="bi bi-qr-code me-1 text-danger"></i> QR Oficial Doña Ross
                        </span>
                        @if($qrPagoUrl)
                            <div class="d-flex justify-content-center my-2">
                                <img src="{{ $qrPagoUrl }}" alt="Código QR de Pago" class="img-thumbnail rounded-3 shadow-sm" style="width: 150px; height: 150px; object-fit: contain; background: #fff;">
                            </div>
                        @else
                            <div class="p-3 my-2 bg-light rounded-3 border border-dashed text-muted small">
                                <i class="bi bi-qr-code fs-1 d-block text-danger mb-1"></i>
                                Código QR disponible en comprobante y pantalla de confirmación.
                            </div>
                        @endif
                        <p class="text-muted small mb-0" style="font-size: 0.8rem;">
                            Monto a transferir: <strong class="text-dark">Bs. {{ number_format($total, 2) }}</strong>. Podrás ver el comprobante una vez confirmado tu pedido.
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Notas para la cocina o entrega</label>
                        <textarea name="notas" class="form-control" rows="2" placeholder="Ej: Sin cebolla, salsa extra, timbre al llegar...">{{ old('notas') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-ross-primary btn-lg w-100 py-3" @disabled(! $restauranteAbierto)>
                        <i class="bi bi-bag-check-fill fs-5"></i> Confirmar y Pedir Ahora
                    </button>

                    @if(! $restauranteAbierto)
                    <p class="text-muted small mt-2 mb-0 text-center">
                        El botón se reactiva cuando abramos.
                    </p>
                    @endif
                </form>
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-5">
        <div class="card-ross p-5 mx-auto" style="max-width: 480px;">
            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; background: var(--ross-orange-light); color: var(--ross-orange);">
                <i class="bi bi-cart-x fs-1"></i>
            </div>
            <h3 class="fw-bold font-serif mb-2" style="color: var(--ross-primary);">Tu carrito está vacío</h3>
            <p class="text-muted small mb-4">Aún no has agregado deliciosos platos a tu pedido. ¡Explora nuestra carta y date un gusto!</p>
            <a href="{{ route('catalogo') }}" class="btn btn-ross-primary btn-lg px-4">
                <i class="bi bi-egg-fried me-1"></i> Ver Catálogo de Platos
            </a>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function() {
    function initMetodoPagoCarrito() {
        const radios = document.querySelectorAll('input[name="metodo_pago"]');
        const infoEfectivo = document.getElementById('infoPagoEfectivo');
        const infoQr = document.getElementById('infoPagoQr');
        const cardEfectivo = document.getElementById('cardPagoEfectivo');
        const cardQr = document.getElementById('cardPagoQr');

        if (!radios.length) return;

        function actualizarSeleccion(val) {
            const esQr = val === 'qr';
            if (infoEfectivo) infoEfectivo.classList.toggle('d-none', esQr);
            if (infoQr) infoQr.classList.toggle('d-none', !esQr);

            if (cardEfectivo) {
                cardEfectivo.classList.toggle('border-primary', !esQr);
                cardEfectivo.classList.toggle('shadow-sm', !esQr);
                cardEfectivo.classList.toggle('bg-light', !esQr);
            }
            if (cardQr) {
                cardQr.classList.toggle('border-primary', esQr);
                cardQr.classList.toggle('shadow-sm', esQr);
                cardQr.classList.toggle('bg-light', esQr);
            }
        }

        radios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                actualizarSeleccion(this.value);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMetodoPagoCarrito);
    } else {
        initMetodoPagoCarrito();
    }
    window.addEventListener('spa:navigated', initMetodoPagoCarrito);
})();
</script>
@endpush
