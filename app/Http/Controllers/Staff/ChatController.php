<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\ChatArchivo;
use Illuminate\Contracts\View\View;

/**
 * Chat interno de la óptica (guardado en archivos, sin tablas nuevas): la recepcionista (rol 4) conversa con los optómetras (rol 7)
 * y ellos con la recepción. Solo se ve gente de la misma óptica (mismo nit_empresa).
 */
class ChatController extends Controller
{
    protected function yo()
    {
        return Auth::user();
    }

    /** Rol de las personas con las que puedo hablar: la recepción habla con optómetras y viceversa. */
    protected function rolContacto(): int
    {
        return (int) $this->yo()->id_rol === (int) config('visioptica.rol_recepcionista')
            ? (int) config('visioptica.rol_optometra')
            : (int) config('visioptica.rol_recepcionista');
    }

    protected function contactos()
    {
        return DB::table('usuario')
            ->where('nit_empresa', $this->yo()->nit_empresa)
            ->where('id_rol', $this->rolContacto())
            ->where('id_estado', '!=', 2)
            ->orderBy('nombres')
            ->get(['documento', 'nombres', 'apellido']);
    }

    protected function contacto(int $documento)
    {
        return DB::table('usuario')
            ->where('documento', $documento)
            ->where('nit_empresa', $this->yo()->nit_empresa)
            ->where('id_rol', $this->rolContacto())
            ->first(['documento', 'nombres', 'apellido']);
    }

    protected function nit(): string
    {
        return (string) $this->yo()->nit_empresa;
    }

    public function index(Request $request): View
    {
        $yo = (int) $this->yo()->documento;
        $nit = $this->nit();

        $contactos = $this->contactos()->map(function ($c) use ($yo, $nit) {
            $c->ultimo = ChatArchivo::ultimo($nit, $yo, (int) $c->documento);
            $c->sinLeer = ChatArchivo::sinLeer($nit, $yo, (int) $c->documento);

            return $c;
        });

        // Conversación abierta: la elegida o la que tenga mensajes más recientes.
        $actual = $request->filled('con') ? $this->contacto((int) $request->con) : null;
        if (! $actual && $contactos->isNotEmpty()) {
            $actual = $contactos->sortByDesc(fn ($c) => $c->ultimo->id_chat ?? 0)->first();
        }

        $mensajes = collect();
        if ($actual) {
            $mensajes = collect(ChatArchivo::mensajes($nit, $yo, (int) $actual->documento));
            if ($mensajes->isNotEmpty()) {
                ChatArchivo::marcarLeido($nit, $yo, (int) $actual->documento, (int) $mensajes->max('id_chat'));
            }
            foreach ($contactos as $c) {
                if ($c->documento == $actual->documento) {
                    $c->sinLeer = 0;
                }
            }
        }

        return view('staff.chat.index', [
            'contactos' => $contactos,
            'actual' => $actual,
            'mensajes' => $mensajes,
            'yo' => $yo,
            'rolContacto' => $this->rolContacto() === (int) config('visioptica.rol_optometra') ? 'optómetras' : 'recepcionistas',
        ]);
    }

    public function enviar(Request $request, int $documento)
    {
        abort_unless($this->contacto($documento), 404);

        $data = $request->validate(['mensaje' => ['required', 'string', 'max:1000']], ['mensaje.required' => 'Escribe un mensaje.']);
        $texto = trim($data['mensaje']);
        abort_if($texto === '', 422);

        $m = ChatArchivo::agregar($this->nit(), (int) $this->yo()->documento, $documento, $texto);

        if ($request->expectsJson()) {
            return response()->json(['id' => $m->id_chat, 'mensaje' => $texto, 'hora' => now()->format('H:i')]);
        }

        return redirect()->route('chat.index', ['con' => $documento]);
    }

    /** Mensajes nuevos de una conversación (el navegador lo consulta cada pocos segundos). */
    public function nuevos(Request $request, int $documento): JsonResponse
    {
        abort_unless($this->contacto($documento), 404);

        $yo = (int) $this->yo()->documento;
        $nuevos = collect(ChatArchivo::mensajes($this->nit(), $yo, $documento, (int) $request->get('despues', 0)))
            ->filter(fn ($m) => (int) $m->de_documento === $documento)->values();

        if ($nuevos->isNotEmpty()) {
            ChatArchivo::marcarLeido($this->nit(), $yo, $documento, (int) $nuevos->max('id_chat'));
        }

        return response()->json($nuevos->map(fn ($m) => [
            'id' => $m->id_chat,
            'mensaje' => $m->mensaje,
            'hora' => \Illuminate\Support\Carbon::parse($m->fecha)->format('H:i'),
        ])->values());
    }
}
