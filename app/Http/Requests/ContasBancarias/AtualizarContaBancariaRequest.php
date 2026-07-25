<?php

namespace App\Http\Requests\ContasBancarias;

use App\Enum\SimNao;
use App\Enum\TipoContaBancaria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarContaBancariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
        // $contaBancaria = $this->route('contaBancaria');

        // return $contaBancaria && ($this->user()?->can('update', $contaBancaria) ?? false);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'saldo_inicial' => ['sometimes', 'numeric', 'decimal:0,2'],
            'tipo' => ['required', Rule::enum(TipoContaBancaria::class)],
            'arquivada' => ['sometimes', 'boolean'],
            'padrao_desconto' => ['nullable', Rule::enum(SimNao::class)],
            'exibir_resumo' => ['nullable', Rule::enum(SimNao::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $dados = [];

        if ($this->filled('saldo_inicial')) {
            $dados['saldo_inicial'] = $this->normalizarValorMonetario($this->input('saldo_inicial'));
        }
        if ($this->has('arquivada')) {
            $dados['arquivada'] = $this->boolean('arquivada');
        }

        if ($this->exists('padrao_desconto')) {
            $dados['padrao_desconto'] = $this->normalizarSimNao($this->input('padrao_desconto'));
        }

        if ($this->exists('exibir_resumo')) {
            $dados['exibir_resumo'] = $this->normalizarSimNao($this->input('exibir_resumo'));
        }

        $this->merge($dados);
    }

    private function normalizarValorMonetario(mixed $valor): mixed
    {
        if (! is_string($valor)) {
            return $valor;
        }

        $normalizado = str_replace(['R$', ' ', '.'], '', $valor);
        $normalizado = str_replace(',', '.', $normalizado);

        return $normalizado === '' ? $valor : $normalizado;
    }

    private function normalizarSimNao(mixed $valor): ?string
    {
        return SimNao::fromToggle($valor)?->value;
    }
}
