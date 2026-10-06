<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RecordatorioControlMail;
use App\Support\ControlReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ControlController extends Controller
{
    public function index(Request $request): View
    {
        $dias = (int) $request->get('dias', 30);
        $dias = max(7, min($dias, 180));

        $pacientes = ControlReminder::proximos($dias)->map(function ($p) {
            $p->recordatorio_enviado = Cache::has($this->cacheKey($p->documento, $p->fecha_aniversario->year));

            return $p;
        });

        return view('admin.control.index', [
            'pacientes' => $pacientes,
            'dias' => $dias,
        ]);
    }

    public function enviar(Request $request, string $documento): RedirectResponse
    {
        $dias = (int) $request->get('dias', 30);
        $paciente = ControlReminder::proximos($dias)->firstWhere('documento', (int) $documento);

        if (! $paciente) {
            return back()->withErrors([
                'general' => 'Ese paciente ya no está dentro del rango de recordatorio.',
            ]);
        }

        if ($this->enviarCorreo($paciente)) {
            return back()->with('success', "Recordatorio enviado a {$paciente->nombres} {$paciente->apellido}.");
        }

        return back()->withErrors([
            'general' => 'No se pudo enviar el correo. Revisa la configuración de correo (MAIL_MAILER) en tu archivo .env.',
        ]);
    }

    public function enviarTodos(Request $request): RedirectResponse
    {
        $dias = (int) $request->get('dias', 30);
        $pacientes = ControlReminder::proximos($dias);

        $enviados = 0;
        $fallidos = 0;

        foreach ($pacientes as $paciente) {
            if (Cache::has($this->cacheKey($paciente->documento, $paciente->fecha_aniversario->year))) {
                continue; // ya se le había enviado
            }

            if ($this->enviarCorreo($paciente)) {
                $enviados++;
            } else {
                $fallidos++;
            }
        }

        if ($enviados === 0 && $fallidos === 0) {
            return back()->with('success', 'No había recordatorios pendientes por enviar.');
        }

        $mensaje = "Se enviaron {$enviados} recordatorio(s) nuevo(s).";
        if ($fallidos > 0) {
            $mensaje .= " {$fallidos} fallaron (revisa la configuración de correo).";
        }

        return back()->with('success', $mensaje);
    }

    protected function enviarCorreo(object $paciente): bool
    {
        try {
            Mail::to($paciente->correo)->send(new RecordatorioControlMail($paciente));
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        Cache::put(
            $this->cacheKey($paciente->documento, $paciente->fecha_aniversario->year),
            true,
            now()->addDays(400)
        );

        return true;
    }

    protected function cacheKey(int $documento, int $anio): string
    {
        return "control_recordatorio_enviado:{$documento}:{$anio}";
    }
}
