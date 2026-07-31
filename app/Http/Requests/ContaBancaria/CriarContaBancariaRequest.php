<?php

namespace App\Http\Requests\ContaBancaria;

use App\Enum\SimNao;
use App\Enum\TipoContaBancaria;
use App\Models\ContaBancaria\ContaBancaria;
use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarContaBancariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
        // return $this->user()?->can('create', ContaBancaria::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'saldo_inicial' => ['required', 'numeric', 'decimal:0,2'],
            'tipo' => ['required', Rule::enum(TipoContaBancaria::class)],
            'padrao_desconto' => ['nullable', Rule::enum(SimNao::class)],
            'exibir_resumo' => ['nullable', Rule::enum(SimNao::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'saldo_inicial' => Valor::normalizarValorMonetario($this->input('saldo_inicial')),
            'padrao_desconto' => SimNao::fromToggle($this->input('padrao_desconto'))?->value,
            'exibir_resumo' => SimNao::fromToggle($this->input('exibir_resumo'))?->value,
        ]);
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'A descrição da conta bancária é obrigatória.',
            'nome.max' => 'A descrição da conta bancária deve ter no máximo 100 caracteres.',
            'saldo_inicial.required' => 'O saldo inicial é obrigatório.',
            'saldo_inicial.numeric' => 'O saldo inicial deve ser um número válido.',
            'tipo.required' => 'O tipo de conta bancária é obrigatório.',
        ];
    }
}
