<?php

namespace App\Http\Requests\Alocacao;

use Illuminate\Foundation\Http\FormRequest;

class CriarAlocacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome da alocação.',
            'nome.max' => 'O nome da alocação pode ter no máximo 120 caracteres.',
        ];
    }
}
