<?php

// Carpeta (espacio de nombres) de esta clase: app/Support, donde van las clases de ayuda del proyecto.
namespace App\Support;

// Cache de Laravel: guarda datos un tiempo para no volver a pedirlos (aquí se guarda en la tabla `cache`).
use Illuminate\Support\Facades\Cache;

/**
 * Las 3 APIs externas que usa el sitio, con los MISMOS endpoints de public/apis/:
 *   - Departamentos y ciudades · https://api-colombia.com/api/v1/Department         (registro de la óptica en /planes/{plan}/contratar)
 *   - Índice UV de Ibagué      · https://api.open-meteo.com/v1/forecast             (sección de rayos UV del inicio)
 *   - Festivos de Colombia     · https://date.nager.at/api/v3/PublicHolidays/{año}/CO (calendario de citas: los festivos no se atienden)
 *
 * La conexión con cURL es la función consultarApi($url) de public/apis/consultar_api.php: no se repite aquí.
 * Cada respuesta se guarda un rato en caché para no llamar a la API en cada visita.
 * Si una API falla o no responde lo esperado, el método devuelve null y la pantalla muestra un aviso.
 */
// Declaramos la clase. Todos sus métodos son "static": se usan sin crear objeto, por ejemplo ApisExternas::departamentos().
class ApisExternas
{
    // Coordenadas de Ibagué (las mismas de public/apis/api_indice_uv.php).
    // Latitud de Ibagué (constante: no cambia nunca).
    public const LATITUD = 4.4389;
    // Longitud de Ibagué.
    public const LONGITUD = -75.2322;

    /**
     * Prefijo de las claves de caché. Es propio de esta clase para no chocar con datos viejos:
     * el ApiController anterior guardaba la respuesta CRUDA de la API con la clave "api_festivos_2026",
     * y si se leía esa clave aquí los festivos quedaban vacíos (por eso no salían en el calendario).
     */
    // Todas las claves de caché de esta clase empiezan con este texto (por ejemplo visilux_apis_v2_festivos_2026).
    protected const CACHE = 'visilux_apis_v2_';

    /**
     * Solo para las pruebas: si se asigna una función, se usa en lugar de cURL
     * (así las pruebas no dependen de internet). En el sitio real siempre es null.
     */
    // Variable de la clase que puede guardar una función (Closure) o null. Empieza en null.
    public static ?\Closure $falsa = null;

    /** Llama a la API: usa consultarApi() de public/apis/consultar_api.php. */
    // Recibe la URL de la API y devuelve lo que responda (arreglo) o null si falla.
    protected static function consultar(string $url): mixed
    {
        // Si estamos en una prueba y hay una función falsa asignada...
        if (self::$falsa) {
            // ...se usa esa función en vez de ir a internet.
            return (self::$falsa)($url);
        // Fin del if.
        }

        // Trae el archivo que tiene la función consultarApi() (require_once = solo lo carga una vez).
        require_once public_path('apis/consultar_api.php');

        // Llama a la API con cURL y devuelve el JSON ya convertido en arreglo (o null si falló).
        return consultarApi($url);
    // Fin del método consultar.
    }

    /** Guarda en caché solo las respuestas buenas; si la API falla devuelve null y no se guarda nada. */
    // Recibe: el nombre de la clave, cuántos minutos guardarla y una función que trae los datos de la API.
    protected static function enCache(string $clave, int $minutos, callable $obtener): mixed
    {
        // Busca si ya hay algo guardado en la caché con esa clave.
        $guardado = Cache::get($clave);
        // Si sí había algo guardado...
        if ($guardado !== null) {
            // ...lo devuelve sin llamar a la API.
            return $guardado;
        // Fin del if.
        }

        // No había nada: ejecuta la función que consulta la API.
        $datos = $obtener();
        // Si la API respondió bien (no es null)...
        if ($datos !== null) {
            // ...guarda los datos en la caché por los minutos indicados.
            Cache::put($clave, $datos, now()->addMinutes($minutos));
        // Fin del if. (Si falló, no se guarda nada y la próxima vez se intenta de nuevo.)
        }

        // Devuelve los datos (o null si la API falló).
        return $datos;
    // Fin del método enCache.
    }

