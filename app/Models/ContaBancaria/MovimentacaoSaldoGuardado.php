<?php

namespace App\Models\ContaBancaria;

use App\Enum\TipoMovimentacaoSaldoGuardado;
use App\Models\Alocacao\Alocacao;
use App\Models\Lancamento\Lancamento;
use App\Models\Objetivo\AporteObjetivo;
use App\Models\PlanoFinanceiro\ItemPlanoFinanceiro;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoSaldoGuardado extends Model
{
    protected $table = 'movimentacoes_saldo_guardado';

    protected $primaryKey = 'id_movimentacao_saldo_guardado';

    protected $fillable = [
        'id_usuario',
        'id_conta_bancaria',
        'id_conta_bancaria_origem',
        'id_conta_bancaria_resgate',
        'id_item_plano',
        'id_alocacao',
        'id_alocacao_origem',
        'id_lancamento_origem',
        'id_aporte_objetivo',
        'tipo',
        'valor',
        'data_movimentacao',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimentacaoSaldoGuardado::class,
            'valor' => 'decimal:2',
            'data_movimentacao' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function contaBancaria(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class, 'id_conta_bancaria', 'id_conta_bancaria');
    }

    public function contaBancariaOrigem(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class, 'id_conta_bancaria_origem', 'id_conta_bancaria');
    }

    public function contaBancariaResgate(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class, 'id_conta_bancaria_resgate', 'id_conta_bancaria');
    }

    public function alocacao(): BelongsTo
    {
        return $this->belongsTo(Alocacao::class, 'id_alocacao', 'id_alocacao');
    }

    public function alocacaoOrigem(): BelongsTo
    {
        return $this->belongsTo(Alocacao::class, 'id_alocacao_origem', 'id_alocacao');
    }

    public function itemPlanoFinanceiro(): BelongsTo
    {
        return $this->belongsTo(ItemPlanoFinanceiro::class, 'id_item_plano', 'id_item_plano');
    }

    public function lancamentoOrigem(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class, 'id_lancamento_origem', 'id_lancamento');
    }

    public function aporteObjetivo(): BelongsTo
    {
        return $this->belongsTo(AporteObjetivo::class, 'id_aporte_objetivo', 'id_aporte_objetivo');
    }
}
