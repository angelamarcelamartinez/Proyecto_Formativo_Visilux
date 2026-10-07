<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Pantalla obligatoria del primer ingreso: cambiar la contraseña temporal
 * que llegó por correo por una que solo conozca el administrador.
 */
class CambiarPasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user()->debe_cambiar_password) {
            return redirect($this->destino($request));
        }

        return view('auth.cambiar-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password_actual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8', 'different:password_actual'],
        ], [
            'password_actual.current_password' => 'La contraseña temporal no es correcta.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.different' => 'La nueva contraseña debe ser distinta a la temporal.',
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($request->password),
            'id_estado' => 1, // Activo
            'remember_token' => Str::random(60),
        ])->save();

        return redirect($this->destino($request))->with('success', 'Contraseña actualizada. ¡Bienvenido!');
    }

    private function destino(Request $request): string
    {
        return $request->user()->esAdministrador() ? '/admin' : '/';
    }
}
