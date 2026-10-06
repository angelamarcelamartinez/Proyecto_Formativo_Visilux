<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\CarritoController;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * A dónde redirigir según el rol del usuario autenticado:
     * administradores van directo al panel (/admin), el resto
     * se queda en el menú principal público (/).
     */
    protected function redirectTo(): string
    {
        $user = auth()->user();

        return ($user && $user->esAdministrador()) ? '/admin' : '/';
    }

    /**
     * Laravel llama esto automáticamente justo después de un login
     * exitoso, antes de redirigir. Aquí fusionamos lo que la persona
     * haya agregado al carrito como invitada con su carrito real,
     * para que no pierda nada al iniciar sesión.
     */
    protected function authenticated(Request $request, $user)
    {
        app(CarritoController::class)->fusionarCarritoInvitado($user->documento);
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
