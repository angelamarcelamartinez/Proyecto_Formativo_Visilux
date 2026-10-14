@include('includes.header')

@php
    $esPago = $plan->precio > 0;
    $precio = $plan->precioFormateado();
    $campo = fn ($name) => 'form-control' . ($errors->has($name) ? ' is-invalid' : '');
@endphp

<style>
    .contratar-seccion { font-size: 1.05rem; font-weight: 600; margin-bottom: .25rem; }
    .contratar-paso { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
                      font-size: .85rem; font-weight: 600; background: var(--bs-primary-bg-subtle, #e7f1ff); margin-right: .5rem; }
    .resumen-plan { position: sticky; top: 100px; }
</style>

<!-- ======================================================
     BANNER
====================================================== -->
<section class="page-header">
    <div class="container text-center">
        <span class="page-eyebrow d-block mb-2">Plan {{ $plan->nombre }}</span>
        <h1 class="page-title display-5 mb-3">Registra tu óptica</h1>
        <p class="page-subtitle mx-auto">
            Completa los datos de tu óptica y de quien la va a administrar.
            @if ($esPago)
                Cuando confirmemos el pago te enviaremos por correo los datos para entrar.
            @else
                Cuando aprobemos tu solicitud te enviaremos por correo los datos para entrar.
            @endif
        </p>
    </div>
</section>

<section class="py-5">
    <div class="container py-2">

        <form method="POST" action="{{ route('contratar.store', $plan->id_plan) }}" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="row g-4">

                {{-- Formulario --}}
                <div class="col-lg-8">

                    @if ($errors->any())
                        <div class="alert alert-danger mb-4">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            Revisa los campos marcados en rojo. {{ $esPago ? 'No se hizo ningún cobro.' : '' }}
                        </div>
                    @endif

                    {{-- 1. Óptica --}}
                    <div class="card border-0 shadow-sm p-4 p-md-5 mb-4">
                        <p class="contratar-seccion"><span class="contratar-paso">1</span>Datos de la óptica</p>
                        <p class="text-muted small mb-4">Así aparecerá tu óptica en VisiOptica y en su página web.</p>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="nombre" class="form-label fw-medium">Nombre de la óptica</label>
                                <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" class="{{ $campo('nombre') }}" required>
                                @error('nombre') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="nit" class="form-label fw-medium">NIT</label>
                                <input type="text" id="nit" name="nit" value="{{ old('nit') }}" placeholder="900123456-7" class="{{ $campo('nit') }}" required>
                                @error('nit') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-medium">Correo de la óptica</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" class="{{ $campo('email') }}" required>
                                <span class="form-text">Aquí llegan los avisos de pago y de vencimiento.</span>
                                @error('email') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="telefono" class="form-label fw-medium">Teléfono</label>
                                <input type="text" id="telefono" name="telefono" value="{{ old('telefono') }}" class="{{ $campo('telefono') }}" required>
                                @error('telefono') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-5">
                                <label for="ciudad" class="form-label fw-medium">Ciudad</label>
                                <input type="text" id="ciudad" name="ciudad" value="{{ old('ciudad') }}" class="{{ $campo('ciudad') }}" required>
                                @error('ciudad') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-7">
                                <label for="direccion" class="form-label fw-medium">Dirección <span class="text-muted fw-normal">(opcional)</span></label>
                                <input type="text" id="direccion" name="direccion" value="{{ old('direccion') }}" class="{{ $campo('direccion') }}">
                                @error('direccion') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-12">
                                <label for="camara_comercio" class="form-label fw-medium">Certificado de la Cámara de Comercio <span class="text-muted fw-normal">(PDF, máx. 5 MB)</span></label>
                                <input type="file" id="camara_comercio" name="camara_comercio" accept="application/pdf,.pdf" class="{{ $campo('camara_comercio') }}" required>
                                <span class="form-text">Certificado de existencia y representación legal. Con él verificamos que tu NIT existe antes de activar la óptica.</span>
                                @error('camara_comercio') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- 2. Administrador --}}
                    <div class="card border-0 shadow-sm p-4 p-md-5 mb-4">
                        <p class="contratar-seccion"><span class="contratar-paso">2</span>Administrador</p>
                        <p class="text-muted small mb-4">La persona que va a manejar el panel. Entra con su correo y, en el primer ingreso, debe cambiar la contraseña temporal.</p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="admin_nombres" class="form-label fw-medium">Nombres</label>
                                <input type="text" id="admin_nombres" name="admin_nombres" value="{{ old('admin_nombres') }}" class="{{ $campo('admin_nombres') }}" required>
                                @error('admin_nombres') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="admin_apellido" class="form-label fw-medium">Apellidos</label>
                                <input type="text" id="admin_apellido" name="admin_apellido" value="{{ old('admin_apellido') }}" class="{{ $campo('admin_apellido') }}" required>
                                @error('admin_apellido') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="admin_documento" class="form-label fw-medium">Número de cédula</label>
                                <input type="text" inputmode="numeric" id="admin_documento" name="admin_documento" value="{{ old('admin_documento') }}" class="{{ $campo('admin_documento') }}" required>
                                @error('admin_documento') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="admin_telefono" class="form-label fw-medium">Celular</label>
                                <input type="text" inputmode="tel" id="admin_telefono" name="admin_telefono" value="{{ old('admin_telefono') }}" class="{{ $campo('admin_telefono') }}" required>
                                @error('admin_telefono') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-12">
                                <label for="admin_email" class="form-label fw-medium">Correo para entrar al panel</label>
                                <input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" class="{{ $campo('admin_email') }}" required>
                                <span class="form-text">Es tu usuario para entrar al panel. La contraseña temporal llega al correo de la óptica.</span>
                                @error('admin_email') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- 3. Pago --}}
                    @if ($esPago)
                        <div class="card border-0 shadow-sm p-4 p-md-5 mb-4">
                            <p class="contratar-seccion"><span class="contratar-paso">3</span>Pago con tarjeta</p>

                            @include('contratar.partials.tarjeta')
                        </div>
                    @endif

                    <div class="form-check mb-4">
                        <input class="form-check-input @error('acepto') is-invalid @enderror" type="checkbox" value="1" id="acepto" name="acepto" @checked(old('acepto'))>
                        <label class="form-check-label" for="acepto">
                            Acepto los términos del servicio de VisiOptica y el tratamiento de los datos de mi óptica.
                        </label>
                        @error('acepto') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="btn btn-gradient btn-lg rounded-pill px-5">
                        @if ($esPago)
                            <i class="bi bi-lock-fill me-2"></i>Pagar {{ $precio }}
                        @else
                            <i class="bi bi-send me-2"></i>Solicitar prueba gratis
                        @endif
                    </button>
                </div>

                {{-- Resumen --}}
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-4 resumen-plan">
                        <p class="text-muted small mb-1">Tu plan</p>
                        <h2 class="h4 fw-bold mb-1">{{ $plan->nombre }}</h2>
                        <p class="display-6 fw-semibold mb-0">{{ $precio }}</p>
                        <p class="text-muted small mb-4">
                            Por {{ $plan->meses }} {{ $plan->meses === 1 ? 'mes' : 'meses' }}
                            @if ($esPago && $plan->meses > 1)
                                · ${{ number_format($plan->precio / $plan->meses, 0, ',', '.') }} al mes
                            @endif
                        </p>

                        <p class="fw-semibold small mb-2">Qué pasa después</p>
                        <ol class="small text-muted ps-3 mb-4">
                            <li class="mb-1">{{ $esPago ? 'Revisamos tu pago.' : 'Revisamos tu solicitud.' }}</li>
                            <li class="mb-1">Te enviamos al correo del administrador la contraseña para entrar.</li>
                            <li>Entras a tu panel y personalizas la página de tu óptica.</li>
                        </ol>

                        <a href="{{ route('planes') }}" class="small">Cambiar de plan</a>
                    </div>
                </div>

            </div>
        </form>

    </div>
</section>



@include('includes.footer')
