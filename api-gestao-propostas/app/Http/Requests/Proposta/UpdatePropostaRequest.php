<?php

namespace App\Http\Requests\Proposta;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropostaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'versao' => ['required', 'integer', 'min:1'],
            'produto' => ['sometimes', 'string', 'max:255'],
            'valor_mensal' => ['sometimes', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'versao.required' => 'A versão é obrigatória para evitar conflitos (optimistic lock).',
            'versao.integer' => 'A versão deve ser um número inteiro.',
        ];
    }
}
