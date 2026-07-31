<?php

namespace App\Http\Requests\CartaoCredito;

use App\Enum\BandeiraCartaoCredito;
use App\Enum\SimNao;
use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarCartaoCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
        // $cartaoCredito = $this->route('cartaoCredito');

        // return $cartaoCredito && ($this->user()?->can('update', $cartaoCredito) ?? false);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'limite_total' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'dia_fechamento' => ['required', 'integer', 'min:1', 'max:31'],
            'dia_vencimento' => ['required', 'integer', 'min:1', 'max:31'],
            'bandeira' => ['required', Rule::enum(BandeiraCartaoCredito::class)],
            'padrao' => ['nullable', Rule::enum(SimNao::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $dados = [
            'limite_total' => Valor::normalizarValorMonetario($this->input('limite_total')),
        ];

        if ($this->exists('padrao')) {
            $dados['padrao'] = SimNao::fromToggle($this->input('padrao'))?->value;
        }

        $this->merge($dados);
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do cartão é obrigatório.',
            'nome.max' => 'O nome do cartão deve ter no máximo 100 caracteres.',
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
        ];
    }
}