    // ------------------------------------------------------------------
    // 1. Departamentos y ciudades de Colombia (api-colombia.com)
    // ------------------------------------------------------------------

    /** Lista de departamentos [['id' => 23, 'nombre' => 'Tolima'], ...] ordenada por nombre, o null si la API falla. */
    // ?array = devuelve un arreglo o null.
    public static function departamentos(): ?array
    {
        // Usa la caché con la clave "visilux_apis_v2_departamentos" por 1 día (60 minutos x 24 horas).
        return self::enCache(self::CACHE . 'departamentos', 60 * 24, function () {
            // Pide a la API la lista de departamentos.
            $datos = self::consultar('https://api-colombia.com/api/v1/Department');

            // La API devuelve directamente una lista; cada departamento trae 'id' y 'name'.
            // Si no es un arreglo, viene vacío o el primero no tiene id y name, la respuesta no sirve...
            if (! is_array($datos) || count($datos) === 0 || ! isset($datos[0]['id'], $datos[0]['name'])) {
                // ...y se devuelve null (la API "falló").
                return null;
            // Fin del if.
            }

            // Arreglo donde vamos a guardar solo lo que necesitamos.
            $lista = [];
            // Recorre cada departamento que mandó la API.
            foreach ($datos as $d) {
                // Si ese departamento trae id y nombre...
                if (isset($d['id'], $d['name'])) {
                    // ...lo agrega a la lista con nuestras claves: id (número) y nombre (texto sin espacios de más).
                    $lista[] = ['id' => (int) $d['id'], 'nombre' => trim((string) $d['name'])];
                // Fin del if.
                }
            // Fin del foreach.
            }
            // Ordena la lista alfabéticamente por nombre, sin importar tildes ni mayúsculas.
            usort($lista, fn ($a, $b) => strcmp(self::plano($a['nombre']), self::plano($b['nombre'])));

            // Devuelve la lista ya ordenada (y enCache la guarda 1 día).
            return $lista;
        // Fin de la función que se le pasa a enCache.
        });
    // Fin del método departamentos.
    }

    /** Nombres de las ciudades (municipios) de un departamento, ordenados, o null si la API falla. */
    // Recibe el id del departamento (por ejemplo 23 = Tolima).
    public static function ciudades(int $idDepartamento): ?array
    {
        // Caché por 1 día con una clave distinta para cada departamento (visilux_apis_v2_ciudades_23).
        return self::enCache(self::CACHE . 'ciudades_' . $idDepartamento, 60 * 24, function () use ($idDepartamento) {
            // Pide a la API las ciudades de ese departamento.
            $datos = self::consultar('https://api-colombia.com/api/v1/Department/' . $idDepartamento . '/cities');

            // También es una lista; cada ciudad trae 'name'.
            // Si la respuesta no es una lista con nombres, no sirve...
            if (! is_array($datos) || count($datos) === 0 || ! isset($datos[0]['name'])) {
                // ...y se devuelve null.
                return null;
            // Fin del if.
            }

            // Arreglo para guardar solo los nombres.
            $nombres = [];
            // Recorre cada ciudad que mandó la API.
            foreach ($datos as $c) {
                // Si la ciudad trae nombre (no vacío)...
                if (! empty($c['name'])) {
                    // ...agrega el nombre a la lista.
                    $nombres[] = trim((string) $c['name']);
                // Fin del if.
                }
            // Fin del foreach.
            }
            // Quita nombres repetidos (array_unique) y vuelve a numerar la lista desde 0 (array_values).
            $nombres = array_values(array_unique($nombres));
            // Ordena alfabéticamente sin importar tildes ni mayúsculas.
            usort($nombres, fn ($a, $b) => strcmp(self::plano($a), self::plano($b)));

            // Devuelve la lista de ciudades.
            return $nombres;
        // Fin de la función que se le pasa a enCache.
        });
    // Fin del método ciudades.
    }

    /** Busca un departamento por nombre (sin importar tildes ni mayúsculas). */
    // Recibe un nombre (por ejemplo "tolima") y devuelve ['id' => 23, 'nombre' => 'Tolima'] o null.
    public static function buscarDepartamento(string $nombre): ?array
    {
        // Recorre la lista de departamentos (si la API falló, ?? [] usa una lista vacía).
        foreach (self::departamentos() ?? [] as $d) {
            // Compara los dos nombres sin tildes y en minúsculas.
            if (self::plano($d['nombre']) === self::plano($nombre)) {
                // Si coinciden, devuelve ese departamento.
                return $d;
            // Fin del if.
            }
        // Fin del foreach.
        }

        // Si no encontró ninguno, devuelve null.
        return null;
    // Fin del método buscarDepartamento.
    }

