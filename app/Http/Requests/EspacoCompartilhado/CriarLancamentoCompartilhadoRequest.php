<?php

namespace App\Http\Requests\EspacoCompartilhado;

use App\Enum\FrequenciaRecorrencia;
use App\Support\Data\Valor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarLancamentoCompartilhadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999999.99'],
            'data_vencimento' => ['required', 'date'],
            'id_usuario_responsavel' => ['required', 'integer'],
            'id_categoria' => ['required', 'integer'],
            'observacao' => ['nullable', 'string', 'max:1000'],
            'recorrente' => ['nullable', 'boolean'],
            'frequencia_recorrencia' => ['nullable', 'required_if:recorrente,true', Rule::enum(FrequenciaRecorrencia::class)],
            'recorrencia_ate' => ['nullable', 'date', 'after_or_equal:data_vencimento'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor' => Valor::normalizarValorMonetario($this->input('valor')),
            'recorrente' => filter_var($this->input('recorrente'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
        ]);
    }

    public function messages(): array
    {
        return [
            'descricao.required' => 'A descrição é obrigatória.',
            'valor.required' => 'O valor é obrigatório.',
            'valor.min' => 'O valor deve ser maior que zero.',
            'data_vencimento.required' => 'A data de vencimento é obrigatória.',
            'id_usuario_responsavel.required' => 'Selecione o responsável pelo lançamento.',
            'id_categoria.required' => 'Selecione uma categoria do responsável.',
            'frequencia_recorrencia.required_if' => 'Selecione a frequência da recorrência.',
            'recorrencia_ate.after_or_equal' => 'O fim da recorrência deve ser igual ou posterior ao vencimento.',
        ];
    }
}
