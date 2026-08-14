<?php

namespace App\Http\Requests\Orcamento;

use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;

class SimularOrcamentoServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => ['nullable', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'data_orcamento' => ['required', 'date'],
            'data_validade' => ['required', 'date', 'after_or_equal:data_orcamento'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor' => Valor::normalizarValorMonetario($this->input('valor')),
        ]);
    }

    public function messages(): array
    {
        return [
            'valor.required' => 'Informe o valor para simular o impacto.',
            'valor.min' => 'O valor deve ser maior que zero.',
            'data_orcamento.required' => 'A data do orçamento é obrigatória para simular.',
            'data_validade.required' => 'A data de validade é obrigatória para simular.',
            'data_validade.after_or_equal' => 'A data de validade deve ser igual ou posterior à data do orçamento.',
        ];
    }
}
