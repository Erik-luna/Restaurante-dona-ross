<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Doña Ross - Acceso Restringido (403)</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('img/logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --ross-primary: #7A1113;
            --ross-primary-dark: #5A0B0D;
            --ross-orange: #FF5722;
            --ross-orange-light: #FFEDE6;
            --ross-gold: #FFB300;
            --ross-bg: #FCFAF7;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--ross-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .font-serif {
            font-family: 'Playfair Display', Georgia, serif;
        }
        .card-error {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #EAE3D9;
            box-shadow: 0 10px 30px rgba(122, 17, 19, 0.08);
            max-width: 560px;
            width: 100%;
        }
        .btn-ross-primary {
            background: linear-gradient(135deg, var(--ross-orange) 0%, #E64A19 100%);
            color: #ffffff !important;
            border: none;
            border-radius: 9999px;
            font-weight: 600;
            transition: all 0.25s ease;
        }
        .btn-ross-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 87, 34, 0.35);
        }
        .btn-ross-outline {
            border: 2px solid var(--ross-primary);
            color: var(--ross-primary) !important;
            background: transparent;
            border-radius: 9999px;
            font-weight: 600;
            transition: all 0.25s ease;
        }
        .btn-ross-outline:hover {
            background: var(--ross-primary);
            color: #ffffff !important;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card-error mx-auto p-4 p-md-5 text-center">
            <div class="mb-3">
                <img src="{{ asset('img/logo.jpeg') }}" alt="Doña Ross" class="rounded-circle shadow-sm border border-2 border-warning" style="width: 72px; height: 72px; object-fit: cover;">
            </div>
            <span class="display-3 fw-bold font-serif d-block text-danger" style="line-height: 1;">403</span>
            <div class="mx-auto rounded-circle d-flex align-items-center justify-content-center my-3" style="width: 60px; height: 60px; background: #FEE2E2; color: #DC2626;">
                <i class="bi bi-shield-lock-fill fs-2"></i>
            </div>
            <h2 class="h4 fw-bold font-serif mb-2" style="color: var(--ross-primary);">
                Acceso Restringido
            </h2>
            <p class="text-muted small mb-4" style="line-height: 1.6;">
                No dispones de los permisos necesarios para acceder a esta área o la sección pertenece a otro rol del sistema.
            </p>
            <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                @auth
                    <a href="{{ route(auth()->user()->panel().'.dashboard') }}" class="btn btn-ross-primary px-4 py-2">
                        <i class="bi bi-speedometer2 me-1"></i> Volver a Mi Panel
                    </a>
                @else
                    <a href="{{ url('/') }}" class="btn btn-ross-primary px-4 py-2">
                        <i class="bi bi-house-door-fill me-1"></i> Volver al Inicio
                    </a>
                @endauth
                <a href="{{ url('/catalogo') }}" class="btn btn-ross-outline px-4 py-2">
                    <i class="bi bi-egg-fried me-1"></i> Ver Catálogo
                </a>
            </div>
        </div>
    </div>
</body>
</html>
