<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | VisiOptica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">

    <!-- Google Fonts y Bootstrap -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Tus estilos personalizados -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Validación en línea del correo: borde verde si está registrado, rojo si no -->
    <style>
        .login-input.is-valid,
        .login-input.is-valid:focus { border-color: #198754; box-shadow: 0 0 0 .2rem rgba(25,135,84,.2); }
        .login-input.is-invalid,
        .login-input.is-invalid:focus { border-color: #dc3545; box-shadow: 0 0 0 .2rem rgba(220,53,69,.2); }
        .input-group:has(.login-input.is-valid) .input-group-text { border-color: #198754; color: #198754; }
        .input-group:has(.login-input.is-invalid) .input-group-text { border-color: #dc3545; color: #dc3545; }
        .input-group:has(.is-valid) .input-group-text i,
        .input-group:has(.is-invalid) .input-group-text i { color: inherit !important; }
    </style>
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
                    <p class="text-muted small mt-2 mb-0">
                        Accede a tu panel de paciente
                    </p>
                </div>

                <!-- MENSAJES DE ERROR -->
                @if (session('status'))
                    <div class="alert alert-success py-2 small" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- ÓPTICA SIN LICENCIA VIGENTE O SUSPENDIDA -->
                @if ($bloqueo = session('licencia_bloqueada'))
                    <div class="alert alert-warning small" role="alert">
                        <i class="bi bi-lock-fill me-1"></i>
                        {{ $bloqueo['mensaje'] }}
                        @if ($bloqueo['renovar_nit'])
                            <a href="{{ route('renovar.create', ['nit' => $bloqueo['renovar_nit']]) }}"
                               class="btn btn-sm btn-gradient rounded-pill w-100 mt-2">
                                Renovar mi plan
                            </a>
                        @endif
                    </div>
                @endif

                <!-- FORMULARIO -->
                <form method="POST" action="{{ route('login') }}" novalidate>
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
                                   placeholder="tu@correo.com" 
                                   required 
                                   autofocus>
                        </div>
                        <div id="email-feedback" class="small mt-1" aria-live="polite"></div>
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

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="small link-teal text-decoration-none">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-gradient w-100 rounded-pill py-2 fw-semibold mb-3">
                        Iniciar sesión
                    </button>

                    <a href="{{ url('/') }}" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-semibold text-decoration-none text-center">
                        &larr; Volver al menú
                    </a>
                </form>

            </div>
        </div>
    </div>

    <!-- VENTANA EMERGENTE: CUENTA INACTIVADA -->
    @if ($inactiva = session('cuenta_inactiva'))
        <div class="modal fade" id="modalCuentaInactiva" tabindex="-1" aria-labelledby="modalCuentaInactivaTitulo" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
                    <div class="modal-body p-4 p-md-5 text-center">
                        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger"
                             style="width: 64px; height: 64px;">
                            <i class="bi bi-lock-fill fs-3"></i>
                        </div>

                        <h5 class="fw-semibold mb-2" id="modalCuentaInactivaTitulo">Tu cuenta ha sido inactivada</h5>

                        <div id="reactivacion-contenido">
                            @if ($inactiva['es_admin'])
                                <p class="text-muted small mb-4">
                                    Por seguridad bloqueamos tu cuenta de administrador después de varios intentos fallidos.
                                    Comunícate con VisiOptica para que la active.
                                </p>
                            @elseif ($inactiva['ya_solicitada'])
                                <p class="text-muted small mb-4">
                                    Ya enviaste una solicitud de activación al administrador de la óptica.
                                    Te avisaremos por correo cuando tu cuenta esté activa.
                                </p>
                            @else
                                <p class="text-muted small mb-4">
                                    Por seguridad bloqueamos tu cuenta después de varios intentos fallidos.
                                    <strong>Solicita su activación aquí:</strong> le enviaremos un correo al administrador de la óptica.
                                </p>
                                <button type="button" id="btn-enviar-solicitud"
                                        class="btn btn-gradient w-100 rounded-pill py-2 fw-semibold mb-2">
                                    <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
                                    Enviar tu solicitud
                                </button>
                            @endif
                        </div>

                        <div id="reactivacion-respuesta" class="small mb-3 d-none" role="status"></div>

                        <button type="button" class="btn btn-outline-secondary w-100 rounded-pill py-2" data-bs-dismiss="modal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @if (session('cuenta_inactiva'))
    <!-- Abre la ventana de cuenta inactivada y envía la solicitud por correo al administrador -->
    <script>
    (function () {
        const modal = new bootstrap.Modal(document.getElementById('modalCuentaInactiva'));
        modal.show();

        const boton = document.getElementById('btn-enviar-solicitud');
        if (!boton) return;

        const respuesta = document.getElementById('reactivacion-respuesta');
        const contenido = document.getElementById('reactivacion-contenido');
        const spinner = boton.querySelector('.spinner-border');

        function mostrar(ok, texto) {
            respuesta.className = 'small mb-3 alert py-2 ' + (ok ? 'alert-success' : 'alert-danger');
            respuesta.textContent = texto;
        }

        boton.addEventListener('click', async function () {
            boton.disabled = true;
            spinner.classList.remove('d-none');

            try {
                const resp = await fetch("{{ route('login.solicitar-reactivacion') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const datos = await resp.json().catch(() => ({}));

                if (resp.ok && datos.ok) {
                    contenido.classList.add('d-none'); // ya no se puede volver a enviar
                    mostrar(true, datos.mensaje);
                } else {
                    mostrar(false, datos.mensaje || (resp.status === 429
                        ? 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.'
                        : 'No pudimos enviar la solicitud. Inténtalo de nuevo.'));
                    boton.disabled = false;
                }
            } catch (e) {
                mostrar(false, 'No hay conexión. Inténtalo de nuevo.');
                boton.disabled = false;
            }

            spinner.classList.add('d-none');
        });
    })();
    </script>
    @endif

    <!-- Validación en línea del correo -->
    <script>
    (function () {
        const token   = document.querySelector('meta[name="csrf-token"]').content;
        const email   = document.getElementById('email');
        const fbEmail = document.getElementById('email-feedback');
        const URL_EMAIL = "{{ route('login.verificar-email') }}";
        const formatoEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        let ultimaConsulta = 0;

        // Pinta el campo: true = verde, false = rojo, null = sin color
        function marcar(ok, mensaje) {
            email.classList.remove('is-valid', 'is-invalid');
            fbEmail.className = 'small mt-1';
            fbEmail.textContent = '';
            if (ok === null) return;
            email.classList.add(ok ? 'is-valid' : 'is-invalid');
            fbEmail.classList.add(ok ? 'text-success' : 'text-danger');
            fbEmail.textContent = mensaje;
        }

        async function validarEmail() {
            const valor = email.value.trim();
            const id = ++ultimaConsulta;

            if (valor === '') return marcar(null);
            if (!formatoEmail.test(valor)) return marcar(false, 'Escribe un correo válido.');

            try {
                const resp = await fetch(URL_EMAIL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ email: valor })
                });
                if (!resp.ok) throw new Error(resp.status);
                const r = await resp.json();
                if (id !== ultimaConsulta) return; // llegó una respuesta vieja
                marcar(r.existe, r.existe ? 'Correo registrado.' : 'Este correo no está registrado.');
            } catch (e) {
                marcar(null); // si falla la consulta, no se pinta nada
            }
        }

        // Espera a que el usuario deje de escribir antes de consultar
        let espera;
        email.addEventListener('input', function () {
            // Al corregir el correo se quita el mensaje de error que vino del servidor
            const errServidor = email.closest('.mb-3').querySelector('.invalid-feedback');
            if (errServidor) errServidor.remove();
            email.classList.remove('is-invalid');
            clearTimeout(espera);
            espera = setTimeout(validarEmail, 500);
        });
        email.addEventListener('blur', validarEmail);
    })();
    </script>
</body>
</html>