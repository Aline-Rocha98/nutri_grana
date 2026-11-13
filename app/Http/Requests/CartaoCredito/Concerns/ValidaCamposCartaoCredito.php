<?php

namespace App\Http\Requests\CartaoCredito\Concerns;

use App\Enum\AcabamentoVisual;
use App\Enum\BandeiraCartaoCredito;
use App\Enum\SimNao;
use App\Support\Data\Valor;
use Illuminate\Validation\Rule;

trait ValidaCamposCartaoCredito
{
    protected function regrasCamposCartaoCredito(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'nome_titular' => ['required', 'string', 'max:100'],
            'limite_total' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'dia_fechamento' => ['required', 'integer', 'min:1', 'max:31'],
            'dia_vencimento' => ['required', 'integer', 'min:1', 'max:31'],
            'bandeira' => ['required', Rule::enum(BandeiraCartaoCredito::class)],
            'ultimos_digitos' => ['nullable', 'digits:4'],
            'acabamento' => ['required', Rule::enum(AcabamentoVisual::class)],
            'padrao' => ['nullable', Rule::enum(SimNao::class)],
        ];
    }

    protected function normalizarCamposCartaoCredito(): array
    {
        $ultimosDigitos = preg_replace('/\D/', '', (string) $this->input('ultimos_digitos', ''));

        return [
            'nome_titular' => trim((string) $this->input('nome_titular', '')),
            'limite_total' => Valor::normalizarValorMonetario($this->input('limite_total')),
            'ultimos_digitos' => $ultimosDigitos !== '' ? $ultimosDigitos : null,
            'acabamento' => $this->input('acabamento') ?: AcabamentoVisual::padrao()->value,
        ];
    }

    protected function mensagensCamposCartaoCredito(): array
    {
        return [
            'nome.required' => 'O nome do cartão é obrigatório.',
            'nome.max' => 'O nome do cartão deve ter no máximo 100 caracteres.',
            'nome_titular.required' => 'O nome do titular é obrigatório.',
            'nome_titular.max' => 'O nome do titular deve ter no máximo 100 caracteres.',
            'limite_total.required' => 'O limite total é obrigatório.',
            'limite_total.numeric' => 'O limite total deve ser um número válido.',
            'limite_total.min' => 'O limite total não pode ser negativo.',
            'dia_fechamento.required' => 'O dia de fechamento é obrigatório.',
            'dia_fechamento.min' => 'O dia de fechamento deve ser entre 1 e 31.',
            'dia_fechamento.max' => 'O dia de fechamento deve ser entre 1 e 31.',
            'dia_vencimento.required' => 'O dia de vencimento é obrigatório.',
            'dia_vencimento.min' => 'O dia de vencimento deve ser entre 1 e 31.',
            'dia_vencimento.max' => 'O dia de vencimento deve ser entre 1 e 31.',
            'bandeira.required' => 'A bandeira do cartão é obrigatória.',
            'ultimos_digitos.digits' => 'Informe exatamente os 4 últimos dígitos do cartão.',
            'acabamento.required' => 'Selecione o acabamento do cartão.',
            'acabamento.enum' => 'O acabamento selecionado é inválido.',
        ];
    }
}
