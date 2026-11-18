@extends('layouts.app')

@section('title', 'Sabor Casero y Tradición')

@section('content')
<div class="container pb-5">

    <div class="card border-0 rounded-4 overflow-hidden mb-5 shadow-lg position-relative" style="background: linear-gradient(135deg, rgba(30, 5, 6, 0.88) 0%, rgba(90, 11, 13, 0.75) 50%, rgba(255, 87, 34, 0.45) 100%), url('{{ asset('img/platos/plato1.jpeg') }}') center/cover; min-height: 480px;">
        <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-center text-white my-auto">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3" style="background: rgba(255, 179, 0, 0.2); border: 1px solid rgba(255, 179, 0, 0.5); backdrop-filter: blur(8px);">
                        <i class="bi bi-stars text-warning"></i>
                        <span class="text-warning fw-semibold small text-uppercase" style="letter-spacing: 1.5px;">El auténtico sabor casero</span>
                    </div>

                    <h1 class="display-4 fw-bold mb-3 text-white" style="line-height: 1.2;">
                        Tradición y Pasión <br>
                        <span style="color: var(--ross-gold); font-style: italic;">en Cada Platillo</span>
                    </h1>

                    <p class="lead mb-4 text-white-50 fs-5" style="max-width: 600px;">
                        Descubre las mejores recetas familiares preparadas con ingredientes frescos del día. Sabor inigualable que te hará sentir como en casa.
                    </p>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('catalogo') }}" class="btn btn-ross-primary btn-lg px-4">
                            <i class="bi bi-egg-fried fs-5"></i> Explorar Catálogo
                        </a>
                        <a href="{{ route('promociones') }}" class="btn btn-ross-gold btn-lg px-4">
                            <i class="bi bi-tag-fill fs-5"></i> Ver Promociones
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 d-none d-lg-block text-center">
                    <div class="p-4 rounded-4 text-start" style="background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.15);">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: var(--ross-orange);">
                                <i class="bi bi-bicycle text-white fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-white fw-bold">Delivery Rápido</h6>
                                <small class="text-white-50">Directo a tu mesa o domicilio</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: var(--ross-gold);">
                                <i class="bi bi-shield-check text-dark fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-white fw-bold">100% Calidad Casera</h6>
                                <small class="text-white-50">Recetas con sazón de hogar</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3 col-sm-6">
            <div class="card-ross h-100 p-4 text-center">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background-color: var(--ross-orange-light); color: var(--ross-orange);">
                    <i class="bi bi-fire fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Recetas Auténticas</h5>
                <p class="text-muted small mb-0">Preparadas con el toque tradicional y familiar que nos caracteriza.</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card-ross h-100 p-4 text-center">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background-color: #E8F8F0; color: #10B981;">
                    <i class="bi bi-patch-check-fill fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Ingredientes Frescos</h5>
                <p class="text-muted small mb-0">Seleccionamos los mejores insumos día a día para garantizar la excelencia.</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card-ross h-100 p-4 text-center">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background-color: var(--ross-gold-light); color: #B45309;">
                    <i class="bi bi-truck fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Delivery Confiable</h5>
                <p class="text-muted small mb-0">Llevamos tus platos favoritos calientes y listos para disfrutar.</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card-ross h-100 p-4 text-center">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background-color: #EBF5FF; color: #2563EB;">
                    <i class="bi bi-credit-card-2-front-fill fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Pedidos Online</h5>
                <p class="text-muted small mb-0">Haz tu pedido fácil y rápido desde tu computadora o teléfono celular.</p>
            </div>
        </div>
    </div>

    @if($destacados->count())
    <div class="section-header text-center">
        <span class="section-subtitle">Lo más pedido</span>
        <h2 class="section-title">Nuestras Especialidades</h2>
        <p class="text-muted small mb-0">Desliza para conocer nuestros platos tradicionales más aclamados</p>
        <div class="section-divider mt-3"></div>
    </div>

    <div class="carousel-ross-container position-relative mb-4" id="carouselDestacadosContainer">
        {{-- Botón Anterior --}}
        <button type="button" class="carousel-nav-btn carousel-nav-prev" id="carouselDestacadosPrev" aria-label="Plato anterior">
            <i class="bi bi-chevron-left fs-5"></i>
        </button>

        {{-- Pista deslizante del carrusel --}}
        <div class="carousel-ross-track" id="carouselDestacadosTrack" tabindex="0" role="region" aria-label="Carrusel de platos destacados">
            @foreach($destacados as $producto)
            <div class="carousel-ross-item">
                <div class="card-ross h-100 d-flex flex-column shadow-sm">
                    <div class="position-relative overflow-hidden" style="border-radius: var(--ross-radius) var(--ross-radius) 0 0;">
                        <img src="{{ $producto->imagen_url ?: asset('img/platos/plato1.jpeg') }}"
                             onerror="this.onerror=null;this.src='{{ asset('img/platos/plato1.jpeg') }}';"
                             class="w-100"
                             alt="{{ $producto->nombre }}"
                             loading="lazy"
                             style="height: 230px; object-fit: cover; transition: transform 0.4s ease;"
                             onmouseover="this.style.transform='scale(1.06)'"
                             onmouseout="this.style.transform='scale(1)'">
                        
                        <span class="position-absolute top-0 start-0 m-3 badge rounded-pill px-3 py-2 shadow-sm" style="background: var(--ross-orange); font-weight: 600;">
                            <i class="bi bi-star-fill me-1 text-warning"></i> Destacado
                        </span>

                        @if($producto->categoria)
                            <span class="position-absolute bottom-0 end-0 m-3 badge rounded-pill px-3 py-2 shadow-sm bg-dark bg-opacity-75 text-white">
                                {{ $producto->categoria->nombre }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="card-body p-4 d-flex flex-column flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h4 class="fw-bold mb-0 font-serif" style="color: var(--ross-primary);">{{ $producto->nombre }}</h4>
                        </div>
                        
                        <p class="text-muted small flex-grow-1 mb-3" style="line-height: 1.5;">
                            {{ Str::limit($producto->descripcion, 90) }}
                        </p>

                        <div class="d-flex align-items-center justify-content-between pt-3 border-top border-light-subtle">
                            <div>
                                <span class="text-muted small d-block">Precio</span>
                                <span class="fs-4 fw-bold" style="color: var(--ross-primary-dark);">
                                    Bs. {{ number_format($producto->precio, 2) }}
                                </span>
                            </div>

                            @auth
                                @if(auth()->user()->esCliente() && (int) $producto->disponible > 0)
                                    <form action="{{ route('cliente.carrito.add', $producto) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-ross-primary btn-sm px-3">
                                            <i class="bi bi-cart-plus-fill"></i> Pedir
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('catalogo') }}" class="btn btn-ross-outline btn-sm">
                                        Ver menú
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('catalogo') }}" class="btn btn-ross-outline btn-sm">
                                    Ver detalle
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Botón Siguiente --}}
        <button type="button" class="carousel-nav-btn carousel-nav-next" id="carouselDestacadosNext" aria-label="Siguiente plato">
            <i class="bi bi-chevron-right fs-5"></i>
        </button>

        {{-- Indicadores / Dots --}}
        <div class="carousel-indicators-ross" id="carouselDestacadosDots"></div>
    </div>

    <div class="text-center mb-5">
        <a href="{{ route('catalogo') }}" class="btn btn-ross-secondary btn-lg px-5">
            <i class="bi bi-grid-3x3-gap-fill me-2 text-warning"></i> Ver Todo el Menú y Carta
        </a>
    </div>
    @endif

    <div class="card border-0 rounded-4 overflow-hidden mb-5 text-white shadow" style="background: linear-gradient(135deg, var(--ross-primary) 0%, #4D090B 100%); border-left: 8px solid var(--ross-gold) !important;">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge badge-ross-gold text-dark mb-2 px-3 py-2 fw-bold">
                        <i class="bi bi-percent me-1"></i> AHORRA MÁS
                    </span>
                    <h2 class="display-6 fw-bold mb-2 font-serif text-white">¿Buscas descuentos especiales?</h2>
                    <p class="text-white-50 mb-4 mb-lg-0 fs-5">
                        Aprovecha nuestros combos familiares, promociones del día y descuentos por compras online.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('promociones') }}" class="btn btn-ross-gold btn-lg px-4 shadow">
                        <i class="bi bi-fire me-1"></i> Ver Promociones
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($promociones->isNotEmpty())
    <div class="row g-4 mb-5">
        @foreach($promociones as $promo)
        <div class="col-md-6">
            <div class="card-ross h-100 p-4 position-relative" style="border-left: 6px solid var(--ross-orange) !important;">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    @if($promo->descuento_porcentaje)
                        <span class="badge rounded-pill px-3 py-2 fw-bold" style="background: linear-gradient(135deg, var(--ross-orange) 0%, #E64A19 100%); color: white; font-size: 0.85rem;">
                            <i class="bi bi-tag-fill me-1"></i> {{ (int)$promo->descuento_porcentaje }}% OFF
                        </span>
                    @else
                        <span class="badge rounded-pill px-3 py-2 fw-bold" style="background: var(--ross-gold); color: #5A0B0D; font-size: 0.85rem;">
                            <i class="bi bi-stars me-1"></i> Oferta Especial
                        </span>
                    @endif

                    @if($promo->fecha_fin)
                        <span class="badge bg-light text-muted border">
                            <i class="bi bi-calendar3 me-1"></i> Hasta {{ $promo->fecha_fin->format('d/m/Y') }}
                        </span>
                    @endif
                </div>

                <h4 class="fw-bold mb-2 font-serif" style="color: var(--ross-primary);">{{ $promo->titulo }}</h4>
                <p class="text-muted small mb-3" style="line-height: 1.6;">{{ $promo->descripcion }}</p>

                <a href="{{ route('promociones') }}" class="btn btn-ross-outline btn-sm">
                    <i class="bi bi-fire me-1"></i> Ver Promoción
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="row g-4">
        <div class="{{ $qrPagoUrl ? 'col-md-6 col-lg-3' : 'col-md-4' }}">
            <div class="card-ross h-100 p-4 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background-color: var(--ross-orange-light); color: var(--ross-orange);">
                    <i class="bi bi-clock-history fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1 font-serif">Horario de Atención</h5>
                    <p class="text-muted small mb-1">
                        {{ $horarioHoy?->nombreDia() }}: {{ $horarioHoy?->rango() ?? 'no configurado' }}
                    </p>
                    @if($horarioHoy?->estaAbierto())
                        <span class="badge bg-success-subtle text-success small">Abierto ahora</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary small">Cerrado ahora</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="{{ $qrPagoUrl ? 'col-md-6 col-lg-3' : 'col-md-4' }}">
            <div class="card-ross h-100 p-4 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background-color: var(--ross-gold-light); color: #B45309;">
                    <i class="bi bi-geo-alt-fill fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1 font-serif">Nuestra Ubicación</h5>
                    <p class="text-muted small mb-0">{{ $contenido['direccion'] }}</p>
                    <span class="text-warning small fw-bold">Fácil acceso y parqueo</span>
                </div>
            </div>
        </div>

        <div class="{{ $qrPagoUrl ? 'col-md-6 col-lg-3' : 'col-md-4' }}">
            <div class="card-ross h-100 p-4 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background-color: #E8F8F0; color: #10B981;">
                    <i class="bi bi-whatsapp fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1 font-serif">Pedidos Directos</h5>
                    <p class="text-muted small mb-0">{{ $contenido['telefono'] }}</p>
                    @if($whatsappEnlace)
                        <a href="{{ $whatsappEnlace }}" target="_blank" rel="noopener" class="text-success small fw-bold text-decoration-none">Escribir por WhatsApp &rarr;</a>
                    @endif
                </div>
            </div>
        </div>

        @if($qrPagoUrl)
        <div class="col-md-6 col-lg-3">
            <div class="card-ross h-100 p-4 d-flex flex-row align-items-center gap-3">
                <img src="{{ $qrPagoUrl }}" alt="QR de Pago" class="rounded border shadow-sm flex-shrink-0" style="width: 56px; height: 56px; object-fit: contain; background: #fff;">
                <div>
                    <h5 class="fw-bold mb-1 font-serif">Paga por QR</h5>
                    <p class="text-muted small mb-0">Escanea nuestro código para transferencias rápidas.</p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .carousel-ross-container {
        position: relative;
        padding: 0 4px;
    }
    .carousel-ross-track {
        display: flex;
        gap: 1.5rem;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding: 10px 4px 18px 4px;
        outline: none;
    }
    .carousel-ross-track::-webkit-scrollbar {
        display: none;
    }
    .carousel-ross-item {
        scroll-snap-align: start;
        flex: 0 0 100%;
        max-width: 100%;
        transition: transform 0.25s ease;
    }
    @media (min-width: 768px) {
        .carousel-ross-item {
            flex: 0 0 calc((100% - 1.5rem) / 2);
            max-width: calc((100% - 1.5rem) / 2);
        }
    }
    @media (min-width: 992px) {
        .carousel-ross-item {
            flex: 0 0 calc((100% - 3rem) / 3);
            max-width: calc((100% - 3rem) / 3);
        }
    }
    .carousel-nav-btn {
        position: absolute;
        top: 42%;
        transform: translateY(-50%);
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background-color: #FFFFFF;
        color: var(--ross-primary);
        border: 2px solid var(--ross-border);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.14);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .carousel-nav-btn:hover {
        background-color: var(--ross-primary);
        color: var(--ross-gold);
        border-color: var(--ross-primary);
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 8px 22px rgba(122, 17, 19, 0.3);
    }
    .carousel-nav-btn:focus-visible {
        outline: 3px solid var(--ross-orange);
    }
    .carousel-nav-prev {
        left: -18px;
    }
    .carousel-nav-next {
        right: -18px;
    }
    @media (max-width: 767.98px) {
        .carousel-nav-prev { left: 2px; }
        .carousel-nav-next { right: 2px; }
        .carousel-nav-btn {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(4px);
        }
    }
    .carousel-indicators-ross {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 0.75rem;
        margin-bottom: 2rem;
    }
    .carousel-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #D8D2C6;
        border: none;
        padding: 0;
        cursor: pointer;
        transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .carousel-dot:hover {
        background-color: var(--ross-orange);
        transform: scale(1.15);
    }
    .carousel-dot.active {
        width: 32px;
        border-radius: 8px;
        background: linear-gradient(90deg, var(--ross-primary) 0%, var(--ross-orange) 100%);
        box-shadow: 0 2px 8px rgba(122, 17, 19, 0.28);
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    function initCarouselDestacados() {
        const track = document.getElementById('carouselDestacadosTrack');
        const prevBtn = document.getElementById('carouselDestacadosPrev');
        const nextBtn = document.getElementById('carouselDestacadosNext');
        const dotsContainer = document.getElementById('carouselDestacadosDots');
        const container = document.getElementById('carouselDestacadosContainer');

        if (!track || !prevBtn || !nextBtn || !dotsContainer || !container) return;

        const items = track.querySelectorAll('.carousel-ross-item');
        if (items.length === 0) return;

        function getVisibleCount() {
            if (window.innerWidth >= 992) return 3;
            if (window.innerWidth >= 768) return 2;
            return 1;
        }

        let visible = getVisibleCount();
        let totalPages = Math.ceil(items.length / visible);
        let currentPage = 0;
        let isProgrammaticScroll = false;

        function renderDots() {
            visible = getVisibleCount();
            totalPages = Math.max(1, Math.ceil(items.length / visible));
            dotsContainer.innerHTML = '';

            for (let i = 0; i < totalPages; i++) {
                const dot = document.createElement('button');
                dot.className = 'carousel-dot' + (i === currentPage ? ' active' : '');
                dot.setAttribute('type', 'button');
                dot.setAttribute('aria-label', 'Ver grupo de platos ' + (i + 1) + ' de ' + totalPages);
                dot.addEventListener('click', function () {
                    goToPage(i);
                });
                dotsContainer.appendChild(dot);
            }
        }

        function updateDotsUI(pageIndex) {
            const dots = dotsContainer.querySelectorAll('.carousel-dot');
            dots.forEach(function (dot, idx) {
                dot.classList.toggle('active', idx === pageIndex);
            });
        }

        function goToPage(pageIdx) {
            if (pageIdx < 0) pageIdx = totalPages - 1;
            if (pageIdx >= totalPages) pageIdx = 0;
            currentPage = pageIdx;
            isProgrammaticScroll = true;

            const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
            let targetLeft = 0;

            if (pageIdx === totalPages - 1) {
                targetLeft = maxScroll;
            } else {
                const targetItem = items[pageIdx * visible];
                if (targetItem) {
                    targetLeft = Math.min(targetItem.offsetLeft - track.offsetLeft, maxScroll);
                }
            }

            track.scrollTo({ left: targetLeft, behavior: 'smooth' });
            updateDotsUI(currentPage);

            setTimeout(function () {
                isProgrammaticScroll = false;
            }, 450);
        }

        function slideNext() {
            const next = (currentPage + 1) >= totalPages ? 0 : currentPage + 1;
            goToPage(next);
        }

        function slidePrev() {
            const prev = (currentPage - 1) < 0 ? (totalPages - 1) : currentPage - 1;
            goToPage(prev);
        }

        nextBtn.onclick = function (e) {
            e.preventDefault();
            slideNext();
        };

        prevBtn.onclick = function (e) {
            e.preventDefault();
            slidePrev();
        };

        track.addEventListener('scroll', function () {
            if (isProgrammaticScroll) return;
            const scrollLeft = track.scrollLeft;
            const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
            let active = 0;

            if (scrollLeft >= maxScroll - 15) {
                active = totalPages - 1;
            } else {
                const step = track.clientWidth;
                active = Math.min(totalPages - 1, Math.max(0, Math.round(scrollLeft / step)));
            }

            if (active !== currentPage) {
                currentPage = active;
                updateDotsUI(currentPage);
            }
        }, { passive: true });

        // Teclado
        track.onkeydown = function (e) {
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                slideNext();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                slidePrev();
            }
        };

        // Autoplay fluido cada 4.5 segundos
        let autoplayTimer = null;
        function startAutoplay() {
            stopAutoplay();
            if (totalPages > 1) {
                autoplayTimer = setInterval(slideNext, 4500);
            }
        }
        function stopAutoplay() {
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        }

        container.onmouseenter = stopAutoplay;
        container.onmouseleave = startAutoplay;
        container.ontouchstart = stopAutoplay;
        container.ontouchend = function () {
            setTimeout(startAutoplay, 2000);
        };

        let resizeTimer = null;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                renderDots();
                goToPage(Math.min(currentPage, totalPages - 1));
            }, 150);
        });

        renderDots();
        startAutoplay();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCarouselDestacados);
    } else {
        initCarouselDestacados();
    }
})();
</script>
@endpush
