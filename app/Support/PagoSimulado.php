<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pago con tarjeta SIMULADO: no se conecta a ningún banco ni cobra nada.
 * Valida los datos como lo haría una pasarela real y devuelve un comprobante.
 *
 * Nunca se guarda el número completo ni el CVV: solo la marca y los últimos 4 dígitos.
 *
 * Tarjetas de prueba:
 *   4242 4242 4242 4242  → pago aprobado (Visa)
 *   5555 5555 5555 4444  → pago aprobado (Mastercard)
 *   4000 0000 0000 0002  → pago rechazado por el banco
 */
class PagoSimulado
{
    public const TARJETA_RECHAZADA = '4000000000000002';

    /**
     * @return array{referencia: string, marca: string, ultimos4: string, titular: string}
     */
    public static function cobrar(string $numero, string $titular, string $vencimiento, string $cvv, float $valor): array
    {
        $numero = preg_replace('/\D/', '', $numero);

        if (! static::luhnValido($numero)) {
            throw ValidationException::withMessages(['tarjeta_numero' => 'El número de la tarjeta no es válido. Revisa que esté completo.']);
        }

        $marca = static::marca($numero);
        if (! $marca) {
            throw ValidationException::withMessages(['tarjeta_numero' => 'Solo se aceptan tarjetas Visa, Mastercard o American Express.']);
        }

        if (! preg_match('/^(0[1-9]|1[0-2])\/?(\d{2})$/', trim($vencimiento), $m)) {
            throw ValidationException::withMessages(['tarjeta_vencimiento' => 'Escribe la fecha de vencimiento como MM/AA.']);
        }
        $finDeMes = now()->setDate(2000 + (int) $m[2], (int) $m[1], 1)->endOfMonth();
        if ($finDeMes->isPast()) {
            throw ValidationException::withMessages(['tarjeta_vencimiento' => 'La tarjeta está vencida.']);
        }

        $digitosCvv = $marca === 'American Express' ? 4 : 3;
        if (! preg_match('/^\d{' . $digitosCvv . '}$/', $cvv)) {
            throw ValidationException::withMessages(['tarjeta_cvv' => "El código de seguridad debe tener {$digitosCvv} dígitos."]);
        }

        if ($numero === self::TARJETA_RECHAZADA) {
            throw ValidationException::withMessages(['tarjeta_numero' => 'El banco rechazó el pago. Prueba con otra tarjeta.']);
        }

        return [
            'referencia' => 'SIM-' . now()->format('ymd') . '-' . strtoupper(Str::random(6)),
            'marca' => $marca,
            'ultimos4' => substr($numero, -4),
            'titular' => mb_strtoupper(trim($titular)),
        ];
    }

    public static function marca(string $numero): ?string
    {
        return match (true) {
            (bool) preg_match('/^4\d{15}$/', $numero) => 'Visa',
            (bool) preg_match('/^(5[1-5]\d{14}|2(2[2-9]|[3-6]\d|7[01])\d{12}|2720\d{12})$/', $numero) => 'Mastercard',
            (bool) preg_match('/^3[47]\d{13}$/', $numero) => 'American Express',
            default => null,
        };
    }

    /**
     * Algoritmo de Luhn: el mismo chequeo que usan los bancos para detectar números mal digitados.
     */
    public static function luhnValido(string $numero): bool
    {
        if (! preg_match('/^\d{13,19}$/', $numero)) {
            return false;
        }

        $suma = 0;
        $doblar = false;
        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $d = (int) $numero[$i];
            if ($doblar) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $suma += $d;
            $doblar = ! $doblar;
        }

        return $suma % 10 === 0;
    }
}
