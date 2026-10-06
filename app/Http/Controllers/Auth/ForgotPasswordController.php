<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Recuperar contraseña: envío del enlace por correo
    |--------------------------------------------------------------------------
    |
    | Cuando el correo se envía bien, la persona vuelve al formulario de
    | inicio de sesión con el mensaje "Revisa tu correo para el siguiente
    | paso". Si algo falla, se queda en este formulario con el error.
    |
    */

    use SendsPasswordResetEmails;

    protected function validateEmail(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
    }

    protected function sendResetLinkResponse(Request $request, $response): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => trans($response)], 200);
        }

        // Si la petición vino del login del superadmin, vuelve a ese login.
        $destino = $request->input('origen') === 'superadmin' ? 'superadmin.login' : 'login';

        return redirect()->route($destino)->with('status', trans($response));
    }
}
