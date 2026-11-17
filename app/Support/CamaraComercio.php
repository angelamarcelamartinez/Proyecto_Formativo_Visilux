<?php

// Clase de apoyo para el certificado de la Cámara de Comercio.
namespace App\Support;

// UploadedFile: el archivo que subió el usuario.
use Illuminate\Http\UploadedFile;
// Storage: sistema de archivos de Laravel (disco "local" = storage/app/private).
use Illuminate\Support\Facades\Storage;
// Str: ayudas para texto (aquí, Str::slug).
use Illuminate\Support\Str;

/**
 * Certificado de existencia y representación legal (Cámara de Comercio) de cada óptica.
 *
 * Se guarda en storage/app/private/camara_comercio (NO es público): solo el superadmin
 * puede verlo, a través de la ruta superadmin.empresas.camara. El superadmin lo compara
 * a mano con la Cámara de Comercio (sin API) y confirma que el NIT existe.
 */
// El PDF se guarda en una carpeta PRIVADA (no accesible por URL): solo el superadmin lo ve mediante la ruta superadmin.empresas.camara.
class CamaraComercio
{
    // Subcarpeta dentro del disco privado donde se guardan los PDF.
    public const CARPETA = 'camara_comercio';

    /** Reglas de validación del archivo (PDF de máximo 5 MB). */
    // Reglas del archivo: requerido (u opcional), debe ser archivo, extensión pdf, tipo MIME application/pdf y máximo 5120 KB (5 MB).
    public static function reglas(bool $obligatorio = true): array
    {
        return [$obligatorio ? 'required' : 'nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:5120'];
    }

    // Mensajes de error en español para cada regla anterior.
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

    /** Guarda el PDF y devuelve su ruta relativa (la que va en empresa.camara_comercio). */
    // Guarda el PDF y devuelve su ruta relativa.
    public static function guardar(UploadedFile $archivo, string $nit): string
    {
        // Nombre del archivo: NIT + fecha y hora (año mes día hora min seg) + .pdf; evita nombres repetidos.
        $nombre = Str::slug($nit) . '_' . now()->format('YmdHis') . '.pdf';

        // putFileAs(carpeta, archivo, nombre) copia el archivo subido a esa carpeta y devuelve la ruta.
        return Storage::disk('local')->putFileAs(self::CARPETA, $archivo, $nombre);
    }

    // ¿La ruta tiene valor y el archivo realmente existe en disco? Lo usa Empresa::tieneCamara().
    public static function existe(?string $ruta): bool
    {
        return $ruta && Storage::disk('local')->exists($ruta);
    }

    // Convierte la ruta relativa en la ruta absoluta del servidor (para poder mostrar el PDF).
    public static function ruta(string $ruta): string
    {
        return Storage::disk('local')->path($ruta);
    }

    // Elimina el archivo si existe (se usa cuando falla el registro).
    public static function borrar(?string $ruta): void
    {
        if ($ruta && Storage::disk('local')->exists($ruta)) {
            Storage::disk('local')->delete($ruta);
        }
    }
}
