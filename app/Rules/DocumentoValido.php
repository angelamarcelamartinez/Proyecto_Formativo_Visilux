<?php

namespace App\Rules;

use App\Support\TipoDocumento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida el número de documento según el tipo elegido (cédula = solo números, pasaporte = alfanumérico...).
 * Uso: 'documento' => ['required', new DocumentoValido($request->input('id_tipo_docu'))]
 */
class DocumentoValido implements ValidationRule
{
    public function __construct(private $idTipo)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($error = TipoDocumento::error($this->idTipo, (string) $value)) {
            $fail($error);
        }
    }
}
