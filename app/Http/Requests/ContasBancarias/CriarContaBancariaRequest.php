<?php

namespace App\Http\Requests\ContasBancarias;

use App\Enum\TipoContaBancaria;
use App\Models\ContasBancarias\ContaBancaria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarContaBancariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return $this->user()?->can('create', ContaBancaria::class) ?? false;
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'saldo_inicial' => ['required', 'numeric', 'decimal:0,2'],
            'tipo' => ['required', Rule::enum(TipoContaBancaria::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'saldo_inicial' => $this->normalizarValorMonetario($this->input('saldo_inicial')),
        ]);
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
