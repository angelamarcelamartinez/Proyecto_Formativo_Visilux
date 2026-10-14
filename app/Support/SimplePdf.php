<?php

namespace App\Support;

/**
 * Generador mínimo de PDF con una tabla, sin librerías externas.
 *
 * Igual que SimpleXlsx, no necesita instalar nada con Composer: arma el PDF a
 * mano (hoja A4 horizontal, fuente Helvetica, encabezado repetido en cada
 * página, filas que se parten en varias líneas cuando el texto es largo y
 * número de página al pie). Se usa en los reportes del superadmin.
 */
class SimplePdf
{
    // Hoja A4 horizontal, en puntos (1 pt = 1/72 de pulgada)
    private const W = 841.89;
    private const H = 595.28;
    private const M = 36;          // margen
    private const FONT = 8;        // tamaño del texto de la tabla
    private const LINE = 10.5;     // alto de cada línea de texto
    private const PAD = 4;         // espacio interno de cada celda

    /** Ancho de cada carácter de Helvetica (por 1000), de ' ' (32) a '~' (126). */
    private const WIDTHS = [
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
        1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
        333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
        556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
    ];

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, string>  $resumen  Datos que van arriba (etiqueta => valor)
     * @param  array<int, string>  $alinear  'L' (izquierda) o 'R' (derecha) por columna
     * @return string contenido binario del PDF
     */
    public static function build(string $titulo, string $subtitulo, array $headers, array $rows, array $resumen = [], array $alinear = []): string
    {
        $anchoUtil = self::W - 2 * self::M;
        $columnas = count($headers);

        // 1) Texto de cada celda, ya en la codificación del PDF
        $tabla = [];
        foreach (array_values($rows) as $fila) {
            $linea = [];
            for ($i = 0; $i < $columnas; $i++) {
                $linea[] = self::codificar((string) (array_values($fila)[$i] ?? ''));
            }
            $tabla[] = $linea;
        }
        $cabecera = array_map(fn ($h) => self::codificar((string) $h), $headers);

        // 2) Ancho de cada columna: proporcional al texto más largo de la columna
        $natural = [];
        for ($i = 0; $i < $columnas; $i++) {
            $max = self::ancho($cabecera[$i], true);
            foreach ($tabla as $linea) {
                $max = max($max, self::ancho($linea[$i], false));
            }
            $natural[$i] = min($max, 230) + 2 * self::PAD + 4;
        }
        $suma = array_sum($natural) ?: 1;
        $anchos = array_map(fn ($n) => $n * $anchoUtil / $suma, $natural);

        // 3) Páginas
        $paginas = [];
        $ops = '';
        $y = self::H - self::M;

        $nuevaPagina = function () use (&$paginas, &$ops, &$y) {
            if ($ops !== '') {
                $paginas[] = $ops;
            }
            $ops = '';
            $y = self::H - self::M;
        };

        // Encabezado del documento (solo en la primera página)
        $ops .= self::texto(self::M, $y - 14, self::codificar($titulo), 16, true, [0.18, 0.16, 0.13]);
        $y -= 20;
        if ($subtitulo !== '') {
            $ops .= self::texto(self::M, $y - 10, self::codificar($subtitulo), 9, false, [0.45, 0.43, 0.38]);
            $y -= 16;
        }
        $y -= 6;

        if ($resumen) {
            $x = self::M;
            $caja = ($anchoUtil - 8 * (count($resumen) - 1)) / count($resumen);
            foreach ($resumen as $etiqueta => $valor) {
                $ops .= self::rect($x, $y - 34, $caja, 34, [0.94, 0.92, 0.87]);
                $ops .= self::texto($x + 8, $y - 13, self::codificar((string) $etiqueta), 7.5, false, [0.45, 0.43, 0.38]);
                $ops .= self::texto($x + 8, $y - 28, self::codificar((string) $valor), 12, true, [0.18, 0.16, 0.13]);
                $x += $caja + 8;
            }
            $y -= 46;
        }

        $dibujarCabecera = function () use (&$ops, &$y, $cabecera, $anchos, $alinear) {
            $alto = self::LINE + 2 * self::PAD - 2;
            $ops .= self::rect(self::M, $y - $alto, array_sum($anchos), $alto, [0.58, 0.46, 0.18]);
            $x = self::M;
            foreach ($cabecera as $i => $t) {
                $ops .= self::celda($x, $y - self::PAD - 8, $anchos[$i], $t, true, [1, 1, 1], ($alinear[$i] ?? 'L') === 'R');
                $x += $anchos[$i];
            }
            $y -= $alto;
        };

        $dibujarCabecera();

        foreach ($tabla as $n => $linea) {
            // Cada celda se parte en las líneas que necesite
            $partes = [];
            $maxLineas = 1;
            foreach ($linea as $i => $texto) {
                $partes[$i] = self::partir($texto, $anchos[$i] - 2 * self::PAD);
                $maxLineas = max($maxLineas, count($partes[$i]));
            }
            $alto = $maxLineas * self::LINE + 2 * self::PAD - 3;

            if ($y - $alto < self::M + 22) {
                $nuevaPagina();
                $dibujarCabecera();
            }

            if ($n % 2 === 1) {
                $ops .= self::rect(self::M, $y - $alto, array_sum($anchos), $alto, [0.98, 0.97, 0.95]);
            }

            $x = self::M;
            foreach ($partes as $i => $lineasCelda) {
                foreach ($lineasCelda as $k => $t) {
                    $ops .= self::celda($x, $y - self::PAD - 7 - $k * self::LINE, $anchos[$i], $t, false, [0.18, 0.16, 0.13], ($alinear[$i] ?? 'L') === 'R');
                }
                $x += $anchos[$i];
            }

            $y -= $alto;
            $ops .= self::linea(self::M, $y, self::M + array_sum($anchos), $y, [0.9, 0.87, 0.8]);
        }

        if ($tabla === []) {
            $ops .= self::texto(self::M + 6, $y - 16, self::codificar('No hay datos para este reporte.'), 9, false, [0.45, 0.43, 0.38]);
        }

        $paginas[] = $ops;

        // 4) Pie de página con número, ahora que sabemos cuántas son
        $total = count($paginas);
        $generado = self::codificar('VisiOptica · Generado el ' . now()->format('d/m/Y H:i'));
        foreach ($paginas as $i => $contenido) {
            $pie = self::texto(self::M, 22, $generado, 7.5, false, [0.55, 0.53, 0.48]);
            $num = self::codificar('Página ' . ($i + 1) . ' de ' . $total);
            $pie .= self::texto(self::W - self::M - self::ancho($num, false, 7.5), 22, $num, 7.5, false, [0.55, 0.53, 0.48]);
            $paginas[$i] = $contenido . $pie;
        }

        return self::armar($paginas, self::codificar($titulo));
    }

