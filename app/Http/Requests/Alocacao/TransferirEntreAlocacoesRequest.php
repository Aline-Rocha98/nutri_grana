<?php

namespace App\Http\Requests\Alocacao;

use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;

class TransferirEntreAlocacoesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_alocacao_destino' => ['required', 'integer'],
            'id_conta_bancaria' => ['required', 'integer'],
            'valor' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'data_transferencia' => ['required', 'date'],
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
            'id_alocacao_destino.required' => 'Selecione a alocação que vai receber o valor.',
            'id_conta_bancaria.required' => 'Selecione a conta onde o valor está guardado.',
            'valor.required' => 'Informe o valor da transferência.',
            'valor.min' => 'O valor da transferência deve ser maior que zero.',
            'data_transferencia.required' => 'Informe a data da transferência.',
            'data_transferencia.date' => 'Informe uma data válida.',
        ];
    }
}
