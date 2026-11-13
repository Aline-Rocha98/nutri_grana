<?php

namespace App\Http\Requests\PlanoFinanceiro;

use App\Enum\CorItemPlanoFinanceiro;
use App\Enum\TipoCalculoItemPlano;
use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarPlanoFinanceiroRequest extends FormRequest
{
    private const LIMITE_ITENS_DO_PLANO = 20;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'itens' => ['required', 'array', 'min:1', 'max:'.self::LIMITE_ITENS_DO_PLANO],
            'itens.*.id' => ['nullable', 'integer'],
            'itens.*.nome' => ['required', 'string', 'max:120'],
            'itens.*.descricao' => ['nullable', 'string', 'max:255'],
            'itens.*.cor' => ['required', Rule::enum(CorItemPlanoFinanceiro::class)],
            'itens.*.tipo_calculo' => ['required', Rule::enum(TipoCalculoItemPlano::class)],
            'itens.*.valor' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'itens.*.separar_do_saldo' => ['required', 'boolean'],
            'itens.*.id_conta_bancaria_destino' => ['nullable', 'integer'],
            'itens.*.id_objetivo' => ['nullable', 'integer'],
            'itens.*.id_alocacao' => ['nullable', 'integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'itens' => $this->normalizarItensDoPlano(),
        ]);
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do plano.',
            'itens.required' => 'Adicione pelo menos uma categoria ao plano.',
            'itens.min' => 'Adicione pelo menos uma categoria ao plano.',
            'itens.max' => 'O plano pode ter no máximo '.self::LIMITE_ITENS_DO_PLANO.' categorias.',
            'itens.*.nome.required' => 'Informe o nome da categoria.',
            'itens.*.cor.required' => 'Escolha uma cor para a categoria.',
            'itens.*.tipo_calculo.required' => 'Selecione como o valor será calculado.',
            'itens.*.valor.required' => 'Informe o percentual ou valor da categoria.',
            'itens.*.valor.min' => 'O valor da categoria deve ser maior que zero.',
        ];
    }

    private function normalizarItensDoPlano(): array
    {
        $itens = $this->input('itens');

        if (! is_array($itens)) {
            return [];
        }

        return array_map(
            fn ($item) => is_array($item)
                ? [
                    ...$item,
                    'valor' => ($item['tipo_calculo'] ?? null) === TipoCalculoItemPlano::ValorFixo->value
                        ? Valor::normalizarValorMonetario($item['valor'] ?? null)
                        : ($item['valor'] ?? null),
                    'id_conta_bancaria_destino' => ($item['id_conta_bancaria_destino'] ?? null) ?: null,
                    'id_objetivo' => ($item['id_objetivo'] ?? null) ?: null,
                    'id_alocacao' => ($item['id_alocacao'] ?? null) ?: null,
                ]
                : $item,
            array_values($itens),
        );
    }
}
