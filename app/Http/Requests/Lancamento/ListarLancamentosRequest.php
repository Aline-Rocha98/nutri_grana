<?php

namespace App\Http\Requests\Lancamento;

use App\Enum\TipoLancamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarLancamentosRequest extends FormRequest
{
    public const STATUS_EFETIVADOS = 'efetivados';

    public const STATUS_PENDENTES = 'pendentes';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['nullable', Rule::enum(TipoLancamento::class)],
            'status' => ['nullable', Rule::in([self::STATUS_EFETIVADOS, self::STATUS_PENDENTES])],
            'situacao' => ['nullable', 'string'],
            'id_categoria' => ['nullable', 'integer'],
            'busca' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $busca = trim((string) $this->input('busca', ''));

        $this->merge([
            'busca' => $busca !== '' ? $busca : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'tipo.enum' => 'O tipo informado é inválido.',
            'status.in' => 'O status informado é inválido.',
            'busca.max' => 'A busca deve ter no máximo 100 caracteres.',
        ];
    }
}
