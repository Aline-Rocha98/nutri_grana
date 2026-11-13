<?php

namespace App\Models\Alocacao;

use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\ContaBancaria\MovimentacaoSaldoGuardado;
use App\Models\PlanoFinanceiro\ItemPlanoFinanceiro;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alocacao extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'alocacoes';

    protected $primaryKey = 'id_alocacao';

    protected $fillable = [
        'id_usuario',
        'nome',
        'saldo_consolidado',
        'saldo_consolidado_em',
    ];

    protected function casts(): array
    {
        return [
            'saldo_consolidado' => 'decimal:2',
            'saldo_consolidado_em' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function itensPlano(): HasMany
    {
        return $this->hasMany(ItemPlanoFinanceiro::class, 'id_alocacao', 'id_alocacao');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoSaldoGuardado::class, 'id_alocacao', 'id_alocacao');
    }

    public function movimentacoesComoOrigem(): HasMany
    {
        return $this->hasMany(MovimentacaoSaldoGuardado::class, 'id_alocacao_origem', 'id_alocacao');
    }
}
