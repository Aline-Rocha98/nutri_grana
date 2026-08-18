<?php

namespace App\Http\Requests\Orcamento;

use App\Enum\FormaPagamento;
use App\Enum\ModalidadePagamentoOrcamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AprovarOrcamentoServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $parcelado = $this->input('modalidade_pagamento') === ModalidadePagamentoOrcamento::Parcelado->value;
        $conta = $this->input('forma_pagamento') === FormaPagamento::ContaBancaria->value;
        $cartao = $this->input('forma_pagamento') === FormaPagamento::CartaoCredito->value;

        return [
            'modalidade_pagamento' => ['required', Rule::enum(ModalidadePagamentoOrcamento::class)],
            'total_parcelas' => [
                $parcelado ? 'required' : 'nullable',
                'integer',
                $parcelado ? 'min:2' : 'min:1',
                'max:48',
            ],
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
            'modalidade_pagamento' => $modalidade,
            'forma_pagamento' => $forma,
            'total_parcelas' => $modalidade === ModalidadePagamentoOrcamento::Parcelado->value
                ? (int) ($this->input('total_parcelas') ?: 2)
                : 1,
            'id_conta_bancaria' => $forma === FormaPagamento::ContaBancaria->value
                ? $this->input('id_conta_bancaria')
                : null,
            'id_cartao_credito' => $forma === FormaPagamento::CartaoCredito->value
                ? $this->input('id_cartao_credito')
                : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'modalidade_pagamento.required' => 'Informe se o pagamento será à vista ou parcelado.',
            'total_parcelas.required' => 'Informe a quantidade de parcelas.',
            'total_parcelas.min' => 'Para pagamento parcelado, informe ao menos 2 parcelas.',
            'forma_pagamento.required' => 'Informe a forma de pagamento.',
            'id_conta_bancaria.required' => 'Selecione a conta bancária.',
            'id_cartao_credito.required' => 'Selecione o cartão de crédito.',
        ];
    }
}
