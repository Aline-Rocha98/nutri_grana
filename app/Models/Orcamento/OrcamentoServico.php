<?php

namespace App\Models\Orcamento;

use App\Enum\FormaPagamento;
use App\Enum\ModalidadePagamentoOrcamento;
use App\Enum\StatusOrcamentoServico;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\Categoria\Categoria;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrcamentoServico extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'orcamentos_servico';

    protected $primaryKey = 'id_orcamento_servico';

    protected $fillable = [
        'id_usuario',
        'descricao',
        'fornecedor',
        'valor',
        'data_orcamento',
        'data_validade',
        'observacao',
        'status',
        'id_categoria',
        'id_subcategoria',
        'modalidade_pagamento',
        'total_parcelas',
        'forma_pagamento',
        'id_conta_bancaria',
        'id_cartao_credito',
        'data_aprovacao',
        'data_recusa',
        'data_conclusao',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_orcamento' => 'date',
            'data_validade' => 'date',
            'status' => StatusOrcamentoServico::class,
            'modalidade_pagamento' => ModalidadePagamentoOrcamento::class,
            'total_parcelas' => 'integer',
            'forma_pagamento' => FormaPagamento::class,
            'data_aprovacao' => 'datetime',
            'data_recusa' => 'datetime',
            'data_conclusao' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_subcategoria', 'id_categoria');
    }

    public function contaBancaria(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class, 'id_conta_bancaria', 'id_conta_bancaria');
    }

    public function cartaoCredito(): BelongsTo
    {
        return $this->belongsTo(CartaoCredito::class, 'id_cartao_credito', 'id_cartao_credito');
    }

    public function compromissos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'id_orcamento_servico', 'id_orcamento_servico');
    }

    public function idCategoriaCompromisso(): ?int
    {
        return $this->id_subcategoria ?? $this->id_categoria;
    }
}