    // ------------------------------------------------------------------
    // 2. Índice UV de Ibagué (open-meteo.com)
    // ------------------------------------------------------------------

    /**
     * Índice UV actual de Ibagué con su nivel, color y consejo:
     * ['uv' => 7.2, 'nivel' => 'Alto', 'color' => '#ffe0b2', 'consejo' => '...', 'hora' => '2026-11-23T13:00'], o null si la API falla.
     */
    // Devuelve los datos del UV de ahora o null.
    public static function uvActual(): ?array
    {
        // Caché de solo 10 minutos, porque el UV cambia durante el día.
        return self::enCache(self::CACHE . 'uv_actual', 10, function () {
            // Arma la URL con las coordenadas de Ibagué, la zona horaria de Colombia, 1 día de pronóstico, el UV actual y el máximo del día.
            $url = 'https://api.open-meteo.com/v1/forecast?latitude=' . self::LATITUD . '&longitude=' . self::LONGITUD
                // Segunda parte de la URL (el punto . une textos).
                . '&timezone=America/Bogota&forecast_days=1&current=uv_index&daily=uv_index_max';
            // Llama a la API.
            $datos = self::consultar($url);

            // La respuesta trae 'current' => ['time' => ..., 'uv_index' => ...] y 'daily' => ['uv_index_max' => [...]].
            // Si no llegó el UV actual o no es un número, la respuesta no sirve...
            if (! is_array($datos) || ! isset($datos['current']['uv_index']) || ! is_numeric($datos['current']['uv_index'])) {
                // ...y se devuelve null.
                return null;
            // Fin del if.
            }

            // UV actual redondeado a 1 decimal (por ejemplo 7.25 → 7.3).
            $uv = round((float) $datos['current']['uv_index'], 1);
            // UV máximo de hoy (primer valor de la lista diaria); null si no vino.
            $max = $datos['daily']['uv_index_max'][0] ?? null;

            // Junta (+) el nivel, color y consejo de nivelUv() con estos otros datos.
            return self::nivelUv($uv) + [
                // El UV actual.
                'uv' => $uv,
                // El máximo de hoy redondeado, o null si no es un número.
                'uv_max_hoy' => is_numeric($max) ? round((float) $max, 1) : null,
                // La hora de la medición que mandó la API.
                'hora' => $datos['current']['time'] ?? null,
            // Cierra el arreglo.
            ];
        // Fin de la función que se le pasa a enCache.
        });
    // Fin del método uvActual.
    }

    /** Clasificación de la OMS (la misma tabla de colores de public/apis/api_indice_uv.php). */
    // Recibe el número del UV y devuelve el nivel, el color y el consejo.
    public static function nivelUv(float $uv): array
    {
        // Menos de 3: bajo.
        if ($uv < 3) {
            // Verde.
            return ['nivel' => 'Bajo', 'color' => '#c8e6c9', 'consejo' => 'Sin riesgo especial. Usa gafas de sol si hay mucho brillo.'];
        // De 3 a menos de 6: moderado.
        } elseif ($uv < 6) {
            // Amarillo.
            return ['nivel' => 'Moderado', 'color' => '#fff9c4', 'consejo' => 'Usa gafas con filtro UV si vas a estar al aire libre.'];
        // De 6 a menos de 8: alto.
        } elseif ($uv < 8) {
            // Naranja.
            return ['nivel' => 'Alto', 'color' => '#ffe0b2', 'consejo' => 'Usa gafas con filtro UV400 y sombrero.'];
        // De 8 a menos de 11: muy alto.
        } elseif ($uv < 11) {
            // Rojo.
            return ['nivel' => 'Muy alto', 'color' => '#ffcdd2', 'consejo' => 'Gafas UV400 obligatorias y evita el sol del mediodía.'];
        // Fin de los if.
        }

        // 11 o más: extremo (morado).
        return ['nivel' => 'Extremo', 'color' => '#e1bee7', 'consejo' => 'Evita el sol directo; gafas UV400 y protección total.'];
    // Fin del método nivelUv.
    }

