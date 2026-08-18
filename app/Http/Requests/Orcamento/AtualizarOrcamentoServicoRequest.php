<?php

namespace App\Http\Requests\Orcamento;

use App\Enum\FormaPagamento;
use App\Enum\ModalidadePagamentoOrcamento;
use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarOrcamentoServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $conta = $this->input('forma_pagamento') === FormaPagamento::ContaBancaria->value;
        $cartao = $this->input('forma_pagamento') === FormaPagamento::CartaoCredito->value;

        return [
            'descricao' => ['required', 'string', 'max:255'],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'data_orcamento' => ['required', 'date'],
            'data_validade' => ['required', 'date', 'after_or_equal:data_orcamento'],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'id_categoria' => ['nullable', 'integer'],
            'id_subcategoria' => ['nullable', 'integer'],
            'modalidade_pagamento' => ['required', Rule::enum(ModalidadePagamentoOrcamento::class)],
            'forma_pagamento' => ['required', Rule::enum(FormaPagamento::class)],
            'id_conta_bancaria' => [$conta ? 'required' : 'nullable', 'integer'],
            'id_cartao_credito' => [$cartao ? 'required' : 'nullable', 'integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $modalidade = $this->input('modalidade_pagamento') ?: ModalidadePagamentoOrcamento::AVista->value;
        $forma = $this->input('forma_pagamento') ?: FormaPagamento::ContaBancaria->value;

        $this->merge([
            'valor' => Valor::normalizarValorMonetario($this->input('valor')),
            'fornecedor' => $this->filled('fornecedor') ? $this->input('fornecedor') : null,
            'observacao' => $this->filled('observacao') ? $this->input('observacao') : null,
            'id_categoria' => $this->filled('id_categoria') ? (int) $this->input('id_categoria') : null,
            'id_subcategoria' => $this->filled('id_subcategoria') ? (int) $this->input('id_subcategoria') : null,
            'modalidade_pagamento' => $modalidade,
            'forma_pagamento' => $forma,
            'id_conta_bancaria' => $forma === FormaPagamento::ContaBancaria->value && $this->filled('id_conta_bancaria')
                ? (int) $this->input('id_conta_bancaria')
                : null,
            'id_cartao_credito' => $forma === FormaPagamento::CartaoCredito->value && $this->filled('id_cartao_credito')
                ? (int) $this->input('id_cartao_credito')
                : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'descricao.required' => 'A descrição da cotação é obrigatória.',
            'valor.required' => 'O valor estimado é obrigatório.',
            'valor.min' => 'O valor deve ser maior que zero.',
            'data_orcamento.required' => 'A data da cotação é obrigatória.',
            'data_validade.required' => 'A data de validade é obrigatória.',
            'data_validade.after_or_equal' => 'A data de validade deve ser igual ou posterior à data da cotação.',
            'modalidade_pagamento.required' => 'Informe se o pagamento será à vista ou parcelado.',
            'forma_pagamento.required' => 'Informe a forma de pagamento.',
            'id_conta_bancaria.required' => 'Selecione a conta bancária.',
            'id_cartao_credito.required' => 'Selecione o cartão de crédito.',
        ];
    }
}
