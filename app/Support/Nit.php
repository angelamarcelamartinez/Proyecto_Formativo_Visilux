<?php

// Clase de apoyo (app/Support): funciones estáticas reutilizables, no es un modelo ni un controlador.
namespace App\Support;

/**
 * NIT de persona jurídica en Colombia: 9 dígitos + 1 dígito de verificación (DV).
 *
 *   En pantalla:          800000000-9
 *   En la base de datos:  8000000009   (BIGINT, sin guion)
 *
 * Solo se revisa el formato: que queden 10 dígitos en total.
 */
// NIT colombiano = 9 dígitos + 1 dígito de verificación. En pantalla lleva guion; en la base de datos se guarda como número entero.
class Nit
{
    /** Deja solo los dígitos: "800000000-9" → "8000000009". */
    // Quita todo lo que no sea dígito.
    public static function normalizar(?string $nit): string
    {
        // preg_replace con /\D/ reemplaza cualquier carácter NO numérico por nada. (string) convierte null en texto vacío.
        return preg_replace('/\D/', '', (string) $nit);
    }

    /** 8000000009 → "800000000-9" (si no tiene 10 dígitos lo devuelve tal cual). */
    // Operación inversa: pone el guion antes del último dígito para mostrar el NIT.
    public static function formatear(int|string|null $nit): string
    {
        // Primero deja solo dígitos (por si llega con guion o puntos).
        $nit = self::normalizar((string) $nit);

        // Si no tiene exactamente 10 dígitos no se puede formatear: lo devuelve como está.
        if (strlen($nit) !== 10) {
            return $nit;
        }

        // Primeros 9 dígitos + guion + el último dígito (el de verificación).
        return substr($nit, 0, 9) . '-' . substr($nit, -1);
    }

    /** Reglas de Laravel para el NIT ya normalizado: 9 dígitos + DV = 10 dígitos. */
    // Reglas de validación de Laravel: obligatorio y exactamente 10 dígitos (digits:10). Solo valida formato, no consulta ninguna entidad.
    public static function reglas(): array
    {
        return ['required', 'digits:10'];
    }

    // Mensajes en español para esas reglas. El texto de la llave es "campo.regla".
    public static function mensajes(string $campo = 'nit'): array
    {
        return [
            "{$campo}.required" => 'Escribe el NIT.',
            "{$campo}.digits" => 'El NIT debe tener 9 dígitos, guion y el dígito de verificación (ej. 800197268-4).',
        ];
    }
}
