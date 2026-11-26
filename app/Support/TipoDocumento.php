<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reglas de cada tipo de documento (cédula, TI, pasaporte...).
 *
 * La fuente es la tabla tipo_documento (columnas patron, long_min, long_max, solo_numeros, ayuda),
 * que agrega el script database/sql/visioptica_final.sql. La validación la hace Laravel
 * (App\Rules\DocumentoValido) en todos los formularios donde se escribe un documento.
 *
 * Si la base todavía no tiene esas columnas, se usan los valores de POR_DEFECTO.
 */
class TipoDocumento
{
    /** id_tipo_docu => [patrón, mínimo, máximo, solo números, ayuda] */
    public const POR_DEFECTO = [
        1 => ['^[0-9]{6,10}$', 6, 10, true, 'Cédula de ciudadanía: solo números, de 6 a 10 dígitos.'],
        2 => ['^[0-9]{10,11}$', 10, 11, true, 'Tarjeta de identidad: solo números, 10 u 11 dígitos.'],
        3 => ['^[0-9]{6,10}$', 6, 10, true, 'Cédula de extranjería: solo números, de 6 a 10 dígitos.'],
        4 => ['^[A-Z0-9]{6,12}$', 6, 12, false, 'Pasaporte: letras y números, de 6 a 12 caracteres, sin espacios.'],
        5 => ['^[0-9]{9,10}$', 9, 10, true, 'NIT: 9 dígitos y, si quiere, el dígito de verificación (sin guion).'],
        6 => ['^[0-9]{10,11}$', 10, 11, true, 'Registro civil (NUIP): solo números, 10 u 11 dígitos.'],
    ];

    /** Quita espacios, puntos y guiones y pasa a mayúsculas: "1.020.304-5" -> "10203045", "av 12345" -> "AV12345". */
    public static function normalizar(?string $documento): string
    {
        return strtoupper(preg_replace('/[\s.\-]/u', '', (string) $documento));
    }

    /** Todas las reglas, con nombre: id => ['nombre', 'patron', 'min', 'max', 'numeros', 'ayuda']. */
    public static function reglas(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $conColumnas = Schema::hasColumn('tipo_documento', 'patron');
        $filas = DB::table('tipo_documento')->orderBy('id_tipo_docu')->get();

        $cache = [];
        foreach ($filas as $f) {
            $def = self::POR_DEFECTO[$f->id_tipo_docu] ?? ['^[A-Z0-9]{4,20}$', 4, 20, false, 'Letras y números, de 4 a 20 caracteres.'];
            $cache[(int) $f->id_tipo_docu] = [
                'nombre' => $f->nom_ti_docu,
                'patron' => $conColumnas && $f->patron ? $f->patron : $def[0],
                'min' => (int) ($conColumnas && $f->long_min ? $f->long_min : $def[1]),
                'max' => (int) ($conColumnas && $f->long_max ? $f->long_max : $def[2]),
                'numeros' => (bool) ($conColumnas ? $f->solo_numeros : $def[3]),
                'ayuda' => $conColumnas && $f->ayuda ? $f->ayuda : $def[4],
            ];
        }

        return $cache;
    }

    /** null si el documento es válido para ese tipo; si no, el mensaje para el usuario. */
    public static function error($idTipo, ?string $documento): ?string
    {
        $regla = self::reglas()[(int) $idTipo] ?? null;
        if (! $regla) {
            return 'Selecciona un tipo de documento válido.';
        }

        $doc = self::normalizar($documento);
        if ($doc === '') {
            return 'Escribe el número de documento.';
        }
        if ($regla['numeros'] && ! ctype_digit($doc)) {
            return "{$regla['nombre']}: solo se permiten números.";
        }
        if (! $regla['numeros'] && ! ctype_alnum($doc)) {
            return "{$regla['nombre']}: solo letras y números, sin espacios ni símbolos.";
        }
        $largo = strlen($doc);
        if ($largo < $regla['min'] || $largo > $regla['max']) {
            $rango = $regla['min'] === $regla['max'] ? $regla['min'] : "{$regla['min']} a {$regla['max']}";

            return "{$regla['nombre']}: debe tener {$rango} " . ($regla['numeros'] ? 'dígitos' : 'caracteres') . " (escribiste {$largo}).";
        }
        if (! preg_match('/' . str_replace('/', '\/', $regla['patron']) . '/', $doc)) {
            return $regla['ayuda'];
        }

        return null;
    }

    /** Para los formularios: las reglas en JSON que usa resources/views/partials/documento-js.blade.php. */
    public static function paraJs(): array
    {
        return collect(self::reglas())->map(fn ($r) => [
            'min' => $r['min'], 'max' => $r['max'], 'numeros' => $r['numeros'], 'ayuda' => $r['ayuda'],
        ])->all();
    }
}
