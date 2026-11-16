<?php

namespace App\Http\Requests\EspacoCompartilhado;

use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;

class AtualizarLancamentoCompartilhadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'versao' => ['required', 'integer', 'min:1'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999999.99'],
            'data_vencimento' => ['required', 'date'],
            'id_usuario_responsavel' => ['required', 'integer'],
            'id_categoria' => ['required', 'integer'],
            'observacao' => ['nullable', 'string', 'max:1000'],
            'aplicar_na_recorrencia' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor' => Valor::normalizarValorMonetario($this->input('valor')),
            'aplicar_na_recorrencia' => filter_var(
                $this->input('aplicar_na_recorrencia'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? false,
        ]);
    }

    public function messages(): array
    {
        return [
            'versao.required' => 'Recarregue a página e tente novamente.',
            'descricao.required' => 'A descrição é obrigatória.',
            'valor.required' => 'O valor é obrigatório.',
            'valor.min' => 'O valor deve ser maior que zero.',
            'data_vencimento.required' => 'A data de vencimento é obrigatória.',
            'id_usuario_responsavel.required' => 'Selecione o responsável pelo lançamento.',
            'id_categoria.required' => 'Selecione uma categoria do responsável.',
        ];
    }
}
