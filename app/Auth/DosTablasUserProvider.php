<?php

namespace App\Auth;

use App\Models\Optometra;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Login en dos tablas (config/auth.php → providers.users.driver = 'dos_tablas').
 *
 *   1. Busca el correo en `usuario` (administradores, recepción, vendedores, clientes, superadmin).
 *   2. Si no está ahí, lo busca en `optometra` (columna correo).
 *   3. Si tampoco está, la persona no tiene cuenta.
 *
 * Un documento o un correo no se repite entre las dos tablas: lo validan el registro, Equipo,
 * recepción, contratar y el panel del administrador, así que nunca hay ambigüedad.
 */
class DosTablasUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier)
    {
        return parent::retrieveById($identifier) ?? Optometra::find($identifier);
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        $user = parent::retrieveByToken($identifier, $token);
        if ($user) {
            return $user;
        }

        $optometra = Optometra::find($identifier);
        $guardado = $optometra?->getRememberToken();

        return $guardado && hash_equals($guardado, $token) ? $optometra : null;
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        $user = parent::retrieveByCredentials($credentials);
        if ($user || empty($credentials['email'])) {
            return $user;
        }

        $optometra = Optometra::where('correo', $credentials['email'])->first();

        // Un optómetra registrado sin contraseña todavía no tiene cuenta.
        return $optometra && $optometra->tieneCuenta() ? $optometra : null;
    }

    /** Busca a la persona dueña de un correo en las dos tablas (null si no tiene cuenta). */
    public static function porCorreo(?string $correo): ?\App\Models\Usuario
    {
        $correo = trim((string) $correo);
        if ($correo === '') {
            return null;
        }

        return \App\Models\Usuario::where('email', $correo)->first()
            ?? Optometra::where('correo', $correo)->get()->first(fn ($o) => $o->tieneCuenta());
    }
}
