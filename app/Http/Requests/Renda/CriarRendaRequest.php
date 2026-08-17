<?php

namespace App\Http\Requests\Renda;

use App\Enum\FrequenciaRecorrencia;
use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarRendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:255'],
            'valor_esperado' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'id_conta_bancaria' => ['required', 'integer'],
            'frequencia' => ['required', Rule::enum(FrequenciaRecorrencia::class)],
            'dia_esperado' => ['required', 'integer', 'min:1', 'max:31'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor_esperado' => Valor::normalizarValorMonetario($this->input('valor_esperado')),
        ]);
    }

    public function messages(): array
    {
        return [
            'descricao.required' => 'A descrição é obrigatória.',
            'valor_esperado.required' => 'O valor esperado é obrigatório.',
            'valor_esperado.min' => 'O valor esperado deve ser maior que zero.',
            'id_conta_bancaria.required' => 'Selecione a conta de recebimento.',
            'frequencia.required' => 'Selecione a frequência.',
            'dia_esperado.required' => 'Informe o dia esperado de recebimento.',
            'dia_esperado.min' => 'O dia deve ser entre 1 e 31.',
            'dia_esperado.max' => 'O dia deve ser entre 1 e 31.',
        ];
    }
}
