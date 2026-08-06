<?php

namespace App\Http\Requests\Usuario;

use App\Enum\MotivosControleFinanceiro;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->user()) ?? false;
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
                Rule::unique(Usuario::class, 'email')->ignore($this->user()->id_usuario, 'id_usuario'),
            ],
            'data_nascimento' => ['required', 'date', 'before:today'],
            'motivo_controle_financeiro' => ['required', Rule::enum(MotivosControleFinanceiro::class)],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
