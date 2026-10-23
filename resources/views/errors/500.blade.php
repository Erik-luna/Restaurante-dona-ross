<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error interno del servidor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <main class="container py-5" style="max-width: 640px;">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 text-center">
                <p class="display-6 mb-2">🍲</p>
                <h1 class="h4 mb-3">Algo se quemó en la cocina</h1>
                <p class="text-muted mb-4">
                    Ocurrió un error inesperado al procesar tu solicitud. El equipo técnico
                    de Doña Ross ya fue notificado. Vuelve a intentarlo en unos minutos.
                </p>
                <a href="{{ url('/') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    Volver al inicio
                </a>
            </div>
        </div>
    </main>
</body>
</html>