    // ---------------------------------------------------------------- dibujo

    private static function celda(float $x, float $y, float $ancho, string $texto, bool $negrita, array $color, bool $derecha): string
    {
        if ($texto === '') {
            return '';
        }
        $px = $derecha
            ? $x + $ancho - self::PAD - self::ancho($texto, $negrita)
            : $x + self::PAD;

        return self::texto($px, $y, $texto, self::FONT, $negrita, $color);
    }

    private static function texto(float $x, float $y, string $texto, float $tam, bool $negrita, array $color): string
    {
        return sprintf(
            "BT /%s %.2F Tf %.3F %.3F %.3F rg %.2F %.2F Td (%s) Tj ET\n",
            $negrita ? 'F2' : 'F1', $tam, $color[0], $color[1], $color[2], $x, $y, self::escapar($texto)
        );
    }

    private static function rect(float $x, float $y, float $w, float $h, array $color): string
    {
        return sprintf("%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f\n", $color[0], $color[1], $color[2], $x, $y, $w, $h);
    }

    private static function linea(float $x1, float $y1, float $x2, float $y2, array $color): string
    {
        return sprintf("%.3F %.3F %.3F RG 0.4 w %.2F %.2F m %.2F %.2F l S\n", $color[0], $color[1], $color[2], $x1, $y1, $x2, $y2);
    }

