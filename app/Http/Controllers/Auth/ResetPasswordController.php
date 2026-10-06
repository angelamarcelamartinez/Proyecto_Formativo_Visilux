<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Recuperar contraseña: formulario al que lleva el enlace del correo
    |--------------------------------------------------------------------------
    |
    | Al guardar la nueva contraseña se envía a la persona al inicio de
    | sesión con un mensaje de confirmación (antes intentaba ir a /home,
    | una ruta que no existe, y por eso salía un error).
    |
    */

    use ResetsPasswords;

    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ];
    }

    protected function validationErrorMessages()
    {
        return [
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    /**
     * Guarda la nueva contraseña (sin iniciar sesión automáticamente).
     */
    protected function resetPassword($user, $password)
    {
        $user->password = Hash::make($password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        event(new PasswordReset($user));
    }

    protected function sendResetResponse(Request $request, $response): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => trans($response)], 200);
        }

        // Quien acaba de restablecer con el enlace del correo es el dueño de la cuenta:
        // si es el superadmin, vuelve a su login (el público no lo deja entrar).
        $esSuperadmin = \App\Models\Usuario::where('email', $request->email)->first()?->esSuperadmin();

        return redirect()->route($esSuperadmin ? 'superadmin.login' : 'login')
            ->with('status', trans($response))
            ->withInput(['email' => $request->email]);
    }
}
