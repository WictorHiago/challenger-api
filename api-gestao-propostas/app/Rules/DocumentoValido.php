<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DocumentoValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) === 11) {
            if (!$this->cpfValido($digits)) {
                $fail('O campo :attribute não é um CPF válido.');
            }
            return;
        }

        if (strlen($digits) === 14) {
            if (!$this->cnpjValido($digits)) {
                $fail('O campo :attribute não é um CNPJ válido.');
            }
            return;
        }

        $fail('O campo :attribute deve ser um CPF (11 dígitos) ou CNPJ (14 dígitos) válido.');
    }

    private function cpfValido(string $cpf): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += (int) $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ((int) $cpf[$c] !== $d) {
                return false;
            }
        }

        return true;
    }

    private function cnpjValido(string $cnpj): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnpj[$i] * $weights1[$i];
        }
        $digit1 = ($sum % 11) < 2 ? 0 : 11 - ($sum % 11);
        if ((int) $cnpj[12] !== $digit1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $cnpj[$i] * $weights2[$i];
        }
        $digit2 = ($sum % 11) < 2 ? 0 : 11 - ($sum % 11);

        return (int) $cnpj[13] === $digit2;
    }
}
