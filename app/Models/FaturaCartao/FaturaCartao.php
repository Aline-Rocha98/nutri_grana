<?php

namespace App\Models\FaturaCartao;

use App\Enum\SituacaoFatura;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaturaCartao extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'faturas_cartao';

    protected $primaryKey = 'id_fatura_cartao';

    protected $fillable = [
        'id_usuario',
        'id_cartao_credito',
        'ano',
        'mes',
        'data_fechamento',
        'data_vencimento',
        'situacao',
        'id_conta_bancaria_pagamento',
        'id_lancamento_pagamento',
    ];

    protected function casts(): array
    {
        return [
            'ano' => 'integer',
            'mes' => 'integer',
            'data_fechamento' => 'date',
            'data_vencimento' => 'date',
            'situacao' => SituacaoFatura::class,
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function cartaoCredito(): BelongsTo
    {
        return $this->belongsTo(CartaoCredito::class, 'id_cartao_credito', 'id_cartao_credito');
    }

    public function contaBancariaPagamento(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class, 'id_conta_bancaria_pagamento', 'id_conta_bancaria');
    }

    public function lancamentoPagamento(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class, 'id_lancamento_pagamento', 'id_lancamento');
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'id_fatura_cartao', 'id_fatura_cartao');
    }

    public function estaAberta(): bool
    {
        return $this->situacao === SituacaoFatura::Aberta || $this->situacao === SituacaoFatura::Fechada;
    }
}
