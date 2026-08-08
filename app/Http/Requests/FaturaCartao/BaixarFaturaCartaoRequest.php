<?php

namespace App\Http\Requests\FaturaCartao;

use Illuminate\Foundation\Http\FormRequest;

class BaixarFaturaCartaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_conta_bancaria' => ['required', 'integer'],
            'data_pagamento' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_conta_bancaria.required' => 'Selecione a conta bancária para o pagamento.',
        ];
    }
}
