@extends('layouts.app')

@section('title', 'Delivery a Domicilio')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card-ross p-4 p-md-5 shadow-lg" style="border-top: 5px solid var(--ross-orange) !important;">
                <div class="text-center mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 65px; height: 65px; background: var(--ross-orange-light); color: var(--ross-orange);">
                        <i class="bi bi-bicycle fs-2"></i>
                    </div>
                    <h2 class="fw-bold font-serif mb-1" style="color: var(--ross-primary);">Delivery a Domicilio</h2>
                    <p class="text-muted small mb-0">Disfruta los platillos de Doña Ross sin salir de casa.</p>
                </div>

                <div class="mb-4">
                    <p>
                        Los pedidos a domicilio se toman desde nuestro catálogo online: así puedes elegir
                        varios platillos, aplicar cupones de promoción y seguir el estado de tu pedido
                        desde tu cuenta de cliente.
                    </p>
                    <ul class="list-unstyled small mb-0">
                        <li class="d-flex align-items-start mb-2">
                            <i class="bi bi-check-circle-fill me-2 mt-1" style="color: var(--ross-orange);"></i>
                            <span>Ingresa al catálogo y agrega tus platillos favoritos al carrito.</span>
                        </li>
                        <li class="d-flex align-items-start mb-2">
                            <i class="bi bi-check-circle-fill me-2 mt-1" style="color: var(--ross-orange);"></i>
                            <span>Regístrate o inicia sesión como cliente para confirmar tu pedido.</span>
                        </li>
                        <li class="d-flex align-items-start mb-2">
                            <i class="bi bi-check-circle-fill me-2 mt-1" style="color: var(--ross-orange);"></i>
                            <span>Indica la dirección exacta de entrega y elige tu método de pago.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill me-2 mt-1" style="color: var(--ross-orange);"></i>
                            <span>Sigue el estado de tu pedido hasta que llegue calientito a tu puerta.</span>
                        </li>
                    </ul>
                </div>

                <div class="d-grid">
                    <a href="{{ route('catalogo') }}" class="btn btn-ross-primary btn-lg py-3 shadow">
                        <i class="bi bi-basket-fill me-1"></i> Pedir ahora
                    </a>
                </div>

                <div class="text-center pt-3 border-top mt-4 text-muted small">
                    <p class="mb-0">
                        ¿Aún no tienes una cuenta?
                        <a href="{{ route('register') }}" class="text-danger fw-bold text-decoration-none">Regístrate aquí</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
