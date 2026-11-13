<?php

namespace App\Http\Requests\CartaoCredito;

use App\Enum\SimNao;
use App\Http\Requests\CartaoCredito\Concerns\ValidaCamposCartaoCredito;
use Illuminate\Foundation\Http\FormRequest;

class AtualizarCartaoCreditoRequest extends FormRequest
{
    use ValidaCamposCartaoCredito;

    public function authorize(): bool
    {
        $cartaoCredito = $this->route('cartaoCredito');

        return $cartaoCredito && ($this->user()?->can('update', $cartaoCredito) ?? false);
    }

    public function rules(): array
    {
        return $this->regrasCamposCartaoCredito();
    }

    protected function prepareForValidation(): void
    {
        $dados = $this->normalizarCamposCartaoCredito();

        if ($this->exists('padrao')) {
            $dados['padrao'] = SimNao::fromToggle($this->input('padrao'))?->value;
        }

        $this->merge($dados);
    }

    public function messages(): array
    {
        return $this->mensagensCamposCartaoCredito();
    }
}
