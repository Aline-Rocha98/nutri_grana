<?php

namespace App\Http\Requests\EspacoCompartilhado;

use Illuminate\Foundation\Http\FormRequest;

class AssumirLancamentoCompartilhadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'versao' => ['required', 'integer', 'min:1'],
            'id_categoria' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'versao.required' => 'Recarregue a página e tente novamente.',
            'id_categoria.required' => 'Selecione uma das suas categorias para assumir este lançamento.',
        ];
    }
}
