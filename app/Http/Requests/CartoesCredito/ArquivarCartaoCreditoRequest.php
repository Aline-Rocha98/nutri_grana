<?php

namespace App\Http\Requests\CartoesCredito;

use Illuminate\Foundation\Http\FormRequest;

class ArquivarCartaoCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'arquivada' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('arquivada')) {
            $this->merge([
                'arquivada' => $this->boolean('arquivada'),
            ]);
        }
    }
}
