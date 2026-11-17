<?php

// Este controlador está en app/Http/Controllers/Superadmin, por eso su namespace termina en \Superadmin.
namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;   // Clase base de todos los controladores de Laravel.
use App\Models\Licencia;               // Modelo de la tabla "licencia".
use App\Support\Licencias;             // Clase con las reglas de negocio de las licencias (aprobar, rechazar...).
use App\Support\RegistroOptica;        // Clase con las reglas para dar de alta una óptica nueva.
use Illuminate\Http\RedirectResponse;  // Tipo de respuesta: redirigir a otra página.
use Illuminate\Http\Request;           // Objeto que trae todo lo que envió el usuario (formulario, URL...).
use Illuminate\Support\Facades\DB;     // Para consultar tablas/vistas directamente sin modelo.
use Illuminate\View\View;              // Tipo de respuesta: una vista Blade.

/**
 * Licencias: historial (con descarga en Excel y PDF) y ópticas que requieren atención.
 * Aprobar o rechazar las solicitudes de plan se hace desde el Panel general.
 */
// Un controlador recibe la petición de una ruta, usa modelos/clases de negocio y devuelve una respuesta.
// Todas las rutas de este controlador están en el grupo /superadmin protegido por el middleware "superadmin".
class LicenciaController extends Controller
{
    // GET /superadmin/licencias → pantalla con alertas e historial. Recibe $request para leer el filtro de la URL.
    public function index(Request $request): View
    {
        // Antes de mostrar nada, actualiza los estados: las activas con fecha_fin pasada pasan a "vencida".
        Licencias::marcarVencidas();

        // Ópticas que requieren atención: por vencer, vencidas o sin plan
        // (se filtra en PHP: estado_licencia es una columna calculada de la vista y compararla en SQL
        // puede chocar con la collation de la conexión)
        // v_estado_licencias es una VISTA de la base de datos: una fila por óptica con su estado y días restantes.
        $alertas = DB::table('v_estado_licencias')
            ->get()                                                             // Trae todas las filas a PHP.
            // Se queda solo con las que necesitan atención.
            ->whereIn('estado_licencia', ['por_vencer', 'vencida', 'sin_licencia'])
            // Ordena de menos a más días restantes (la más urgente primero). Si no hay días (null) usa -9999
            // para que las "sin licencia" queden de primeras. "??" = si es null, usa lo que sigue.
            ->sortBy(fn ($o) => $o->dias_restantes ?? -9999)
            ->values();                                                         // Reinicia los índices 0,1,2...

        // Historial de licencias de todas las ópticas (los pagos por aprobar están en el Panel general)
        // Lee ?lic=... de la URL. Solo acepta 'activa', 'vencida' o 'cancelada'; cualquier otro valor (o ninguno) = 'todas'.
        // El "true" en in_array compara el tipo también (comparación estricta), por seguridad.
        $estadoLic = in_array($request->get('lic'), ['activa', 'vencida', 'cancelada'], true) ? $request->get('lic') : 'todas';

        // Consulta del historial.
        $historial = Licencia::with(['empresa', 'plan'])   // Carga de una vez la óptica y el plan de cada licencia.
            ->where('estado', '<>', 'pendiente')           // Las pendientes NO van aquí (se aprueban en el Panel general).
            // when(condición, función): solo agrega el filtro por estado si el usuario eligió uno distinto de 'todas'.
            ->when($estadoLic !== 'todas', fn ($q) => $q->where('estado', $estadoLic))
            ->orderByDesc('fecha_solicitud')               // Las más recientes primero.
            ->paginate(10, ['*'], 'hist')                  // 10 por página; la página se lee de ?hist=N.
            ->withQueryString()                            // Mantiene el filtro ?lic=... al cambiar de página.
            ->fragment('historial');                       // Agrega #historial al enlace para volver a esa sección.

        // Envía a la vista superadmin/licencias/index.blade.php las tres variables.
        // compact('a','b') equivale a ['a' => $a, 'b' => $b].
        return view('superadmin.licencias.index', compact('alertas', 'historial', 'estadoLic'));
    }

