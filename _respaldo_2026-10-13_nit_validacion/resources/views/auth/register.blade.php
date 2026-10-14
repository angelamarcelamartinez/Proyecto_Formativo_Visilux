<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario | VisiOptica</title>
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
        <div class="card login-card border-0 shadow-lg w-100" style="max-width: 480px;">
            <div class="card-body p-4 p-md-5">

                <!-- LOGO Y ENCABEZADO -->
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}" class="text-decoration-none d-inline-block">
                        <img src="{{ asset('assets/img/logo-visilux.png') }}" alt="VisiOptica" class="login-logo img-fluid">
                    </a>
                    <p class="text-muted small mt-2 mb-0">
                        Crea tu cuenta en VisiOptica
                    </p>
                </div>

                <!-- FORMULARIO DE REGISTRO -->
                <form method="POST" action="{{ route('register') }}" novalidate>
                    @csrf

                    <!-- CAMPO DOCUMENTO -->
                    <!-- CAMPO TIPO DE DOCUMENTO -->
                    <div class="mb-3">
                        <label for="id_tipo_docu" class="form-label fw-medium">Tipo de documento</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-person-vcard text-muted"></i>
                            </span>
                            <select class="form-select border-start-0 login-input @error('id_tipo_docu') is-invalid @enderror"
                                    id="id_tipo_docu" name="id_tipo_docu" required>
                                @foreach (\Illuminate\Support\Facades\DB::table('tipo_documento')->orderBy('id_tipo_docu')->get() as $td)
                                    <option value="{{ $td->id_tipo_docu }}" @selected((string) old('id_tipo_docu', 1) === (string) $td->id_tipo_docu)>{{ $td->nom_ti_docu }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('id_tipo_docu')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="documento" class="form-label fw-medium">Número de documento</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-card-text text-muted"></i>
                            </span>
                            <input type="number"
                                   class="form-control border-start-0 login-input @error('documento') is-invalid @enderror"
                                   id="documento"
                                   name="documento"
                                   value="{{ old('documento') }}"
                                   placeholder="Ej: 1020304050"
                                   required
                                   autofocus>
                        </div>
                        @error('documento')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- CAMPO NOMBRES -->
                    <div class="mb-3">
                        <label for="nombres" class="form-label fw-medium">Nombres</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-person text-muted"></i>
                            </span>
                            <input type="text" 
                                   class="form-control border-start-0 login-input @error('nombres') is-invalid @enderror" 
                                   id="nombres" 
                                   name="nombres" 
                                   value="{{ old('nombres') }}" 
                                   placeholder="Tus nombres" 
                                   required>
                        </div>
                        @error('nombres')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- CAMPO APELLIDO -->
                    <div class="mb-3">
                        <label for="apellido" class="form-label fw-medium">Apellidos</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-person text-muted"></i>
                            </span>
                            <input type="text"
                                   class="form-control border-start-0 login-input @error('apellido') is-invalid @enderror"
                                   id="apellido"
                                   name="apellido"
                                   value="{{ old('apellido') }}"
                                   placeholder="Tus apellidos"
                                   required>
                        </div>
                        @error('apellido')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- CAMPO TELÉFONO -->
                    <div class="mb-3">
                        <label for="telefono" class="form-label fw-medium">Teléfono</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-telephone text-muted"></i>
                            </span>
                            <input type="text"
                                   class="form-control border-start-0 login-input @error('telefono') is-invalid @enderror"
                                   id="telefono"
                                   name="telefono"
                                   value="{{ old('telefono') }}"
                                   placeholder="Ej: 3001234567"
                                   required>
                        </div>
                        @error('telefono')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- CAMPO CORREO -->
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
                                   required>
                        </div>
                        @error('email')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- CAMPO CONTRASEÑA -->
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

                    <!-- CONFIRMAR CONTRASEÑA -->
                    <div class="mb-4">
                        <label for="password-confirm" class="form-label fw-medium">Confirmar contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-lock-fill text-muted"></i>
                            </span>
                            <input type="password" 
                                   class="form-control border-start-0 login-input" 
                                   id="password-confirm" 
                                   name="password_confirmation" 
                                   placeholder="••••••••" 
                                   required>
                        </div>
                    </div>

                    <!-- ACEPTACIÓN DE TÉRMINOS Y CONDICIONES -->
                    <div class="mb-4 form-check">
                        <input class="form-check-input @error('acepto_terminos') is-invalid @enderror"
                               type="checkbox" id="acepto_terminos" name="acepto_terminos" value="1" disabled required>
                        <label class="form-check-label small" for="acepto_terminos">
                            He leído y acepto los
                            <a href="#" data-bs-toggle="modal" data-bs-target="#modalTerminos" class="fw-semibold text-decoration-none">
                                Términos y Condiciones y la Autorización de Tratamiento de Datos
                            </a>
                        </label>
                        @error('acepto_terminos')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <!-- BOTÓN ENVIAR -->
                    <button type="submit" id="btnRegistrar" class="btn btn-gradient w-100 rounded-pill py-2 fw-semibold mb-3" disabled>
                        Registrarse
                    </button>

                    <!-- VOLVER AL LOGIN -->
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-semibold text-decoration-none text-center">
                        &larr; ¿Ya tienes cuenta? Inicia sesión
                    </a>
                </form>

            </div>
        </div>
    </div>

    <!-- ======================================================
         MODAL: Autorización para el Tratamiento de Datos Personales
    ====================================================== -->
    <div class="modal fade" id="modalTerminos" tabindex="-1" aria-labelledby="modalTerminosLabel"
         data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="modalTerminosLabel">
                        Autorización para el Tratamiento de Datos Personales — VisiOptica
                    </h5>
                </div>
                <div class="modal-body small" id="terminosBody">
                    <p>De conformidad con la Ley 1581 de 2012, el Decreto 1377 de 2013 y demás normas concordantes
                        que regulan la protección de datos personales en Colombia, VisiOptica, como Responsable del
                        Tratamiento, solicita su autorización previa, expresa e informada para el tratamiento de sus
                        datos personales.</p>

                    <h6 class="fw-semibold mt-4">1. Datos que serán recolectados</h6>
                    <ul>
                        <li><strong>Datos de identificación:</strong> nombre completo, número de documento, fecha de nacimiento.</li>
                        <li><strong>Datos de contacto:</strong> dirección, teléfono, correo electrónico.</li>
                        <li><strong>Datos sensibles de salud visual:</strong> diagnósticos optométricos, fórmulas ópticas, historial de agudeza visual y demás información relacionada con su salud visual.</li>
                        <li><strong>Datos financieros:</strong> información de medios de pago para la compra de productos.</li>
                        <li><strong>Datos de navegación:</strong> dirección IP, cookies y preferencias de uso del sitio web.</li>
                    </ul>

                    <h6 class="fw-semibold mt-4">2. Finalidad del tratamiento</h6>
                    <p>Sus datos serán utilizados para: (a) gestionar su historia optométrica y brindar la atención
                        requerida; (b) procesar compras y pagos de productos ópticos; (c) agendar y confirmar citas;
                        (d) enviar comunicaciones relacionadas con el servicio; (e) mejorar la experiencia del sitio
                        web mediante análisis de navegación; (f) cumplir obligaciones legales y contractuales.</p>

                    <h6 class="fw-semibold mt-4">3. Carácter sensible de los datos de salud</h6>
                    <p>Le informamos que los datos relacionados con su salud visual son considerados datos sensibles
                        conforme al artículo 5 de la Ley 1581 de 2012. Usted no está obligado a autorizar su
                        tratamiento; sin embargo, sin esta autorización no será posible prestarle el servicio de
                        historia optométrica.</p>

                    <h6 class="fw-semibold mt-4">4. Derechos del titular</h6>
                    <p>Usted tiene derecho a: conocer, actualizar y rectificar sus datos; solicitar prueba de la
                        autorización otorgada; ser informado sobre el uso dado a sus datos; presentar quejas ante la
                        Superintendencia de Industria y Comercio (SIC); revocar la autorización y/o solicitar la
                        supresión de sus datos, salvo que exista un deber legal o contractual que lo impida; acceder
                        gratuitamente a sus datos.</p>

                    <h6 class="fw-semibold mt-4">5. Responsable del tratamiento</h6>
                    <p>VisiOptica, [dirección], [correo de contacto para ejercer derechos: datos@visioptica.com].
                        Puede consultar la Política de Tratamiento de Datos Personales completa en [enlace].</p>

                    <hr class="my-4">

                    <p class="fw-semibold mb-0">
                        Al hacer clic en "Aceptar y continuar", autorizo de manera previa, expresa e informada
                        el tratamiento de mis datos personales, incluidos mis datos sensibles de salud visual
                        y financieros, en los términos aquí descritos.
                    </p>

                    <p class="text-muted small mb-0 mt-2" id="scrollHint">
                        <i class="bi bi-arrow-down-circle"></i> Desplázate hasta el final de este texto para poder aceptar.
                    </p>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" id="btnNoAcepto" data-bs-dismiss="modal">
                        No acepto
                    </button>
                    <button type="button" class="btn btn-gradient rounded-pill" id="btnAceptarContinuar" disabled>
                        Aceptar y continuar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // "Aceptar y continuar" solo se habilita cuando la persona
    // baja hasta el final del texto. Al hacer clic, se acepta.
    document.addEventListener('DOMContentLoaded', function () {
        const modalBody = document.getElementById('terminosBody');
        const btnAceptar = document.getElementById('btnAceptarContinuar');
        const btnNoAcepto = document.getElementById('btnNoAcepto');
        const scrollHint = document.getElementById('scrollHint');
        const aceptoTerminos = document.getElementById('acepto_terminos');
        const btnRegistrar = document.getElementById('btnRegistrar');

        function habilitarBoton() {
            const yaEstaAbajo = modalBody.scrollTop + modalBody.clientHeight >= modalBody.scrollHeight - 15;
            if (yaEstaAbajo) {
                btnAceptar.disabled = false;
                scrollHint.style.display = 'none';
            }
        }

        modalBody.addEventListener('scroll', habilitarBoton);

        btnAceptar.addEventListener('click', function () {
            aceptoTerminos.disabled = false; // un checkbox disabled no se envía
            aceptoTerminos.checked = true;
            btnRegistrar.disabled = false;
            bootstrap.Modal.getInstance(document.getElementById('modalTerminos')).hide();
        });

        btnNoAcepto.addEventListener('click', function () {
            btnAceptar.disabled = true;
            scrollHint.style.display = '';
            modalBody.scrollTop = 0;
            aceptoTerminos.checked = false;
            aceptoTerminos.disabled = true;
            btnRegistrar.disabled = true;
        });

        // Si el texto cabe completo sin scroll (pantallas altas), se habilita igual.
        document.getElementById('modalTerminos').addEventListener('shown.bs.modal', habilitarBoton);
    });
</script>
</body>
</html>