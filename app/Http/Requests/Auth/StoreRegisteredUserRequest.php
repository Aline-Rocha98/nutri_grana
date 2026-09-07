<?php

namespace App\Http\Requests\Auth;

use App\Enum\MotivosControleFinanceiro;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreRegisteredUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:100',
                Rule::unique(Usuario::class, 'email'),
            ],
            'data_nascimento' => ['required', 'date', 'before:today'],
            'motivo_controle_financeiro' => ['required', Rule::enum(MotivosControleFinanceiro::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => __('validation.usuario.email.unique'),
        ];
    }
}
