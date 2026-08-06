<?php

namespace App\Http\Requests\Categoria;

use App\Data\IconesCategoria;
use App\Enum\SimNao;
use App\Enum\TipoCategoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('id_categoria_pai')) {
            $this->merge(['id_categoria_pai' => null]);
        }
    }

    public function rules(): array
    {
        $idUsuario = (int) $this->user()?->getAuthIdentifier();

        return [
            'nome' => ['required', 'string', 'max:100'],
            'tipo' => ['required', Rule::enum(TipoCategoria::class)],
            'icone' => ['required', 'string', Rule::in(IconesCategoria::todos())],
            'id_categoria_pai' => [
                'nullable',
                'integer',
                Rule::exists('categorias', 'id_categoria')
                    ->where(fn ($query) => $query
                        ->where('id_usuario', $idUsuario)
                        ->whereNull('id_categoria_pai')
                        ->where('arquivada', SimNao::Nao->value)
                    ),
            ],
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
            'id_categoria_pai.exists' => 'A categoria principal informada é inválida.',
        ];
    }
}
