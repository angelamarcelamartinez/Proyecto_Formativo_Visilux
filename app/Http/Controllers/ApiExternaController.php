<?php

namespace App\Http\Controllers;

// ApisExternas: las llamadas a las APIs externas (mismos endpoints de public/apis/).
use App\Support\ApisExternas;
use Illuminate\Http\JsonResponse;

/**
 * Ruta JSON que usa el formulario de /planes/{plan}/contratar con JavaScript:
 *   - GET /api-externa/departamentos/{id}/ciudades → ciudades de un departamento.
 * Si la API externa no responde, se devuelve un error 502 con un mensaje claro.
 */
class ApiExternaController extends Controller
{
    // Ciudades del departamento elegido.
    public function ciudades(int $id): JsonResponse
    {
        $ciudades = ApisExternas::ciudades($id);

        if ($ciudades === null) {
            return response()->json(['error' => 'No se pudieron cargar las ciudades: la API de departamentos de Colombia no respondió.'], 502);
        }

        return response()->json(['ciudades' => $ciudades]);
    }
}
