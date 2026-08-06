<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;

class SolicitarCodigoAlteracaoSenhaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changePassword', $this->user()) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
