<?php

namespace App\Http\Requests\ContasBancarias;

use App\Enum\TipoContaBancaria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarContaBancariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contaBancaria = $this->route('contaBancaria');

        return $contaBancaria
            && ($this->user()?->can('update', $contaBancaria) ?? false);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'saldo_inicial' => ['required', 'numeric', 'decimal:0,2'],
            'tipo' => ['required', Rule::enum(TipoContaBancaria::class)],
            'arquivada' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $dados = [
            'saldo_inicial' => $this->normalizarValorMonetario($this->input('saldo_inicial')),
        ];

        if ($this->has('arquivada')) {
            $dados['arquivada'] = $this->boolean('arquivada');
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
}
