<?php

namespace App\Http\Requests\EspacoCompartilhado;

use App\Enum\FormaPagamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DarBaixaLancamentoCompartilhadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'versao' => ['required', 'integer', 'min:1'],
            'descricao' => ['required', 'string', 'max:255'],
            'data_vencimento' => ['required', 'date'],
            'forma_pagamento' => ['required', Rule::enum(FormaPagamento::class)],
            'id_conta_bancaria' => ['nullable', 'integer'],
            'id_cartao_credito' => ['nullable', 'integer'],
            'id_categoria' => ['nullable', 'integer'],
            'observacao' => ['nullable', 'string'],
            'situacao' => ['nullable', 'in:pendente,pago'],
            'data_pagamento' => ['nullable', 'date'],
            'confirmar_ultrapassagem_orcamento' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'confirmar_ultrapassagem_orcamento' => filter_var(
                $this->input('confirmar_ultrapassagem_orcamento'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? false,
        ]);
    }

    public function messages(): array
    {
        return [
            'versao.required' => 'Recarregue a página e tente novamente.',
            'descricao.required' => 'A descrição é obrigatória.',
            'data_vencimento.required' => 'A data do lançamento é obrigatória.',
            'forma_pagamento.required' => 'A forma de pagamento é obrigatória.',
        ];
    }
}
