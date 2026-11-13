<?php

namespace App\Http\Requests\Alocacao;

use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;

class ResgatarAlocacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_conta_bancaria_saida' => ['required', 'integer'],
            'id_conta_bancaria_destino' => ['required', 'integer'],
            'valor' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'data_resgate' => ['required', 'date'],
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
            'id_conta_bancaria_saida.required' => 'Selecione a conta de onde o valor vai sair.',
            'id_conta_bancaria_destino.required' => 'Selecione a conta que vai receber o resgate.',
            'valor.required' => 'Informe o valor do resgate.',
            'valor.min' => 'O valor do resgate deve ser maior que zero.',
            'data_resgate.required' => 'Informe a data do resgate.',
            'data_resgate.date' => 'Informe uma data válida.',
        ];
    }
}
