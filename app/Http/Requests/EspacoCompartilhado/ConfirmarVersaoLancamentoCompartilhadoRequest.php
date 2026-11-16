<?php

namespace App\Http\Requests\EspacoCompartilhado;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarVersaoLancamentoCompartilhadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'versao' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'versao.required' => 'Recarregue a página e tente novamente.',
        ];
    }
}
