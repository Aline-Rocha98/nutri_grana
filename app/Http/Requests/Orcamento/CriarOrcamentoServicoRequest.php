<?php

namespace App\Http\Requests\Orcamento;

use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;

class CriarOrcamentoServicoRequest extends FormRequest
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
            'data_orcamento' => ['required', 'date'],
            'data_validade' => ['required', 'date', 'after_or_equal:data_orcamento'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor' => Valor::normalizarValorMonetario($this->input('valor')),
            'observacao' => $this->filled('observacao') ? $this->input('observacao') : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'descricao.required' => 'A descrição do orçamento é obrigatória.',
            'descricao.max' => 'A descrição deve ter no máximo 255 caracteres.',
            'valor.required' => 'O valor do orçamento é obrigatório.',
            'valor.min' => 'O valor deve ser maior que zero.',
            'data_orcamento.required' => 'A data do orçamento é obrigatória.',
            'data_validade.required' => 'A data de validade é obrigatória.',
            'data_validade.after_or_equal' => 'A data de validade deve ser igual ou posterior à data do orçamento.',
            'observacao.max' => 'A observação deve ter no máximo 2000 caracteres.',
        ];
    }
}
