<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * APIs externas (públicas y sin clave) que usa el sitio:
 *   1. Departamentos y ciudades de Colombia · api-colombia.com   → formulario "Contratar".
 *   2. Índice UV de Ibagué                  · open-meteo.com     → sección de rayos UV del inicio.
 *   3. Festivos de Colombia                 · date.nager.at      → calendarios de citas (no se atienden festivos).
 *
 * La conexión usa la misma función consultarApi($url) de public/apis/consultar_api.php
 * (así la lógica de cURL está en un solo lugar). Las respuestas buenas se guardan en caché
 * para no llamar la API en cada visita; si una API no responde se devuelve null
 * (los festivos, en cambio, se calculan localmente con la ley colombiana).
 */
class ApisExternas
{
    /** De dónde salieron los últimos festivos: 'api' (date.nager.at) o 'local' (calculados). */
    public static ?string $fuenteFestivos = null;

    protected const URL_DEPARTAMENTOS = 'https://api-colombia.com/api/v1/Department';
    protected const URL_UV = 'https://api.open-meteo.com/v1/forecast?latitude=4.4389&longitude=-75.2322&timezone=America/Bogota&forecast_days=1&current=uv_index&daily=uv_index_max';
    protected const URL_FESTIVOS = 'https://date.nager.at/api/v3/PublicHolidays/%d/CO';

    /** Llama la API y devuelve el JSON como arreglo (o null si falló). Solo guarda en caché las respuestas buenas. */
    protected static function consultar(string $url, int $segundosCache): ?array
    {
        $clave = 'api_externa:' . md5($url);

        if (is_array($guardado = Cache::get($clave))) {
            return $guardado;
        }

        require_once public_path('apis/consultar_api.php');

        try {
            $datos = consultarApi($url);
        } catch (\Throwable $e) {
            $datos = null;
        }

        if (! is_array($datos) || $datos === []) {
            return null;
        }

        Cache::put($clave, $datos, $segundosCache);

        return $datos;
    }

    /** Texto para comparar sin tildes ni mayúsculas: "Bogotá, D.C." → "bogota, d.c." */
    public static function plano(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return preg_replace('/\s+/u', ' ', $texto);
    }

    // ------------------------------------------------------------------
    // 1. Departamentos y ciudades de Colombia
    // ------------------------------------------------------------------

    /** [['id' => 5, 'nombre' => 'Antioquia'], ...] ordenados por nombre, o null si la API no respondió. */
    public static function departamentos(): ?array
    {
        $datos = self::consultar(self::URL_DEPARTAMENTOS, 86400);
        if ($datos === null) {
            return null;
        }

        $lista = [];
        foreach ($datos as $d) {
            if (isset($d['id'], $d['name'])) {
                $lista[] = ['id' => (int) $d['id'], 'nombre' => (string) $d['name']];
            }
        }
        usort($lista, fn ($a, $b) => strcmp(self::plano($a['nombre']), self::plano($b['nombre'])));

        return $lista ?: null;
    }

    /** Departamento por su nombre (sin importar tildes ni mayúsculas), o null. */
    public static function buscarDepartamento(string $nombre): ?array
    {
        $buscado = self::plano($nombre);

        foreach (self::departamentos() ?? [] as $d) {
            if (self::plano($d['nombre']) === $buscado) {
                return $d;
            }
        }

        return null;
    }

    /** Nombres de las ciudades de un departamento, ordenados, o null si la API no respondió. */
    public static function ciudades(int $idDepartamento): ?array
    {
        $datos = self::consultar(self::URL_DEPARTAMENTOS . '/' . $idDepartamento . '/cities', 86400);
        if ($datos === null) {
            return null;
        }

        $ciudades = [];
        foreach ($datos as $c) {
            if (! empty($c['name'])) {
                $ciudades[] = (string) $c['name'];
            }
        }
        $ciudades = array_values(array_unique($ciudades));
        usort($ciudades, fn ($a, $b) => strcmp(self::plano($a), self::plano($b)));

        return $ciudades;
    }

    // ------------------------------------------------------------------
    // 2. Índice UV (escala de la OMS)
    // ------------------------------------------------------------------

    /**
     * ['uv' => 7.5, 'nivel' => 'Alto', 'color' => '#ffe0b2', 'consejo' => '...', 'uv_max_hoy' => 9.1]
     * o null si open-meteo.com no respondió.
     */
    public static function uvActual(): ?array
    {
        $datos = self::consultar(self::URL_UV, 1800);
        if ($datos === null) {
            return null;
        }

        $actual = $datos['current']['uv_index'] ?? null;
        $maximo = $datos['daily']['uv_index_max'][0] ?? null;
        $uv = $actual ?? $maximo;

        if ($uv === null) {
            return null;
        }

        return ['uv' => round((float) $uv, 1), 'uv_max_hoy' => $maximo !== null ? round((float) $maximo, 1) : null]
            + self::nivelUv((float) $uv);
    }

