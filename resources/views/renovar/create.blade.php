@include('includes.header')

@php $campo = fn ($name) => 'form-control' . ($errors->has($name) ? ' is-invalid' : ''); @endphp

<section class="page-header">
    <div class="container text-center">
        <span class="page-eyebrow d-block mb-2">Ópticas registradas</span>
        <h1 class="page-title display-5 mb-3">Renueva tu plan</h1>
        <p class="page-subtitle mx-auto">
            Si tu licencia venció o está por vencer, renuévala aquí. Cuando confirmemos el pago
            vuelves a entrar al panel con tu misma cuenta.
        </p>
    </div>
</section>

<section class="py-5">
    <div class="container py-2">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                @if ($r = session('renovacion'))
                    <div class="card border-0 shadow-sm p-4 p-md-5 text-center">
                        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                             style="width: 72px; height: 72px; background: #e8f6ef;">
                            <i class="bi bi-check-lg fs-1 text-success"></i>
                        </div>
                        <h2 class="h3 fw-bold mb-2">Recibimos tu pago</h2>
                        <p class="text-muted mb-4">
                            Renovación de {{ $r['empresa'] }} con el plan {{ $r['plan'] }} por {{ $r['valor'] }}.
                            Cuando lo aprobemos te llegará un correo de confirmación y podrás volver a entrar.
                            El plan empieza el {{ $r['inicio'] }}.
                        </p>
                        <div class="text-start bg-light rounded-3 p-3 mb-4 small">
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Tarjeta</span>
                                <span>{{ $r['pago']['marca'] }} terminada en {{ $r['pago']['ultimos4'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Referencia</span>
                                <span class="font-monospace">{{ $r['pago']['referencia'] }}</span>
                            </div>
                        </div>
                        <div><a href="{{ url('/') }}" class="btn btn-gradient rounded-pill px-4">Volver al inicio</a></div>
                    </div>
                @else

                    @if ($errors->any())
                        <div class="alert alert-danger mb-4">
                            <i class="bi bi-exclamation-circle me-2"></i>Revisa los campos marcados en rojo. No se hizo ningún cobro.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('renovar.store') }}" novalidate>
                        @csrf

                        <div class="card border-0 shadow-sm p-4 p-md-5 mb-4">
                            <p class="fw-semibold mb-1">Tu óptica</p>
                            <p class="text-muted small mb-4">Con estos datos confirmamos que eres administrador de la óptica.</p>
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label for="nit" class="form-label fw-medium">NIT de la óptica</label>
                                    <input type="text" id="nit" name="nit" value="{{ old('nit', $nit) }}" class="{{ $campo('nit') }}" required>
                                    @error('nit') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-7">
                                    <label for="admin_email" class="form-label fw-medium">Correo con el que entras al panel</label>
                                    <input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" class="{{ $campo('admin_email') }}" required>
                                    @error('admin_email') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm p-4 p-md-5 mb-4">
                            <p class="fw-semibold mb-3">Plan</p>
                            <div class="row g-3">
                                @foreach ($planes as $plan)
                                    <div class="col-md-4">
                                        <input type="radio" class="btn-check" name="id_plan" id="plan{{ $plan->id_plan }}" value="{{ $plan->id_plan }}"
                                               @checked(old('id_plan', $planes->firstWhere('destacado', true)?->id_plan) == $plan->id_plan)>
                                        <label class="btn btn-outline-secondary w-100 text-start p-3" for="plan{{ $plan->id_plan }}">
                                            <span class="d-block fw-semibold">{{ $plan->nombre }}</span>
                                            <span class="d-block fs-5">{{ $plan->precioFormateado() }}</span>
                                            <span class="d-block small opacity-75">${{ number_format($plan->precio / max($plan->meses, 1), 0, ',', '.') }} al mes</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('id_plan') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="card border-0 shadow-sm p-4 p-md-5 mb-4">
                            <p class="fw-semibold mb-3">Pago con tarjeta</p>
                            @include('contratar.partials.tarjeta')
                        </div>

                        <button type="submit" class="btn btn-gradient btn-lg rounded-pill px-5">
                            <i class="bi bi-lock-fill me-2"></i>Pagar renovación
                        </button>
                    </form>
                @endif

            </div>
        </div>
    </div>
</section>

@include('includes.footer')
