<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Certificado de existencia y representación legal (Cámara de Comercio) de cada óptica.
 *
 * Se guarda en storage/app/private/camara_comercio (NO es público): solo el superadmin
 * puede verlo, a través de la ruta superadmin.empresas.camara. El superadmin lo compara
 * a mano con la Cámara de Comercio (sin API) y confirma que el NIT existe.
 */
class CamaraComercio
{
    public const CARPETA = 'camara_comercio';

    /** Reglas de validación del archivo (PDF de máximo 5 MB). */
    public static function reglas(bool $obligatorio = true): array
    {
        return [$obligatorio ? 'required' : 'nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:5120'];
    }

    public static function mensajes(): array
    {
        return [
            'camara_comercio.required' => 'Adjunta el certificado de la Cámara de Comercio en PDF.',
            'camara_comercio.mimes' => 'El certificado debe ser un archivo PDF.',
            'camara_comercio.mimetypes' => 'El certificado debe ser un archivo PDF.',
            'camara_comercio.max' => 'El PDF pesa demasiado: el máximo es 5 MB.',
            'camara_comercio.uploaded' => 'No se pudo subir el PDF. Revisa que pese menos de 5 MB e inténtalo de nuevo.',
        ];
    }

    /** Guarda el PDF y devuelve su ruta relativa (la que va en empresa.camara_de_comercio). */
    public static function guardar(UploadedFile $archivo, string $nit): string
    {
        $nombre = Str::slug($nit) . '_' . now()->format('YmdHis') . '.pdf';

        return Storage::disk('local')->putFileAs(self::CARPETA, $archivo, $nombre);
    }

    public static function existe(?string $ruta): bool
    {
        return $ruta && Storage::disk('local')->exists($ruta);
    }

    public static function ruta(string $ruta): string
    {
        return Storage::disk('local')->path($ruta);
    }

    public static function borrar(?string $ruta): void
    {
        if ($ruta && Storage::disk('local')->exists($ruta)) {
            Storage::disk('local')->delete($ruta);
        }
    }
}
