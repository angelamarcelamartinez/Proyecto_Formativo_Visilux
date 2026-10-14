<?php

namespace App\Support;

/**
 * NIT de persona jurídica en Colombia: 9 dígitos + 1 dígito de verificación (DV).
 *
 *   En pantalla:          800000000-9
 *   En la base de datos:  8000000009   (BIGINT, sin guion)
 *
 * Solo se revisa el formato: que queden 10 dígitos en total.
 */
class Nit
{
    /** Deja solo los dígitos: "800000000-9" → "8000000009". */
    public static function normalizar(?string $nit): string
    {
        return preg_replace('/\D/', '', (string) $nit);
    }

    /** 8000000009 → "800000000-9" (si no tiene 10 dígitos lo devuelve tal cual). */
    public static function formatear(int|string|null $nit): string
    {
        $nit = self::normalizar((string) $nit);

        if (strlen($nit) !== 10) {
            return $nit;
        }

        return substr($nit, 0, 9) . '-' . substr($nit, -1);
    }

    /** Reglas de Laravel para el NIT ya normalizado: 9 dígitos + DV = 10 dígitos. */
    public static function reglas(): array
    {
        return ['required', 'digits:10'];
    }

    public static function mensajes(string $campo = 'nit'): array
    {
        return [
            "{$campo}.required" => 'Escribe el NIT.',
            "{$campo}.digits" => 'El NIT debe tener 9 dígitos, guion y el dígito de verificación (ej. 800197268-4).',
        ];
    }
}
