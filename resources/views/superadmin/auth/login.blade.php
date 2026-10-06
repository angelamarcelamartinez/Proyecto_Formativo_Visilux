<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Superadmin | VisiOptica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">

    <!-- Google Fonts y Bootstrap -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Tus estilos personalizados -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body class="login-page">

    <div class="login-bg"></div>

    <div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="card login-card border-0 shadow-lg w-100" style="max-width: 420px;">
            <div class="card-body p-4 p-md-5">

                <!-- LOGO Y ENCABEZADO -->
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}" class="text-decoration-none d-inline-block">
                        <img src="{{ asset('assets/img/logo-visilux.png') }}" alt="VisiOptica" class="login-logo img-fluid">
                    </a>
                    <div class="mt-3">
                        <span class="badge rounded-pill px-3 py-2 text-uppercase fw-semibold" style="background:#2E2A22;letter-spacing:.08em;">
                            <i class="bi bi-shield-lock-fill me-1"></i> Superadmin
                        </span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        Acceso exclusivo para el administrador de VisiOptica
                    </p>
                </div>

                <!-- MENSAJES DE ERROR -->
                @if (session('status'))
                    <div class="alert alert-success py-2 small" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- FORMULARIO -->
                <form method="POST" action="{{ route('superadmin.login.attempt') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-envelope text-muted"></i>
                            </span>
                            <input type="email" 
                                   class="form-control border-start-0 login-input @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   placeholder="superadmin@correo.com" 
                                   required 
                                   autofocus>
                        </div>
                        @error('email')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-medium">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-lock text-muted"></i>
                            </span>
                            <input type="password" 
                                   class="form-control border-start-0 login-input @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   placeholder="••••••••" 
                                   required>
                        </div>
                        @error('password')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label small" for="remember">Recordarme</label>
                        </div>

                        <a href="{{ route('password.request', ['origen' => 'superadmin']) }}" class="small link-teal text-decoration-none">
                            ¿Olvidaste tu contraseña?
                        </a>

                    </div>

                    <button type="submit" class="btn btn-gradient w-100 rounded-pill py-2 fw-semibold mb-3">
                        Entrar como superadmin
                    </button>

                    <a href="{{ url('/') }}" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-semibold text-decoration-none text-center">
                        &larr; Volver al menú
                    </a>
                </form>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>