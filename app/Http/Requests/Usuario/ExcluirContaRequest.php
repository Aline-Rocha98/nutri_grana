<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;

class ExcluirContaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->user()) ?? false;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
        ];
    }
}
