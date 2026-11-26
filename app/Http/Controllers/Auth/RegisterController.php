<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        // "1.020.304" -> "1020304", "av 123456" -> "AV123456"
        $data['documento'] = \App\Support\TipoDocumento::normalizar($data['documento'] ?? '');

        return Validator::make($data, [
            // Óptica a la que pertenece el usuario (solo ópticas activas)
            'nit_empresa' => ['required', 'string', Rule::exists('empresa', 'nit')->where('estado', 'activa')],
            'id_tipo_docu' => ['required', 'integer', 'exists:tipo_documento,id_tipo_docu'],
            'documento' => ['required', 'string', 'max:20', new \App\Rules\DocumentoValido($data['id_tipo_docu'] ?? null), 'unique:usuario,documento', 'unique:optometra,doc_optometra'],
            'nombres' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:20', 'unique:usuario,telefono'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:usuario,email', 'unique:optometra,correo'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // No basta con el JS del modal -- si alguien manda el formulario
            // sin pasar por ahí (por ejemplo, a mano), el servidor lo rechaza.
            'acepto_terminos' => ['required', 'accepted'],
        ], [
            'nit_empresa.required' => 'Selecciona la óptica a la que perteneces.',
            'nit_empresa.exists' => 'La óptica seleccionada no está disponible.',
            'acepto_terminos.required' => 'Debes aceptar los Términos y Condiciones para registrarte.',
            'acepto_terminos.accepted' => 'Debes aceptar los Términos y Condiciones para registrarte.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return Usuario
     */
    protected function create(array $data)
    {
        $campos = [
            'documento' => \App\Support\TipoDocumento::normalizar($data['documento']),
            'nombres' => $data['nombres'],
            'apellido' => $data['apellido'],
            'telefono' => $data['telefono'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'fecha_creacion' => now(),
            'id_rol' => 2, // Cliente
            'id_tipo_docu' => $data['id_tipo_docu'] ?? 1,
            'id_estado' => 1, // Activo
        ];

        // Óptica elegida en el registro: separa los datos de cada óptica por nit_empresa
        if (Schema::hasColumn('usuario', 'nit_empresa')) {
            $campos['nit_empresa'] = $data['nit_empresa'];
        }

        return Usuario::create($campos);
    }
}
