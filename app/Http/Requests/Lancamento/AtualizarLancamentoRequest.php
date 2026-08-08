<?php

namespace App\Http\Requests\Lancamento;

use App\Enum\FormaPagamento;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarLancamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'data_vencimento' => ['required', 'date'],
            'tipo' => ['required', Rule::enum(TipoLancamento::class)],
            'forma_pagamento' => ['required', Rule::enum(FormaPagamento::class)],
            'id_conta_bancaria' => ['nullable', 'integer'],
            'id_cartao_credito' => ['nullable', 'integer'],
            'situacao' => ['required', Rule::enum(SituacaoLancamento::class)],
            'id_categoria' => ['nullable', 'integer'],
            'observacao' => ['nullable', 'string'],
            'data_pagamento' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor' => Valor::normalizarValorMonetario($this->input('valor')),
        ]);
    }
}
