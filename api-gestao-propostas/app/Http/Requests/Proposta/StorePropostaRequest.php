<?php

namespace App\Http\Requests\Proposta;

use App\Enums\PropostaOrigem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropostaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'produto' => ['required', 'string', 'max:255'],
            'valor_mensal' => ['required', 'numeric', 'min:0'],
            'origem' => ['nullable', 'string', Rule::in(array_map(fn (PropostaOrigem $o) => $o->value, PropostaOrigem::cases()))],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'O cliente é obrigatório.',
            'cliente_id.exists' => 'Cliente não encontrado.',
            'produto.required' => 'O produto é obrigatório.',
            'valor_mensal.required' => 'O valor mensal é obrigatório.',
            'valor_mensal.min' => 'O valor mensal deve ser maior ou igual a zero.',
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge(['origem' => $this->input('origem', PropostaOrigem::API->value)]);
    }
}
