<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ConfirmarAlteracaoSenhaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changePassword', $this->user()) ?? false;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'size:6'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
