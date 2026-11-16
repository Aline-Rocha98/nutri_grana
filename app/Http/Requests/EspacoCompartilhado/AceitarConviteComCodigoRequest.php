<?php

namespace App\Http\Requests\EspacoCompartilhado;

use Illuminate\Foundation\Http\FormRequest;

class AceitarConviteComCodigoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email_convidante' => ['required', 'string', 'email', 'max:100'],
            'codigo' => ['required', 'string', 'max:12'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email_convidante' => mb_strtolower(trim((string) $this->input('email_convidante'))),
            'codigo' => mb_strtoupper(trim((string) $this->input('codigo'))),
        ]);
    }

    public function messages(): array
    {
        return [
            'email_convidante.required' => 'Informe o e-mail de quem enviou o convite.',
            'email_convidante.email' => 'Informe um e-mail válido.',
            'email_convidante.max' => 'O e-mail deve ter no máximo 100 caracteres.',
            'codigo.required' => 'Informe o código recebido.',
            'codigo.max' => 'Código inválido.',
        ];
    }
}
