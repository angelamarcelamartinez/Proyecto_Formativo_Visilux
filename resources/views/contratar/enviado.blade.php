@include('includes.header')

<section class="py-5">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm p-4 p-md-5 text-center">

                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                         style="width: 72px; height: 72px; background: #e8f6ef;">
                        <i class="bi bi-check-lg fs-1 text-success"></i>
                    </div>

                    <h1 class="h3 fw-bold mb-2">
                        {{ $registro['pago'] ? 'Recibimos tu pago' : 'Recibimos tu solicitud' }}
                    </h1>
                    <p class="text-muted mb-4">
                        {{ $registro['empresa'] }} quedó registrada con el plan {{ $registro['plan'] }}.
                        Primero verificaremos tu NIT con la Cámara de Comercio. Cuando lo aprobemos llegará a <strong>{{ $registro['correo'] }}</strong>
                        un correo con la contraseña temporal; para entrar al panel usa el correo <strong>{{ $registro['usuario'] }}</strong>.
                    </p>

                    @if ($registro['pago'])
                        <div class="text-start bg-light rounded-3 p-3 mb-4 small">
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Valor</span>
                                <strong>{{ $registro['valor'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Tarjeta</span>
                                <span>{{ $registro['pago']['marca'] }} terminada en {{ $registro['pago']['ultimos4'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Referencia</span>
                                <span class="font-monospace">{{ $registro['pago']['referencia'] }}</span>
                            </div>
                        </div>
                        <p class="small text-muted mb-4">Guarda la referencia por si necesitas consultar tu pago.</p>
                    @endif

                    <div>
                        <a href="{{ url('/') }}" class="btn btn-gradient rounded-pill px-4">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('includes.footer')
