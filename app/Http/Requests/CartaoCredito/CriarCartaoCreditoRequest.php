<?php

namespace App\Http\Requests\CartaoCredito;

use App\Enum\SimNao;
use App\Http\Requests\CartaoCredito\Concerns\ValidaCamposCartaoCredito;
use App\Models\CartaoCredito\CartaoCredito;
use Illuminate\Foundation\Http\FormRequest;

class CriarCartaoCreditoRequest extends FormRequest
{
    use ValidaCamposCartaoCredito;

    public function authorize(): bool
    {
        return $this->user()?->can('create', CartaoCredito::class) ?? false;
    }

    public function rules(): array
    {
        return $this->regrasCamposCartaoCredito();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            ...$this->normalizarCamposCartaoCredito(),
            'padrao' => SimNao::fromToggle($this->input('padrao'))?->value,
        ]);
    }

    public function messages(): array
    {
        return $this->mensagensCamposCartaoCredito();
    }
}
