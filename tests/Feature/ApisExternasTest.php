<?php

namespace Tests\Feature;

use App\Support\ApisExternas;
use Tests\TestCase;

/**
 * Pruebas básicas de las 3 APIs externas integradas en el sitio.
 * No se conectan a internet: ApisExternas::$falsa reemplaza a cURL y devuelve
 * respuestas con el mismo formato que mandan las APIs reales.
 *
 * Ejecutar: php artisan test --filter=ApisExternasTest
 */
class ApisExternasTest extends TestCase
{
    /** URLs que se pidieron durante la prueba (para revisar que se usan los mismos endpoints de public/apis/). */
    protected array $pedidas = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Respuestas falsas con el formato real de cada API.
        ApisExternas::$falsa = function (string $url) {
            $this->pedidas[] = $url;

            if ($url === 'https://api-colombia.com/api/v1/Department') {
                return [
                    ['id' => 23, 'name' => 'Tolima', 'population' => 1339998],
                    ['id' => 2, 'name' => 'Antioquia', 'population' => 6407102],
                    ['id' => 5, 'name' => 'Bogotá', 'population' => 7181469],
                ];
            }
            if ($url === 'https://api-colombia.com/api/v1/Department/23/cities') {
                return [['id' => 1, 'name' => 'Ibagué'], ['id' => 2, 'name' => 'Espinal'], ['id' => 3, 'name' => 'Honda']];
            }
            if (str_starts_with($url, 'https://api.open-meteo.com/v1/forecast')) {
                return ['current' => ['time' => '2026-11-23T12:00', 'uv_index' => 7.25], 'daily' => ['time' => ['2026-11-23'], 'uv_index_max' => [9.1]]];
            }
            if ($url === 'https://date.nager.at/api/v3/PublicHolidays/2026/CO') {
                return [
                    ['date' => '2026-01-01', 'localName' => 'Año Nuevo', 'name' => "New Year's Day"],
                    ['date' => '2026-12-25', 'localName' => 'Navidad', 'name' => 'Christmas Day'],
                ];
            }

            return null; // cualquier otra URL: como si la API no respondiera
        };
    }

    protected function tearDown(): void
    {
        ApisExternas::$falsa = null;
        ApisExternas::$fuenteFestivos = 'api';
        parent::tearDown();
    }

    // ---------------- 1. Departamentos y ciudades ----------------

    public function test_carga_los_departamentos_ordenados(): void
    {
        $deptos = ApisExternas::departamentos();

        $this->assertSame(['Antioquia', 'Bogotá', 'Tolima'], array_column($deptos, 'nombre'));
        $this->assertSame(23, ApisExternas::buscarDepartamento('tolima')['id']);
        $this->assertSame(5, ApisExternas::buscarDepartamento('BOGOTA')['id']); // sin importar tildes ni mayúsculas
    }

    public function test_carga_las_ciudades_del_departamento(): void
    {
        $this->assertSame(['Espinal', 'Honda', 'Ibagué'], ApisExternas::ciudades(23));
        $this->assertContains('https://api-colombia.com/api/v1/Department/23/cities', $this->pedidas);
    }

    public function test_ruta_de_ciudades_devuelve_json(): void
    {
        $this->withoutMiddleware()
            ->getJson('/api-externa/departamentos/23/ciudades')
            ->assertOk()
            ->assertJson(['ciudades' => ['Espinal', 'Honda', 'Ibagué']]);
    }

    public function test_ruta_de_ciudades_avisa_si_la_api_falla(): void
    {
        // El departamento 999 no tiene respuesta falsa → la "API" no responde.
        $this->withoutMiddleware()
            ->getJson('/api-externa/departamentos/999/ciudades')
            ->assertStatus(502)
            ->assertJsonStructure(['error']);
    }

    // ---------------- 2. Festivos ----------------

    public function test_detecta_si_una_fecha_es_festivo(): void
    {
        $this->assertSame(['festivo' => true, 'nombre' => 'Navidad'], ApisExternas::esFestivo('2026-12-25'));
        $this->assertSame(['festivo' => false, 'nombre' => null], ApisExternas::esFestivo('2026-12-24'));
        $this->assertSame('api', ApisExternas::$fuenteFestivos);
    }

    public function test_festivos_entre_dos_fechas_y_agenda_bloqueada(): void
    {
        $this->assertSame(['2026-12-25' => 'Navidad'], ApisExternas::festivosEntre('2026-11-23', '2026-12-31'));
        $this->assertTrue(\App\Support\AgendaCitas::esFestivo(\Illuminate\Support\Carbon::parse('2026-12-25')));
        $this->assertFalse(\App\Support\AgendaCitas::esFestivo(\Illuminate\Support\Carbon::parse('2026-12-24')));
    }

    public function test_el_calendario_recibe_los_festivos_para_pintarlos_y_bloquearlos(): void
    {
        $html = view('includes.calendario-festivos', [
            'input' => 'n_fecha', 'fechaMin' => '2026-11-23', 'fechaMax' => '2027-01-22',
            'festivos' => ApisExternas::festivosEntre('2026-11-23', '2026-12-31'),
        ])->render();

        $this->assertStringContainsString('"2026-12-25":"Navidad"', $html);
        $this->assertStringContainsString('dia-festivo', $html);
        $this->assertStringNotContainsString('No se pudieron consultar los festivos', $html);
    }

    public function test_si_la_api_de_festivos_falla_usa_la_lista_local_y_lo_avisa(): void
    {
        // 2030 no tiene respuesta falsa → la "API" no responde → se usan los festivos calculados.
        $festivos = ApisExternas::festivosEntre('2030-12-01', '2030-12-31');

        $this->assertSame('local', ApisExternas::$fuenteFestivos);
        $this->assertSame(['2030-12-08' => 'Inmaculada Concepción', '2030-12-25' => 'Navidad'], $festivos);

        $html = view('includes.calendario-festivos', [
            'input' => 'n_fecha', 'fechaMin' => '2030-12-01', 'fechaMax' => '2030-12-31', 'festivos' => $festivos,
        ])->render();
        $this->assertStringContainsString('La API de festivos no respondió', $html);
        $this->assertStringContainsString('"2030-12-25":"Navidad"', $html);
    }

    public function test_la_lista_local_coincide_con_la_api_en_2026(): void
    {
        $this->assertSame([
            '2026-01-01', '2026-01-12', '2026-03-23', '2026-04-02', '2026-04-03', '2026-05-01',
            '2026-05-18', '2026-06-08', '2026-06-15', '2026-06-29', '2026-07-13', '2026-07-20', '2026-08-07',
            '2026-08-17', '2026-10-12', '2026-11-02', '2026-11-16', '2026-12-08', '2026-12-25',
        ], array_keys(ApisExternas::festivosLocales(2026)));
    }

    public function test_ignora_la_cache_vieja_del_controlador_anterior(): void
    {
        // Lo que dejaba el ApiController anterior: la respuesta cruda con la clave api_festivos_2026.
        \Illuminate\Support\Facades\Cache::put('api_festivos_2026', [['date' => '2026-12-25', 'localName' => 'Navidad']], 600);

        $this->assertSame(['festivo' => true, 'nombre' => 'Navidad'], ApisExternas::esFestivo('2026-12-25'));
    }

    // ---------------- 3. Índice UV ----------------

    public function test_lee_el_indice_uv_actual(): void
    {
        $uv = ApisExternas::uvActual();

        $this->assertSame(7.3, $uv['uv']);
        $this->assertSame('Alto', $uv['nivel']);
        $this->assertSame(9.1, $uv['uv_max_hoy']);
        $this->assertStringContainsString('latitude=4.4389', $this->pedidas[0]);
        $this->assertStringContainsString('current=uv_index', $this->pedidas[0]);
    }

    public function test_la_seccion_uv_muestra_el_indice_y_el_enlace_a_gafas_uv(): void
    {
        $html = view('includes.rayos-uv', ['uv' => ApisExternas::uvActual()])->render();

        $this->assertStringContainsString('7.3', $html);
        $this->assertStringContainsString('Alto', $html);
        $this->assertStringContainsString(route('productos.index', ['uv' => 1]), $html);
    }

    public function test_la_seccion_uv_avisa_si_la_api_no_responde(): void
    {
        $html = view('includes.rayos-uv', ['uv' => null])->render();

        $this->assertStringContainsString('No pudimos consultar el índice UV', $html);
        $this->assertStringContainsString(route('productos.index', ['uv' => 1]), $html); // el botón sigue disponible
    }
}
