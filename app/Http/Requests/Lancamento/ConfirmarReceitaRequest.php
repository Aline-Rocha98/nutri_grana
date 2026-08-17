<?php

namespace App\Http\Requests\Lancamento;

use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmarReceitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valor_recebido' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'data_recebimento' => ['required', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor_recebido' => Valor::normalizarValorMonetario($this->input('valor_recebido')),
        ]);
    }

    public function messages(): array
    {
        return [
            'valor_recebido.required' => 'Informe o valor recebido.',
            'valor_recebido.min' => 'O valor recebido deve ser maior que zero.',
            'data_recebimento.required' => 'Informe a data de recebimento.',
        ];
    }
}
