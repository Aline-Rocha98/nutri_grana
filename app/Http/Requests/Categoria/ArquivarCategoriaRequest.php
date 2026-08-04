<?php

namespace App\Http\Requests\Categoria;

use App\Enum\SimNao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArquivarCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
        // $categoria = $this->route('categoria');

        // return $categoria && ($this->user()?->can('update', $categoria) ?? false);
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
