<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
// Hash: encripta la contraseña nueva.
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Pantalla obligatoria del primer ingreso: cambiar la contraseña temporal
 * que llegó por correo por una que solo conozca el administrador.
 */
// AQUÍ se cambia la contraseña temporal. Rutas GET/POST /cambiar-password (grupo auth en routes/web.php).
class CambiarPasswordController extends Controller
{
    // GET /cambiar-password: muestra el formulario.
    public function edit(Request $request): View|RedirectResponse
    {
        // Si ya cambió su contraseña no tiene nada que hacer aquí: lo manda a su panel.
        if (! $request->user()->debe_cambiar_password) {
            return redirect($this->destino($request));
        }

        // Vista resources/views/auth/cambiar-password.blade.php.
        return view('auth.cambiar-password');
    }

    // POST /cambiar-password: guarda la contraseña nueva.
    public function update(Request $request): RedirectResponse
    {
        // Validaciones del servidor:
        $request->validate([
            // La contraseña actual (la temporal) es obligatoria y debe coincidir con la guardada.
            'password_actual' => ['required', 'current_password'],
            // La nueva: obligatoria, repetida igual en password_confirmation, mínimo 8 caracteres y distinta de la temporal.
            'password' => ['required', 'confirmed', 'min:8', 'different:password_actual'],
        ], [
            // Mensajes de error personalizados en español.
            'password_actual.current_password' => 'La contraseña temporal no es correcta.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.different' => 'La nueva contraseña debe ser distinta a la temporal.',
        ]);

        // Usuario logueado.
        $user = $request->user();
        // forceFill asigna columnas sin pasar por $fillable...
        $user->forceFill([
            // Guarda la contraseña NUEVA encriptada.
            'password' => Hash::make($request->password),
            // Pasa de 3 (pendiente) a 1 (activo): desde ahora el middleware ya no lo obliga a cambiarla.
            'id_estado' => 1, // Activo
            // Invalida los "recordarme" anteriores de ese usuario.
            'remember_token' => Str::random(60),
        ])->save();

        // Lo manda a su panel con mensaje de bienvenida.
        return redirect($this->destino($request))->with('success', 'Contraseña actualizada. ¡Bienvenido!');
    }

    // Administrador → /admin; cualquier otro → /.
    private function destino(Request $request): string
    {
        return $request->user()->esAdministrador() ? '/admin' : '/';
    }
}
