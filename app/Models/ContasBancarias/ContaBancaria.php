<?php

namespace App\Models\ContasBancarias;

use App\Enum\SimNao;
use App\Enum\TipoContaBancaria;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'arquivada',
        'padrao_desconto',
        'exibir_resumo',
    ];

    protected function casts(): array
    {
        return [
            'saldo_inicial' => 'decimal:2',
            'tipo' => TipoContaBancaria::class,
            'arquivada' => 'boolean',
            'padrao_desconto' => SimNao::class,
            'exibir_resumo' => SimNao::class,
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
