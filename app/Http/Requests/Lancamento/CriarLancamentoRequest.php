<?php

namespace App\Http\Requests\Lancamento;

use App\Enum\FormaPagamento;
use App\Enum\FrequenciaRecorrencia;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarLancamentoRequest extends FormRequest
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
            'situacao' => ['nullable', Rule::enum(SituacaoLancamento::class)],
            'id_categoria' => ['nullable', 'integer'],
            'observacao' => ['nullable', 'string'],
            'recorrente' => ['nullable', 'boolean'],
            'frequencia_recorrencia' => ['nullable', Rule::enum(FrequenciaRecorrencia::class)],
            'intervalo_dias' => ['nullable', 'integer', 'min:1', 'max:365'],
            'recorrencia_ate' => ['nullable', 'date', 'after_or_equal:data_vencimento'],
            'total_parcelas' => ['nullable', 'integer', 'min:1', 'max:48'],
            'data_pagamento' => ['nullable', 'date'],
            'confirmar_ultrapassagem_orcamento' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor' => Valor::normalizarValorMonetario($this->input('valor')),
            'recorrente' => filter_var($this->input('recorrente'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'total_parcelas' => $this->input('total_parcelas') !== null && $this->input('total_parcelas') !== ''
                ? (int) $this->input('total_parcelas')
                : 1,
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
            'descricao.required' => 'A descrição é obrigatória.',
            'valor.required' => 'O valor é obrigatório.',
            'valor.min' => 'O valor deve ser maior que zero.',
            'data_vencimento.required' => 'A data de vencimento é obrigatória.',
            'tipo.required' => 'O tipo é obrigatório.',
            'forma_pagamento.required' => 'A forma de pagamento é obrigatória.',
        ];
    }
}
