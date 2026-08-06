<?php

namespace App\Http\Requests\Categoria;

use App\Data\IconesCategoria;
use App\Enum\TipoCategoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $categoria = $this->route('categoria');

        return $categoria && ($this->user()?->can('update', $categoria) ?? false);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'tipo' => ['required', Rule::enum(TipoCategoria::class)],
            'icone' => ['required', 'string', Rule::in(IconesCategoria::todos())],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da categoria é obrigatório.',
            'nome.max' => 'O nome da categoria deve ter no máximo 100 caracteres.',
            'tipo.required' => 'O tipo da categoria é obrigatório.',
            'icone.required' => 'O ícone da categoria é obrigatório.',
            'icone.in' => 'O ícone selecionado é inválido.',
        ];
    }
}
