<?php

namespace App\Http\Requests\Orcamento;

use App\Enum\SimNao;
use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarOrcamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_categoria' => ['required', 'integer'],
            'valor_mensal' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'exibir_dashboard' => ['nullable', Rule::enum(SimNao::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor_mensal' => Valor::normalizarValorMonetario($this->input('valor_mensal')),
            'exibir_dashboard' => SimNao::fromToggle($this->input('exibir_dashboard'))?->value
                ?? SimNao::Nao->value,
        ]);
    }

    public function messages(): array
    {
        return [
            'id_categoria.required' => 'Selecione a categoria pai.',
            'valor_mensal.required' => 'Informe o valor mensal do orçamento.',
            'valor_mensal.min' => 'O valor mensal deve ser maior que zero.',
        ];
    }
}
