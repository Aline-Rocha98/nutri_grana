<?php

namespace App\Http\Requests\CartaoCredito;

use App\Enum\SimNao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArquivarCartaoCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'arquivada' => ['required', Rule::enum(SimNao::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('arquivada')) {
            $this->merge([
                'arquivada' => SimNao::fromToggle($this->input('arquivada'))?->value,
            ]);
        }
    }
}