    /** Clasificación de la OMS (la misma de public/apis/api_indice_uv.php). */
    public static function nivelUv(float $uv): array
    {
        return match (true) {
            $uv < 3 => ['nivel' => 'Bajo', 'color' => '#c8e6c9', 'consejo' => 'Sin riesgo especial.'],
            $uv < 6 => ['nivel' => 'Moderado', 'color' => '#fff9c4', 'consejo' => 'Usa gafas con filtro UV al aire libre.'],
            $uv < 8 => ['nivel' => 'Alto', 'color' => '#ffe0b2', 'consejo' => 'Gafas con filtro UV400 y sombrero.'],
            $uv < 11 => ['nivel' => 'Muy alto', 'color' => '#ffccbc', 'consejo' => 'Evita el sol del mediodía; usa gafas UV400 envolventes.'],
            default => ['nivel' => 'Extremo', 'color' => '#e1bee7', 'consejo' => 'Evita salir al sol; protección máxima para los ojos y la piel.'],
        };
    }

    // ------------------------------------------------------------------
    // 3. Festivos de Colombia
    // ------------------------------------------------------------------

    /**
     * Festivos entre dos fechas: ['2026-12-08' => 'Inmaculada Concepción', ...].
     * Primero se consulta date.nager.at; si no responde se calculan con la ley colombiana
     * (Ley Emiliani y fechas que dependen de la Pascua) y $fuenteFestivos queda en 'local'.
     */
    public static function festivosEntre(string $desde, string $hasta): ?array
    {
        try {
            $ini = Carbon::parse($desde)->startOfDay();
            $fin = Carbon::parse($hasta)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }

        $todos = [];
        $fuente = 'api';
        for ($anio = $ini->year; $anio <= $fin->year; $anio++) {
            $delAnio = self::festivosDelAnioApi($anio);
            if ($delAnio === null) {
                $delAnio = self::festivosDelAnioLocal($anio);
                $fuente = 'local';
            }
            $todos += $delAnio;
        }
        self::$fuenteFestivos = $fuente;

        $resultado = array_filter($todos, fn ($nombre, $fecha) => $fecha >= $ini->toDateString() && $fecha <= $fin->toDateString(), ARRAY_FILTER_USE_BOTH);
        ksort($resultado);

        return $resultado;
    }

    /** ['festivo' => true|false, 'nombre' => 'Navidad'|null] de una fecha (o null si la fecha no es válida). */
    public static function esFestivo(string $fecha): ?array
    {
        try {
            $dia = Carbon::parse($fecha)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }

        $festivos = self::festivosEntre($dia, $dia);
        if ($festivos === null) {
            return null;
        }

        return ['festivo' => isset($festivos[$dia]), 'nombre' => $festivos[$dia] ?? null];
    }

    protected static function festivosDelAnioApi(int $anio): ?array
    {
        $datos = self::consultar(sprintf(self::URL_FESTIVOS, $anio), 86400 * 7);
        if ($datos === null) {
            return null;
        }

        $lista = [];
        foreach ($datos as $f) {
            if (! empty($f['date'])) {
                $lista[$f['date']] = $f['localName'] ?? $f['name'] ?? 'Festivo';
            }
        }

        return $lista ?: null;
    }

    /** Festivos de Colombia calculados (Ley 51 de 1983, "Ley Emiliani"). */
    public static function festivosDelAnioLocal(int $anio): array
    {
        $f = [];
        $fijo = fn (int $mes, int $dia) => Carbon::create($anio, $mes, $dia);
        // Se pasa al lunes siguiente si no cae en lunes.
        $lunes = fn (Carbon $d) => $d->isMonday() ? $d : $d->copy()->next(Carbon::MONDAY);

        $f[$fijo(1, 1)->toDateString()] = 'Año Nuevo';
        $f[$lunes($fijo(1, 6))->toDateString()] = 'Día de los Reyes Magos';
        $f[$lunes($fijo(3, 19))->toDateString()] = 'Día de San José';
        $f[$fijo(5, 1)->toDateString()] = 'Día del Trabajo';
        $f[$lunes($fijo(6, 29))->toDateString()] = 'San Pedro y San Pablo';
        $f[$fijo(7, 20)->toDateString()] = 'Día de la Independencia';
        $f[$fijo(8, 7)->toDateString()] = 'Batalla de Boyacá';
        $f[$lunes($fijo(8, 15))->toDateString()] = 'La Asunción de la Virgen';
        $f[$lunes($fijo(10, 12))->toDateString()] = 'Día de la Raza';
        $f[$lunes($fijo(11, 1))->toDateString()] = 'Todos los Santos';
        $f[$lunes($fijo(11, 11))->toDateString()] = 'Independencia de Cartagena';
        $f[$fijo(12, 8)->toDateString()] = 'Inmaculada Concepción';
        $f[$fijo(12, 25)->toDateString()] = 'Navidad';

        // Fechas que dependen del Domingo de Pascua
        $pascua = self::domingoDePascua($anio);
        $f[$pascua->copy()->subDays(3)->toDateString()] = 'Jueves Santo';
        $f[$pascua->copy()->subDays(2)->toDateString()] = 'Viernes Santo';
        $f[$pascua->copy()->addDays(43)->toDateString()] = 'Ascensión del Señor';
        $f[$pascua->copy()->addDays(64)->toDateString()] = 'Corpus Christi';
        $f[$pascua->copy()->addDays(71)->toDateString()] = 'Sagrado Corazón de Jesús';

        ksort($f);

        return $f;
    }

    /** Domingo de Pascua (algoritmo de Meeus/Jones/Butcher, calendario gregoriano). */
    protected static function domingoDePascua(int $anio): Carbon
    {
        $a = $anio % 19;
        $b = intdiv($anio, 100);
        $c = $anio % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($anio, $mes, $dia)->startOfDay();
    }
}
