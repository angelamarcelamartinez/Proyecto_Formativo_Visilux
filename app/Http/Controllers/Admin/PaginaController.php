<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\PaginaEmpresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * "Mi página": cada admin edita los textos e imágenes de la página pública de SU óptica.
 * La óptica siempre sale del usuario conectado, así que no puede tocar la de otra.
 */
class PaginaController extends Controller
{
    public function edit(): View
    {
        $empresa = Auth::user()->empresa;

        return view('admin.pagina.edit', [
            'empresa' => $empresa,
            'pagina' => PaginaEmpresa::deEmpresa($empresa),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $empresa = Auth::user()->empresa;
        $pagina = PaginaEmpresa::deEmpresa($empresa);

        $texto = fn ($max) => ['nullable', 'string', 'max:' . $max];
        $imagen = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'];

        $data = $request->validate([
            'hero_titulo' => $texto(80), 'hero_subtitulo' => $texto(120), 'hero_texto' => $texto(300),
            'stat1_valor' => $texto(20), 'stat1_texto' => $texto(60),
            'stat2_valor' => $texto(20), 'stat2_texto' => $texto(60),
            'stat3_valor' => $texto(20), 'stat3_texto' => $texto(60),
            'servicios_titulo' => $texto(120), 'servicios_subtitulo' => $texto(200),
            'serv1_titulo' => $texto(120), 'serv1_texto' => $texto(1000), 'serv1_items' => $texto(1000),
            'serv2_titulo' => $texto(120), 'serv2_texto' => $texto(1000), 'serv2_items' => $texto(1000),
            'prof_nombre' => $texto(120), 'prof_cargo' => $texto(120), 'prof_texto' => $texto(1000),
            'prof_credenciales' => $texto(1500),
            'porque_titulo' => $texto(120), 'porque_subtitulo' => $texto(200),
            'ventaja1_titulo' => $texto(120), 'ventaja1_texto' => $texto(255),
            'ventaja2_titulo' => $texto(120), 'ventaja2_texto' => $texto(255),
            'footer_texto' => $texto(300), 'horario' => $texto(500),
            'telefono' => $texto(40), 'email' => ['nullable', 'email', 'max:150'], 'direccion' => $texto(200),
            'instagram' => ['nullable', 'url', 'max:255'], 'facebook' => ['nullable', 'url', 'max:255'],
            'logo' => $imagen, 'hero_imagen' => $imagen, 'serv1_imagen' => $imagen,
            'serv2_imagen' => $imagen, 'prof_imagen' => $imagen,
        ], [], [
            'instagram' => 'Instagram', 'facebook' => 'Facebook', 'logo' => 'el logo',
            'hero_imagen' => 'la imagen', 'serv1_imagen' => 'la imagen', 'serv2_imagen' => 'la imagen', 'prof_imagen' => 'la foto',
        ]);

        // Imágenes: se guardan en public/assets/img/paginas/{nit}/ y se reemplaza la anterior.
        $carpeta = trim(config('visioptica.carpeta_imagenes'), '/') . '/' . Str::slug($empresa->nit);

        foreach (PaginaEmpresa::IMAGENES as $campo) {
            unset($data[$campo]);

            if ($request->boolean("quitar_{$campo}")) {
                $this->borrarImagen($pagina->{$campo});
                $data[$campo] = null;
            }

            if ($request->hasFile($campo)) {
                $archivo = $request->file($campo);
                $nombre = $campo . '-' . now()->format('YmdHis') . '.' . $archivo->extension();
                $archivo->move(public_path($carpeta), $nombre);

                $this->borrarImagen($pagina->{$campo});
                $data[$campo] = $carpeta . '/' . $nombre;
            }
        }

        $pagina->fill($data);
        $pagina->actualizado = now();
        $pagina->save();

        Actividad::registrar($empresa->nit, 'editar', 'Actualizó su página pública', 'pagina_empresa', $empresa->nit);

        return redirect()->route('admin.pagina.edit')->with('success', 'Cambios guardados. Ya se ven en tu página pública.');
    }

    /**
     * Solo borra imágenes subidas por la óptica, nunca las de ejemplo.
     */
    protected function borrarImagen(?string $ruta): void
    {
        $carpeta = trim(config('visioptica.carpeta_imagenes'), '/');

        if ($ruta && Str::startsWith($ruta, $carpeta . '/') && File::exists(public_path($ruta))) {
            File::delete(public_path($ruta));
        }
    }
}
