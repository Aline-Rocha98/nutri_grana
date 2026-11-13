<?php

namespace App\Http\Requests\ContaBancaria;

use App\Enum\SimNao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArquivarContaBancariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contaBancaria = $this->route('contaBancaria');

        return $contaBancaria && ($this->user()?->can('update', $contaBancaria) ?? false);
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
