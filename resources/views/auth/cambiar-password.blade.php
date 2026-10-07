<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea tu contraseña | VisiOptica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-bg"></div>

    <div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="card login-card border-0 shadow-lg w-100" style="max-width: 420px;">
            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <img src="{{ asset('assets/img/logo-visilux.png') }}" alt="VisiOptica" class="login-logo img-fluid">
                    <h1 class="h5 fw-semibold mt-3 mb-1">Crea tu contraseña</h1>
                    <p class="text-muted small mb-0">
                        Entraste con una contraseña temporal. Por seguridad, elige una propia para continuar.
                    </p>
                </div>

                <form method="POST" action="{{ route('password.cambiar.guardar') }}" novalidate>
                    @csrf

                    @foreach ([
                        ['password_actual', 'Contraseña temporal (la del correo)', 'bi-key'],
                        ['password', 'Nueva contraseña (mínimo 8 caracteres)', 'bi-lock'],
                        ['password_confirmation', 'Repite la nueva contraseña', 'bi-lock-fill'],
                    ] as [$campo, $etiqueta, $icono])
                        <div class="mb-3">
                            <label for="{{ $campo }}" class="form-label fw-medium">{{ $etiqueta }}</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi {{ $icono }} text-muted"></i></span>
                                <input type="password" id="{{ $campo }}" name="{{ $campo }}" required
                                       @if ($loop->first) autofocus @endif
                                       class="form-control border-start-0 login-input @error($campo) is-invalid @enderror">
                            </div>
                            @error($campo)
                                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-gradient w-100 rounded-pill py-2 fw-semibold mb-3">
                        Guardar y entrar al panel
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="text-center">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm text-muted text-decoration-none">Cerrar sesión</button>
                </form>

            </div>
        </div>
    </div>
</body>
</html>
