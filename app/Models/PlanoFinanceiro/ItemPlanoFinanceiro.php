<?php

namespace App\Models\PlanoFinanceiro;

use App\Enum\CorItemPlanoFinanceiro;
use App\Enum\SimNao;
use App\Enum\TipoCalculoItemPlano;
use App\Models\Alocacao\Alocacao;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\ContaBancaria\MovimentacaoSaldoGuardado;
use App\Models\Objetivo\Objetivo;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemPlanoFinanceiro extends Model
{
    protected $table = 'itens_plano';

    protected $primaryKey = 'id_item_plano';

    protected $fillable = [
        'id_plano_financeiro',
        'id_usuario',
        'id_alocacao',
        'nome',
        'descricao',
        'cor',
        'tipo_calculo',
        'valor',
        'separar_do_saldo',
        'id_conta_bancaria_destino',
        'id_objetivo',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'cor' => CorItemPlanoFinanceiro::class,
            'tipo_calculo' => TipoCalculoItemPlano::class,
            'valor' => 'decimal:2',
            'separar_do_saldo' => SimNao::class,
            'ordem' => 'integer',
        ];
    }

    public function planoFinanceiro(): BelongsTo
    {
        return $this->belongsTo(PlanoFinanceiro::class, 'id_plano_financeiro', 'id_plano_financeiro');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function alocacao(): BelongsTo
    {
        return $this->belongsTo(Alocacao::class, 'id_alocacao', 'id_alocacao');
    }

    public function contaBancariaDestino(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class, 'id_conta_bancaria_destino', 'id_conta_bancaria');
    }

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(Objetivo::class, 'id_objetivo', 'id_objetivo');
    }

    public function movimentacoesSaldoGuardado(): HasMany
    {
        return $this->hasMany(MovimentacaoSaldoGuardado::class, 'id_item_plano', 'id_item_plano');
    }

    public function separaDoSaldo(): bool
    {
        return $this->separar_do_saldo === SimNao::Sim;
    }

    public function ehPercentual(): bool
    {
        return $this->tipo_calculo === TipoCalculoItemPlano::Percentual;
    }
}
