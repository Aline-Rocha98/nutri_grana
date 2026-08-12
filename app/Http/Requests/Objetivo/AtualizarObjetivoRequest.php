<?php

namespace App\Http\Requests\Objetivo;

use App\Enum\SimNao;
use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarObjetivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:255'],
            'valor_meta' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'data_limite' => ['required', 'date'],
            'exibir_dashboard' => ['nullable', Rule::enum(SimNao::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor_meta' => Valor::normalizarValorMonetario($this->input('valor_meta')),
            'exibir_dashboard' => SimNao::fromToggle($this->input('exibir_dashboard'))?->value
                ?? SimNao::Nao->value,
        ]);
    }

    public function messages(): array
    {
        return [
            'descricao.required' => 'A descrição do objetivo é obrigatória.',
            'descricao.max' => 'A descrição deve ter no máximo 255 caracteres.',
            'valor_meta.required' => 'O valor da meta é obrigatório.',
            'valor_meta.min' => 'O valor da meta deve ser maior que zero.',
            'data_limite.required' => 'A data limite é obrigatória.',
        ];
    }
}
