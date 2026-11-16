<?php

namespace App\Http\Requests\EspacoCompartilhado;

use Illuminate\Foundation\Http\FormRequest;

class CriarEspacoCompartilhadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:2', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do espaço.',
            'nome.min' => 'O nome deve ter pelo menos 2 caracteres.',
            'nome.max' => 'O nome deve ter no máximo 80 caracteres.',
        ];
    }
}
