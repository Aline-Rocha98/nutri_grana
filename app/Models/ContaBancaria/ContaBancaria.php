<?php

namespace App\Models\ContaBancaria;

use App\Enum\AcabamentoVisual;
use App\Enum\SimNao;
use App\Enum\TipoContaBancaria;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaBancaria extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'contas_bancarias';

    protected $primaryKey = 'id_conta_bancaria';

    protected $fillable = [
        'id_usuario',
        'nome',
        'saldo_inicial',
        'tipo',
        'acabamento',
        'arquivada',
        'padrao_desconto',
        'exibir_resumo',
    ];

    protected function casts(): array
    {
        return [
            'saldo_inicial' => 'decimal:2',
            'tipo' => TipoContaBancaria::class,
            'acabamento' => AcabamentoVisual::class,
            'arquivada' => SimNao::class,
            'padrao_desconto' => SimNao::class,
            'exibir_resumo' => SimNao::class,
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function movimentacoesSaldoGuardado(): HasMany
    {
        return $this->hasMany(MovimentacaoSaldoGuardado::class, 'id_conta_bancaria', 'id_conta_bancaria');
    }
}
