@if ($paginator->hasPages())
    <style>
        .pagination-ross {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 10px;
            background: #ffffff;
            border-radius: 9999px;
            border: 1px solid var(--ross-border, #EAE3D9);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            margin: 0 auto;
        }
        .pagination-ross .page-item {
            display: inline-flex;
            margin: 0;
        }
        .pagination-ross .page-link {
            border: none;
            background: transparent;
            color: var(--ross-primary, #7A1113);
            font-weight: 600;
            font-size: 0.9rem;
            min-width: 36px;
            height: 36px;
            padding: 0 12px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
        }
        .pagination-ross .page-link:hover {
            background-color: var(--ross-orange-light, #FFEDE6);
            color: var(--ross-orange, #FF5722);
            transform: translateY(-1px);
        }
        .pagination-ross .page-item.active .page-link {
            background: linear-gradient(135deg, var(--ross-primary, #7A1113) 0%, var(--ross-orange, #FF5722) 100%);
            color: #ffffff;
            box-shadow: 0 3px 8px rgba(122, 17, 19, 0.28);
            font-weight: 700;
        }
        .pagination-ross .page-item.disabled .page-link {
            color: #9CA3AF;
            background: transparent;
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .pagination-ross .page-link-dots {
            min-width: 24px;
            padding: 0 4px;
            color: #9CA3AF;
        }
    </style>
    <nav class="d-flex justify-content-center align-items-center my-4" aria-label="Navegación de páginas">
        <ul class="pagination pagination-ross mb-0">
            {{-- Botón Anterior --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="Página anterior">
                    <span class="page-link" aria-hidden="true">
                        <i class="bi bi-chevron-left me-1"></i>
                        <span class="d-none d-sm-inline">Anterior</span>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior">
                        <i class="bi bi-chevron-left me-1"></i>
                        <span class="d-none d-sm-inline">Anterior</span>
                    </a>
                </li>
            @endif

            {{-- Elementos de Paginación --}}
            @foreach ($elements as $element)
                {{-- Separador "..." --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link page-link-dots">{{ $element }}</span>
                    </li>
                @endif

                {{-- Números de Página --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Botón Siguiente --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente">
                        <span class="d-none d-sm-inline me-1">Siguiente</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="Página siguiente">
                    <span class="page-link" aria-hidden="true">
                        <span class="d-none d-sm-inline me-1">Siguiente</span>
                        <i class="bi bi-chevron-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
