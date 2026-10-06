<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $empresa->nombre }} · VisiOptica</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container text-center" style="max-width: 520px;">
        <i class="bi bi-eyeglasses display-3 text-secondary"></i>
        <h1 class="h3 fw-semibold mt-3">{{ $empresa->nombre }}</h1>
        <p class="text-muted mt-3">
            Esta página no está disponible en este momento.
            Si necesitas una cita, comunícate directamente con la óptica
            @if ($empresa->telefono)
                al <strong>{{ $empresa->telefono }}</strong>
            @endif
            @if ($empresa->email)
                o escribe a <strong>{{ $empresa->email }}</strong>
            @endif.
        </p>
    </div>
</body>
</html>