    // ----------------------------------------------------------------- texto

    /** UTF-8 -> Windows-1252, que es la codificación de las fuentes básicas del PDF. */
    private static function codificar(string $texto): string
    {
        $texto = str_replace(["\r", "\n", "\t"], ' ', $texto);
        $convertido = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $texto);

        return $convertido === false ? preg_replace('/[^\x20-\x7E]/', '?', $texto) : $convertido;
    }

    private static function escapar(string $texto): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $texto);
    }

    /** Ancho del texto en puntos. */
    private static function ancho(string $texto, bool $negrita, float $tam = self::FONT): float
    {
        $suma = 0;
        $len = strlen($texto);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($texto[$i]);
            $suma += ($c >= 32 && $c <= 126) ? self::WIDTHS[$c - 32] : 556;
        }

        return $suma * $tam / 1000 * ($negrita ? 1.06 : 1);
    }

    /**
     * Parte un texto en líneas que quepan en el ancho dado, cortando por palabras
     * (y por letras cuando una palabra sola no cabe).
     *
     * @return array<int, string>
     */
    private static function partir(string $texto, float $maximo): array
    {
        if ($texto === '' || self::ancho($texto, false) <= $maximo) {
            return [$texto];
        }

        $lineas = [];
        $actual = '';
        foreach (explode(' ', $texto) as $palabra) {
            $prueba = $actual === '' ? $palabra : $actual . ' ' . $palabra;
            if (self::ancho($prueba, false) <= $maximo) {
                $actual = $prueba;
                continue;
            }
            if ($actual !== '') {
                $lineas[] = $actual;
                $actual = '';
            }
            // Palabra más larga que la columna: se corta por letras
            while (self::ancho($palabra, false) > $maximo && strlen($palabra) > 1) {
                $corte = strlen($palabra) - 1;
                while ($corte > 1 && self::ancho(substr($palabra, 0, $corte), false) > $maximo) {
                    $corte--;
                }
                $lineas[] = substr($palabra, 0, $corte);
                $palabra = substr($palabra, $corte);
            }
            $actual = $palabra;
        }
        if ($actual !== '') {
            $lineas[] = $actual;
        }

        return $lineas ?: [''];
    }

    // ------------------------------------------------------------- estructura

    /** @param array<int, string> $paginas contenido de cada página */
    private static function armar(array $paginas, string $titulo): string
    {
        // Objetos: 1 catálogo, 2 páginas, 3 y 4 fuentes, luego (página, contenido) por cada hoja
        $objetos = [];
        $hijos = [];
        foreach ($paginas as $i => $contenido) {
            $idPagina = 5 + $i * 2;
            $hijos[] = $idPagina . ' 0 R';
            $objetos[$idPagina] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::W, self::H, $idPagina + 1
            );
            $objetos[$idPagina + 1] = '<< /Length ' . strlen($contenido) . " >>\nstream\n" . $contenido . 'endstream';
        }

        $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[2] = '<< /Type /Pages /Kids [' . implode(' ', $hijos) . '] /Count ' . count($paginas) . ' >>';
        $objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $info = 5 + count($paginas) * 2;
        $objetos[$info] = '<< /Title (' . self::escapar($titulo) . ') /Producer (VisiOptica) >>';
        ksort($objetos);

        $pdf = "%PDF-1.4\n";
        $posiciones = [];
        foreach ($objetos as $id => $cuerpo) {
            $posiciones[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $cuerpo . "\nendobj\n";
        }

        $inicioXref = strlen($pdf);
        $pdf .= "xref\n0 " . ($info + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= $info; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $posiciones[$id]);
        }
        $pdf .= "trailer\n<< /Size " . ($info + 1) . ' /Root 1 0 R /Info ' . $info . " 0 R >>\nstartxref\n" . $inicioXref . "\n%%EOF";

        return $pdf;
    }
}
