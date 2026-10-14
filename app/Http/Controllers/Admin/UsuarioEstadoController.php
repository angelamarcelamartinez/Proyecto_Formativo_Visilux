<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Support\Alcance;
use App\Support\BloqueoUsuarios;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Botón "Activar / Desactivar" del listado de usuarios del panel de administración.
 */
class UsuarioEstadoController extends Controller
{
    public function cambiar(string $documento): RedirectResponse
    {
        // Solo usuarios de la óptica del administrador.
        $existe = Alcance::restringir(DB::table('usuario'), 'usuario')
            ->where('usuario.documento', $documento)
            ->exists();

        $usuario = $existe ? Usuario::find($documento) : null;

        if (! $usuario || $usuario->esSuperadmin()) {
            throw new NotFoundHttpException('Usuario no encontrado.');
        }

        if ((string) $usuario->documento === (string) Auth::id()) {
            return back()->withErrors(['general' => 'No puedes desactivar tu propia cuenta.']);
        }

        // Los administradores solo los activa o desactiva VisiOptica (panel del superadmin).
        if ($usuario->esAdministrador()) {
            return back()->withErrors(['general' => 'No puedes activar ni desactivar a otro administrador.']);
        }

        $nombre = trim($usuario->nombres . ' ' . $usuario->apellido);

        if (BloqueoUsuarios::estaInactivo($usuario)) {
            $correo = BloqueoUsuarios::activar($usuario, 'Usuario activado por el administrador');

            return back()->with('success', "Usuario {$nombre} activado. Ya puede iniciar sesión."
                . ($correo ? " Le enviamos un correo a {$usuario->email} avisándole." : ' (No se pudo enviar el correo de aviso.)'));
        }

        BloqueoUsuarios::inactivar($usuario, 'Usuario desactivado por el administrador');

        return back()->with('success', "Usuario {$nombre} desactivado. No podrá iniciar sesión hasta que lo actives.");
    }
}