    // ------------------------------------------------------------------
    // 3. Festivos de Colombia (date.nager.at)
    // ------------------------------------------------------------------

    /**
     * De dónde salieron los últimos festivos consultados:
     * 'api' = date.nager.at respondió bien; 'local' = la API falló y se usó la lista calculada aquí (festivosLocales).
     */
    // Variable de texto de la clase; el calendario la lee para mostrar el aviso cuando vale 'local'.
    public static string $fuenteFestivos = 'api';

    /**
     * Festivos de un año: ['2026-01-01' => 'Año Nuevo', ...].
     * Primero se pregunta a la API; si no responde, se usa la lista local de Colombia (Ley Emiliani)
     * y $fuenteFestivos queda en 'local' para que la pantalla lo avise.
     */
    // Recibe el año (por ejemplo 2026).
    public static function festivos(int $anio): ?array
    {
        // Pide los festivos a la API (o a la caché).
        $api = self::festivosApi($anio);
        // Debe ser ['Y-m-d' => 'nombre']; si la caché trae otra forma, se descarta y se vuelve a consultar.
        // Si hay datos pero la primera clave NO tiene forma de fecha (2026-01-01)...
        if ($api !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) array_key_first($api))) {
            // ...borra esa caché mala...
            Cache::forget(self::CACHE . 'festivos_' . $anio);
            // ...y vuelve a preguntar a la API.
            $api = self::festivosApi($anio);
        // Fin del if.
        }
        // Si la API respondió bien...
        if ($api !== null) {
            // ...anota que los festivos vienen de la API...
            self::$fuenteFestivos = 'api';

            // ...y los devuelve.
            return $api;
        // Fin del if.
        }

        // Si llegamos aquí la API falló: anota que se usa la lista local.
        self::$fuenteFestivos = 'local';

        // Devuelve los festivos calculados por el sistema.
        return self::festivosLocales($anio);
    // Fin del método festivos.
    }

    /** Solo la API date.nager.at (null si falla). La respuesta buena se guarda 1 día en caché. */
    // Recibe el año.
    public static function festivosApi(int $anio): ?array
    {
        // Caché de 1 día con una clave por año (visilux_apis_v2_festivos_2026).
        return self::enCache(self::CACHE . 'festivos_' . $anio, 60 * 24, function () use ($anio) {
            // Pide a la API los festivos de Colombia (CO) de ese año.
            $datos = self::consultar('https://date.nager.at/api/v3/PublicHolidays/' . $anio . '/CO');

            // La API devuelve una lista; cada festivo trae 'date' (Y-m-d) y 'localName'.
            // Si no es una lista con fechas, la respuesta no sirve...
            if (! is_array($datos) || count($datos) === 0 || ! isset($datos[0]['date'])) {
                // ...y se devuelve null.
                return null;
            // Fin del if.
            }

            // Arreglo fecha → nombre.
            $festivos = [];
            // Recorre cada festivo que mandó la API.
            foreach ($datos as $f) {
                // Si trae fecha...
                if (! empty($f['date'])) {
                    // ...guarda: clave = la fecha, valor = el nombre en español (o en inglés, o "Festivo").
                    $festivos[$f['date']] = $f['localName'] ?? ($f['name'] ?? 'Festivo');
                // Fin del if.
                }
            // Fin del foreach.
            }

            // Devuelve, por ejemplo, ['2026-12-25' => 'Navidad', ...].
            return $festivos;
        // Fin de la función que se le pasa a enCache.
        });
    // Fin del método festivosApi.
    }

    /**
     * Respaldo: festivos de Colombia calculados sin internet (Ley 51 de 1983, "Ley Emiliani").
     *  - Fijos: 1 ene, 1 may, 20 jul, 7 ago, 8 dic, 25 dic.
     *  - Se pasan al lunes siguiente: 6 ene, 19 mar, 29 jun, 15 ago, 12 oct, 1 nov, 11 nov.
     *  - Según la Pascua: Jueves y Viernes Santo, y (pasados a lunes) Ascensión, Corpus Christi y Sagrado Corazón.
     */
    // Recibe el año y siempre devuelve un arreglo (no depende de internet).
    public static function festivosLocales(int $anio): array
    {
        // Arreglo donde se van guardando los festivos (fecha → nombre).
        $f = [];
        // Función de ayuda para agregar un festivo; "use (&$f)" le permite modificar el arreglo $f de afuera.
        $agregar = function (string $fecha, string $nombre) use (&$f) {
            // Guarda el festivo con la fecha como clave.
            $f[$fecha] = $nombre;
        // Fin de la función agregar.
        };
        // Pasa la fecha al lunes siguiente si no cae lunes.
        // Función de ayuda: recibe una fecha y la mueve al lunes siguiente (si ya es lunes, la deja igual).
        $aLunes = function (\DateTime $d): \DateTime {
            // Mientras el día de la semana no sea 1 (lunes; format('N') da 1 = lunes ... 7 = domingo)...
            while ((int) $d->format('N') !== 1) {
                // ...avanza un día.
                $d->modify('+1 day');
            // Fin del while.
            }

            // Devuelve la fecha ya en lunes.
            return $d;
        // Fin de la función aLunes.
        };
        // Función corta que crea una fecha del año pedido, por ejemplo $dia(12, 25) = 25 de diciembre.
        $dia = fn (int $mes, int $d) => new \DateTime(sprintf('%04d-%02d-%02d', $anio, $mes, $d));

        // Fijos
        // 1 de enero.
        $agregar($dia(1, 1)->format('Y-m-d'), 'Año Nuevo');
        // 1 de mayo.
        $agregar($dia(5, 1)->format('Y-m-d'), 'Día del Trabajo');
        // 20 de julio.
        $agregar($dia(7, 20)->format('Y-m-d'), 'Día de la Independencia');
        // 7 de agosto.
        $agregar($dia(8, 7)->format('Y-m-d'), 'Batalla de Boyacá');
        // 8 de diciembre.
        $agregar($dia(12, 8)->format('Y-m-d'), 'Inmaculada Concepción');
        // 25 de diciembre.
        $agregar($dia(12, 25)->format('Y-m-d'), 'Navidad');

        // Ley Emiliani: se corren al lunes
        // 6 de enero → lunes siguiente.
        $agregar($aLunes($dia(1, 6))->format('Y-m-d'), 'Reyes Magos');
        // 19 de marzo → lunes siguiente.
        $agregar($aLunes($dia(3, 19))->format('Y-m-d'), 'San José');
        // 29 de junio → lunes siguiente.
        $agregar($aLunes($dia(6, 29))->format('Y-m-d'), 'San Pedro y San Pablo');
        // Solo desde 2026...
        if ($anio >= 2026) {
            // Nuevo desde 2026 (aparece en la API date.nager.at): 9 de julio, se pasa al lunes.
            $agregar($aLunes($dia(7, 9))->format('Y-m-d'), 'Día de la Virgen de Chiquinquirá');
        // Fin del if.
        }
        // 15 de agosto → lunes siguiente.
        $agregar($aLunes($dia(8, 15))->format('Y-m-d'), 'Asunción de la Virgen');
        // 12 de octubre → lunes siguiente.
        $agregar($aLunes($dia(10, 12))->format('Y-m-d'), 'Día de la Raza');
        // 1 de noviembre → lunes siguiente.
        $agregar($aLunes($dia(11, 1))->format('Y-m-d'), 'Todos los Santos');
        // 11 de noviembre → lunes siguiente.
        $agregar($aLunes($dia(11, 11))->format('Y-m-d'), 'Independencia de Cartagena');

        // Domingo de Pascua (algoritmo de Meeus/Jones/Butcher, calendario gregoriano)
        // Pasos del algoritmo (% = residuo de la división, intdiv = división entera).
        $a = $anio % 19; $b = intdiv($anio, 100); $c = $anio % 100;
        // Más pasos del algoritmo.
        $d = intdiv($b, 4); $e = $b % 4; $g = intdiv(8 * $b + 13, 25);
        // Más pasos del algoritmo.
        $h = (19 * $a + $b - $d - $g + 15) % 30; $i = intdiv($c, 4); $k = $c % 4;
        // Más pasos del algoritmo.
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7; $m = intdiv($a + 11 * $h + 22 * $l, 451);
        // Resultado: el mes y el día del Domingo de Pascua (en 2026: 5 de abril).
        $mes = intdiv($h + $l - 7 * $m + 114, 31); $diaPascua = (($h + $l - 7 * $m + 114) % 31) + 1;
        // Función corta: la fecha que queda X días antes (negativo) o después (positivo) de la Pascua.
        $pascua = fn (int $dias) => (clone $dia($mes, $diaPascua))->modify(($dias >= 0 ? '+' : '') . $dias . ' days');

        // 3 días antes de la Pascua.
        $agregar($pascua(-3)->format('Y-m-d'), 'Jueves Santo');
        // 2 días antes de la Pascua.
        $agregar($pascua(-2)->format('Y-m-d'), 'Viernes Santo');
        // 43 días después (ya cae en lunes).
        $agregar($pascua(43)->format('Y-m-d'), 'Ascensión del Señor');
        // 64 días después (lunes).
        $agregar($pascua(64)->format('Y-m-d'), 'Corpus Christi');
        // 71 días después (lunes).
        $agregar($pascua(71)->format('Y-m-d'), 'Sagrado Corazón');

        // Ordena los festivos por fecha (por la clave).
        ksort($f);

        // Devuelve la lista completa del año.
        return $f;
    // Fin del método festivosLocales.
    }

    /**
     * ¿La fecha (Y-m-d) es festivo en Colombia?
     * ['festivo' => true, 'nombre' => 'Navidad'] / ['festivo' => false, 'nombre' => null], o null si no se pudo consultar.
     */
    // Recibe una fecha como texto, por ejemplo '2026-12-25'.
    public static function esFestivo(string $fecha): ?array
    {
        // Saca el año de la fecha (los 4 primeros caracteres) y trae los festivos de ese año.
        $festivos = self::festivos((int) substr($fecha, 0, 4));
        // Si no hay lista (no debería pasar, porque existe el respaldo local)...
        if ($festivos === null) {
            // ...devuelve null.
            return null;
        // Fin del if.
        }

        // Devuelve si la fecha está en la lista (isset) y el nombre del festivo (o null si no lo es).
        return ['festivo' => isset($festivos[$fecha]), 'nombre' => $festivos[$fecha] ?? null];
    // Fin del método esFestivo.
    }

    /**
     * Festivos entre dos fechas (Y-m-d), para pintar el calendario: ['2026-12-08' => 'Inmaculada Concepción', ...].
     * Junta los años que haga falta (por ejemplo diciembre y enero). Devuelve null si la API no respondió.
     */
    // Recibe la fecha inicial y la final del calendario (hoy y hoy + 60 días).
    public static function festivosEntre(string $desde, string $hasta): ?array
    {
        // Lista final de festivos del rango.
        $lista = [];
        // Recorre cada año entre el año de $desde y el de $hasta (por ejemplo 2026 y 2027).
        for ($anio = (int) substr($desde, 0, 4); $anio <= (int) substr($hasta, 0, 4); $anio++) {
            // Trae los festivos de ese año.
            $festivos = self::festivos($anio);
            // Si no hay lista...
            if ($festivos === null) {
                // ...devuelve null.
                return null;
            // Fin del if.
            }
            // Recorre cada festivo del año (fecha → nombre).
            foreach ($festivos as $fecha => $nombre) {
                // Si la fecha está dentro del rango (los textos Y-m-d se pueden comparar como fechas)...
                if ($fecha >= $desde && $fecha <= $hasta) {
                    // ...lo agrega a la lista.
                    $lista[$fecha] = $nombre;
                // Fin del if.
                }
            // Fin del foreach.
            }
        // Fin del for.
        }

        // Devuelve solo los festivos del rango.
        return $lista;
    // Fin del método festivosEntre.
    }

    /** Texto en minúsculas y sin tildes, para comparar nombres ("Bogotá" = "bogota"). */
    // Recibe un texto y lo devuelve "plano".
    public static function plano(string $s): string
    {
        // Cambia cada letra con tilde o ñ por su letra simple (strtr reemplaza según la lista).
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            // Las mayúsculas con tilde también.
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n']);

        // Pasa todo a minúsculas (mb_ = funciona bien con letras en español) y quita espacios al inicio y al final.
        return trim(mb_strtolower($s));
    // Fin del método plano.
    }
// Fin de la clase.
}