    // POST /superadmin/licencias/{id}/aprobar → el superadmin confirma un pago. $id sale de la URL.
    public function aprobar(Request $request, int $id): RedirectResponse
    {
        // Valida lo que llegó del formulario. Si algo falla, Laravel vuelve atrás con los errores.
        $data = $request->validate([
            'referencia_pago' => ['nullable', 'string', 'max:120'], // Opcional, texto, máx. 120 caracteres.
            'valor' => ['nullable', 'numeric', 'min:0'],            // Opcional, número, no negativo.
        ]);

        // Busca la licencia por id (con su óptica y plan). findOrFail: si no existe responde 404.
        $licencia = Licencia::with(['empresa', 'plan'])->findOrFail($id);
        // Si el campo vino vacío queda null.
        $referencia = $data['referencia_pago'] ?? null;
        // Si hay valor lo convierte a número decimal; si no, null (así se usa el valor que ya tenía la licencia).
        $valor = isset($data['valor']) ? (float) $data['valor'] : null;

        // Óptica nueva registrada desde /planes: se activa y se envían las credenciales al correo de la óptica.
        // Si la empresa está "pendiente", es su PRIMER pago (alta nueva), no una renovación.
        if ($licencia->empresa->estaPendiente()) {
            // Antes de activar una óptica nueva hay que verificar su NIT con la Cámara de Comercio:
            // el superadmin revisa el PDF y lo confirma en la ventana emergente (casilla "confirmo").
            // Sin certificado cargado no se puede aprobar: vuelve atrás con un error.
            if (! $licencia->empresa->tieneCamara()) {
                return back()->withErrors(['general' => "{$licencia->empresa->nombre} no tiene certificado de la Cámara de Comercio cargado: edita la óptica y adjunta el PDF antes de aprobar."]);
            }
            // La casilla "confirmo" debe venir marcada: obliga al superadmin a revisar el certificado.
            if (! $request->boolean('confirmo')) {
                return back()->withErrors(['general' => "Antes de aprobar, revisa el certificado y confirma que verificaste el NIT de {$licencia->empresa->nombre} con la Cámara de Comercio (botón «Verificar NIT y aprobar»)."]);
            }

            // Activa la licencia y la empresa, deja al administrador con contraseña temporal y manda las credenciales por correo.
            // Devuelve un arreglo: correo, usuario, password, enviado (true/false) y error.
            $acceso = RegistroOptica::aprobar($licencia, $referencia, $valor);

            // Si el correo salió bien: mensaje de éxito indicando adónde se envió.
            if ($acceso['enviado']) {
                return back()->with('success', "Registro aprobado: {$licencia->empresa->nombre} ya está activa. "
                    . "Enviamos la contraseña temporal al correo de la óptica ({$acceso['correo']}); el administrador entra con {$acceso['usuario']}.");
            }

            // Si el correo falló: la óptica igual queda activa, pero se muestra la contraseña en pantalla
            // para que el superadmin se la entregue por otro medio. Str::limit corta el error a 200 caracteres.
            return back()->with('success', "Registro aprobado: {$licencia->empresa->nombre} ya está activa, pero no se pudo enviar el correo. "
                . "Entrégale estos datos al administrador: usuario {$acceso['usuario']}, contraseña temporal {$acceso['password']}. "
                . "No se pudo enviar a: " . \Illuminate\Support\Str::limit((string) $acceso['error'], 200));
        }

        // Si llegó aquí, la óptica ya existía: es una renovación o un plan nuevo de una óptica activa.
        // Aprueba la licencia: pasa a "activa", calcula fecha_inicio y fecha_fin.
        $licencia = Licencias::aprobar($licencia, $referencia, $valor);
        // Envía el correo "tu plan ya está activo". Devuelve true/false según si salió.
        $avisada = Licencias::notificarActivacion($licencia);

        // Mensaje de éxito con el plan y la fecha final; el "." une textos, y el "? :" elige el aviso del correo.
        return back()->with('success', "Pago aprobado: {$licencia->empresa->nombre} tiene {$licencia->plan->nombre} hasta el "
            . $licencia->fecha_fin->format('d/m/Y') . '.'
            . ($avisada ? ' Le enviamos la confirmación por correo.' : ' No se pudo enviar el correo de confirmación.'));
    }

    // POST /superadmin/licencias/{id}/rechazar → el superadmin rechaza una solicitud de pago.
    public function rechazar(Request $request, int $id): RedirectResponse
    {
        // Único campo del formulario: el motivo, opcional, texto de máximo 500 caracteres.
        $data = $request->validate(['motivo' => ['nullable', 'string', 'max:500']]);

        // Busca la licencia (404 si no existe).
        $licencia = Licencia::with(['empresa', 'plan'])->findOrFail($id);

        // Si es un registro nuevo (empresa pendiente), rechazarlo borra todo lo que se creó al registrarse.
        if ($licencia->empresa->estaPendiente()) {
            RegistroOptica::rechazar($licencia);

            return back()->with('success', "Registro de {$licencia->empresa->nombre} rechazado y eliminado.");
        }

        // Si es una renovación o plan de una óptica existente: la licencia pasa a "cancelada" (no se borra, queda en el historial).
        Licencias::rechazar($licencia, $data['motivo'] ?? null);

        return back()->with('success', "Solicitud de {$licencia->empresa->nombre} rechazada.");
    }
}
