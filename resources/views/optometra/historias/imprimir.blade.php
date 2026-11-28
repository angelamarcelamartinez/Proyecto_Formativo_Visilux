@php
    use App\Support\Optometria;
    use Illuminate\Support\Carbon;
    $logo = $pagina?->logo ?: config('visioptica.imagenes_por_defecto.logo');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Fórmula · {{ $h->nombres }} {{ $h->apellido }}</title>
    @include('includes.favicon')
    <style>
        * { box-sizing: border-box; }
        body { font-family: Inter, Arial, sans-serif; color: #2E2A22; margin: 0; background: #F1EBDD; }
        .hoja { width: 210mm; min-height: 148mm; margin: 24px auto; background: #fff; padding: 16mm 16mm 12mm; box-shadow: 0 8px 24px -12px rgba(46,42,34,.25); }
        header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #7E6427; padding-bottom: 10px; }
        header img { max-height: 54px; max-width: 180px; object-fit: contain; }
        header .optica { text-align: right; font-size: 11px; line-height: 1.5; color: #6b6555; }
        header .optica b { font-size: 15px; color: #2E2A22; display: block; }
        h1 { font-family: Fraunces, Georgia, serif; font-weight: 500; font-size: 20px; margin: 18px 0 4px; }
        .datos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px 18px; font-size: 12px; margin: 10px 0 16px; }
        .datos span { color: #8A8477; display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #E4DCC7; padding: 7px 8px; text-align: center; }
        thead th { background: #F7F1E1; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #6B5522; }
        tbody th { background: #F7F1E1; width: 46px; }
        .bloque { margin-top: 14px; font-size: 12px; line-height: 1.55; }
        .bloque b { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #8A8477; }
        .firma { margin-top: 40px; display: flex; justify-content: space-between; align-items: flex-end; font-size: 11px; color: #6b6555; }
        .firma .linea { border-top: 1px solid #2E2A22; width: 230px; padding-top: 6px; text-align: center; color: #2E2A22; }
        .acciones { text-align: center; margin: 16px; }
        .acciones button { background: #7E6427; color: #FAF7F2; border: 0; padding: 10px 22px; border-radius: 10px; font-size: 14px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .hoja { margin: 0; box-shadow: none; width: auto; min-height: 0; padding: 10mm; }
            .acciones { display: none; }
        }
    </style>
</head>
<body>
    <div class="acciones"><button onclick="window.print()">Imprimir / guardar como PDF</button></div>

    <div class="hoja">
        <header>
            <img src="{{ asset($logo) }}" alt="{{ $empresa->nombre ?? 'Óptica' }}">
            <div class="optica">
                <b>{{ $empresa->nombre ?? 'Óptica' }}</b>
                NIT {{ $empresa->nit ?? '' }}<br>
                {{ $pagina->direccion ?? $empresa->direccion ?? '' }} {{ $pagina->telefono ?? $empresa->telefono ?? '' }}<br>
                {{ $pagina->email ?? $empresa->email ?? '' }}
            </div>
        </header>

        <h1>Fórmula óptica</h1>
        <div class="datos">
            <div><span>Paciente</span>{{ $h->nombres }} {{ $h->apellido }}</div>
            <div><span>{{ $h->nom_ti_docu ?? 'Documento' }}</span>{{ $h->documento }}</div>
            <div><span>Fecha</span>{{ Carbon::parse($formula->fecha_formula ?? $h->fecha_registro)->format('d/m/Y') }}</div>
        </div>

        @if ($formula)
            <table>
                <thead><tr><th></th><th>Esfera</th><th>Cilindro</th><th>Eje</th><th>Adición</th><th>DP</th><th>AV lejos</th><th>AV cerca</th></tr></thead>
                <tbody>
                    @foreach (['od' => 'OD', 'oi' => 'OI'] as $o => $etq)
                        <tr>
                            <th>{{ $etq }}</th>
                            <td>{{ Optometria::dioptria($formula->{$o.'_esfera'}) }}</td>
                            <td>{{ Optometria::dioptria($formula->{$o.'_cilindro'}) }}</td>
                            <td>{{ $formula->{$o.'_eje'} !== null ? $formula->{$o.'_eje'} . '°' : '—' }}</td>
                            <td>{{ Optometria::dioptria($formula->{$o.'_adc'}) }}</td>
                            <td>{{ $formula->{$o.'_dist_pupilar'} ?? '—' }}</td>
                            <td>{{ $formula->{$o.'_av_lejos'} ?: '—' }}</td>
                            <td>{{ $formula->{$o.'_av_cerca'} ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="datos" style="margin-top:14px">
                <div><span>Corrección</span>{{ $formula->correccion ?: '—' }}</div>
                <div><span>Forma de uso</span>{{ $formula->forma_uso ?: '—' }}</div>
                <div><span>Próximo control</span>{{ $formula->proximo_control ? Carbon::parse($formula->proximo_control)->format('d/m/Y') : '—' }}</div>
            </div>
            @if ($formula->recomendaciones)
                <div class="bloque"><b>Indicaciones</b>{{ $formula->recomendaciones }}</div>
            @endif
        @endif

        @if ($diagnosticos->isNotEmpty())
            <div class="bloque">
                <b>Diagnóstico</b>
                @foreach ($diagnosticos as $d)
                    {{ $d->codigo }} {{ $d->nombre_enfer }} ({{ $d->ojo }}){{ ! $loop->last ? ' · ' : '' }}
                @endforeach
            </div>
        @endif

        @if ($medicamentos->isNotEmpty())
            <div class="bloque">
                <b>Medicamentos</b>
                @foreach ($medicamentos as $m)
                    {{ $m->nombre }} × {{ $m->cantidad }} — {{ $m->dosis }}<br>
                @endforeach
            </div>
        @endif

        @if ($h->recomendaciones)
            <div class="bloque"><b>Recomendaciones</b>{!! nl2br(e($h->recomendaciones)) !!}</div>
        @endif

        <div class="firma">
            <div>Historia clínica #{{ $h->id_historia_clinica }}</div>
            <div class="linea">
                {{ $h->opt_nombre }} {{ $h->opt_apellido }}<br>
                {{ $h->opt_especialidad ?: 'Optómetra' }}{{ $h->tarjeta_profesional ? ' · T.P. ' . $h->tarjeta_profesional : '' }}
            </div>
        </div>
    </div>
</body>
</html>
