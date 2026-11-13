<?php

namespace App\Http\Requests\ContaBancaria;

use App\Enum\AcabamentoVisual;
use App\Enum\SimNao;
use App\Enum\TipoContaBancaria;
use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarContaBancariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contaBancaria = $this->route('contaBancaria');

        return $contaBancaria && ($this->user()?->can('update', $contaBancaria) ?? false);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'saldo_inicial' => ['sometimes', 'numeric', 'decimal:0,2'],
            'tipo' => ['required', Rule::enum(TipoContaBancaria::class)],
            'acabamento' => ['required', Rule::enum(AcabamentoVisual::class)],
            'arquivada' => ['sometimes', Rule::enum(SimNao::class)],
            'padrao_desconto' => ['nullable', Rule::enum(SimNao::class)],
            'exibir_resumo' => ['nullable', Rule::enum(SimNao::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $dados = [
            'acabamento' => $this->input('acabamento') ?: AcabamentoVisual::padrao()->value,
        ];

        if ($this->filled('saldo_inicial')) {
            $dados['saldo_inicial'] = Valor::normalizarValorMonetario($this->input('saldo_inicial'));
        }
        if ($this->has('arquivada')) {
            $dados['arquivada'] = SimNao::fromToggle($this->input('arquivada'))?->value;
        }

        if ($this->exists('padrao_desconto')) {
            $dados['padrao_desconto'] = SimNao::fromToggle($this->input('padrao_desconto'))?->value;
        }

        if ($this->exists('exibir_resumo')) {
            $dados['exibir_resumo'] = SimNao::fromToggle($this->input('exibir_resumo'))?->value;
        }

        $this->merge($dados);
    }

    public function messages(): array
    {
        return [
            'acabamento.required' => 'Selecione o acabamento da conta.',
            'acabamento.enum' => 'O acabamento selecionado é inválido.',
        ];
    }
}
