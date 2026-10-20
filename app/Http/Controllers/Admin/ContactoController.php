<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Alcance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * "Por contactar": mensajes del formulario Contáctanos (tabla mensajes_contacto).
 * El listado lo hace el CRUD genérico; aquí solo está el botón de un clic
 * para marcar un mensaje como "Contactado" (o devolverlo a "Por contactar").
 */
class ContactoController extends Controller
{
    public function estado(int $id): RedirectResponse
    {
        // Solo mensajes de la óptica del administrador (Alcance filtra por nit_empresa).
        $mensaje = Alcance::restringir(DB::table('mensajes_contacto'), 'mensajes_contacto')
            ->where('id_contactenos', $id)
            ->first(['id_contactenos', 'nombre_contactenos', 'estado_contactenos']);

        abort_unless($mensaje, 404);

        $nuevo = $mensaje->estado_contactenos === 'Contactado' ? 'Por contactar' : 'Contactado';

        DB::table('mensajes_contacto')->where('id_contactenos', $id)->update(['estado_contactenos' => $nuevo]);

        return back()->with('success', $nuevo === 'Contactado'
            ? "Marcaste a {$mensaje->nombre_contactenos} como contactado."
            : "{$mensaje->nombre_contactenos} volvió a «Por contactar».");
    }
}
