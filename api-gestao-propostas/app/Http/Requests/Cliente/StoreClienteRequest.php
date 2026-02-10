<?php

namespace App\Http\Requests\Cliente;

use App\Rules\DocumentoValido;
use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:clientes,email'],
            'documento' => ['required', 'string', new DocumentoValido],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'O e-mail deve ser um endereço válido.',
            'email.unique' => 'Já existe um cliente cadastrado com este e-mail.',
            'documento.required' => 'O documento (CPF/CNPJ) é obrigatório.',
        ];
    }
}
