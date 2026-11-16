<?php

namespace App\Http\Requests\EspacoCompartilhado;

use Illuminate\Foundation\Http\FormRequest;

class CriarConviteEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Informe o e-mail da pessoa cadastrada que você quer convidar.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve ter no máximo 100 caracteres.',
        ];
    }
}
