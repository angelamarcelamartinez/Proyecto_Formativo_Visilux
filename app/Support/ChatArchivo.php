<?php

namespace App\Support;

/**
 * Guarda el chat interno en archivos (storage/app/chat/<nit>/<docA>_<docB>.jsonl), sin tocar la base de datos.
 * Cada línea del archivo es un mensaje en JSON. Qué se ha leído se guarda aparte, en <docA>_<docB>.leido.json.
 * storage/app no es público: nadie puede abrir estos archivos desde el navegador.
 */
class ChatArchivo
{
    protected static function carpeta(string $nit): string
    {
        $dir = storage_path('app/chat/' . preg_replace('/[^A-Za-z0-9_-]/', '_', $nit));
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    protected static function base(string $nit, int $a, int $b): string
    {
        return self::carpeta($nit) . '/' . min($a, $b) . '_' . max($a, $b);
    }

    /** Mensajes de la conversación con id mayor a $despues (objetos con id_chat, de_documento, para_documento, mensaje, fecha). */
    public static function mensajes(string $nit, int $a, int $b, int $despues = 0, int $limite = 200): array
    {
        $archivo = self::base($nit, $a, $b) . '.jsonl';
        if (! is_file($archivo)) {
            return [];
        }

        $lista = [];
        foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linea) {
            $m = json_decode($linea);
            if ($m && $m->id_chat > $despues) {
                $lista[] = $m;
            }
        }

        return array_slice($lista, -$limite);
    }

    public static function ultimo(string $nit, int $a, int $b)
    {
        $m = self::mensajes($nit, $a, $b, 0, 1);

        return $m[0] ?? null;
    }

    /** Agrega un mensaje y devuelve su id (con bloqueo, para que dos envíos a la vez no se pisen). */
    public static function agregar(string $nit, int $de, int $para, string $texto): object
    {
        $archivo = self::base($nit, $de, $para) . '.jsonl';
        $f = fopen($archivo, 'c+');
        flock($f, LOCK_EX);

        $id = 0;
        while (($linea = fgets($f)) !== false) {
            $m = json_decode($linea);
            $id = max($id, (int) ($m->id_chat ?? 0));
        }

        $mensaje = (object) [
            'id_chat' => $id + 1,
            'de_documento' => $de,
            'para_documento' => $para,
            'mensaje' => $texto,
            'fecha' => now()->toDateTimeString(),
        ];
        fwrite($f, json_encode($mensaje, JSON_UNESCAPED_UNICODE) . "\n");
        flock($f, LOCK_UN);
        fclose($f);

        return $mensaje;
    }

    protected static function leidos(string $nit, int $a, int $b): array
    {
        $archivo = self::base($nit, $a, $b) . '.leido.json';

        return is_file($archivo) ? (json_decode((string) file_get_contents($archivo), true) ?: []) : [];
    }

    /** Marca como leído, para $yo, todo lo de la conversación hasta el mensaje $hastaId. */
    public static function marcarLeido(string $nit, int $yo, int $otro, int $hastaId): void
    {
        $datos = self::leidos($nit, $yo, $otro);
        if (($datos[$yo] ?? 0) >= $hastaId) {
            return;
        }
        $datos[$yo] = $hastaId;
        file_put_contents(self::base($nit, $yo, $otro) . '.leido.json', json_encode($datos), LOCK_EX);
    }

    /** Mensajes del otro que $yo todavía no ha leído. */
    public static function sinLeer(string $nit, int $yo, int $otro): int
    {
        $visto = (int) (self::leidos($nit, $yo, $otro)[$yo] ?? 0);

        return count(array_filter(
            self::mensajes($nit, $yo, $otro, $visto, 1000),
            fn ($m) => (int) $m->de_documento === $otro
        ));
    }

    /** Total de mensajes sin leer de todas mis conversaciones (insignia del menú). */
    public static function totalSinLeer(string $nit, int $yo): int
    {
        $total = 0;
        foreach (glob(self::carpeta($nit) . '/*.jsonl') ?: [] as $archivo) {
            [$a, $b] = array_map('intval', explode('_', basename($archivo, '.jsonl')) + [0, 0]);
            if ($a === $yo || $b === $yo) {
                $total += self::sinLeer($nit, $yo, $a === $yo ? $b : $a);
            }
        }

        return $total;
    }
}
