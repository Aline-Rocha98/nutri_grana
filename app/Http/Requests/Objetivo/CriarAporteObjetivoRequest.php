<?php

namespace App\Http\Requests\Objetivo;

use App\Enum\TipoAporteObjetivo;
use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarAporteObjetivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(TipoAporteObjetivo::class)],
            'valor' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'data_aporte' => ['required', 'date'],
            'id_conta_bancaria' => [
                'nullable',
                'required_if:tipo,conta_bancaria',
                'integer',
                'exists:contas_bancarias,id_conta_bancaria',
            ],
            'observacao' => ['nullable', 'string', 'max:255'],
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
            'tipo.required' => 'Informe o tipo do aporte.',
            'valor.required' => 'O valor do aporte é obrigatório.',
            'valor.min' => 'O valor do aporte deve ser maior que zero.',
            'data_aporte.required' => 'A data do aporte é obrigatória.',
            'id_conta_bancaria.required_if' => 'Selecione a conta bancária para retirar o valor.',
            'id_conta_bancaria.exists' => 'A conta bancária selecionada é inválida.',
        ];
    }
}
