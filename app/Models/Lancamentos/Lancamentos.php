<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lancamentos extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mes_id',
        'categoria_id',
        'forma_pagamento_id',
        'id_conta_bancaria',
        'descricao',
        'valor',
        'data_pagamento',
        'data_vencimento',
        'essencial',
        'parcelado',
        'parcela_atual',
        'total_parcelas',
        'lancamento_original_id',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_pagamento' => 'date',
        'data_vencimento' => 'date',
        'essencial' => 'boolean',
        'parcelado' => 'boolean',
        'parcela_atual' => 'integer',
        'total_parcelas' => 'integer',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * Relacionamento com Mes
     */
    public function mes(): BelongsTo
    {
        return $this->belongsTo(Meses::class);
    }

    /**
     * Relacionamento com Categoria
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categorias::class);
    }

    /**
     * Relacionamento com FormaPagamento
     */
    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(FormaPagamentos::class);
    }

    /**
     * Relacionamento com Lancamento Original (para parcelas)
     */
    public function lancamentoOriginal(): BelongsTo
    {
        return $this->belongsTo(Lancamentos::class, 'lancamento_original_id');
    }

    /**
     * Relacionamento com Parcelas (quando é o original)
     */
    public function parcelas(): HasMany
    {
        return $this->hasMany(Lancamentos::class, 'lancamento_original_id');
    }
}
