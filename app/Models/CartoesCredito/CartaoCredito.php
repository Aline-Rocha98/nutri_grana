<?php

namespace App\Models\CartoesCredito;

use App\Enum\BandeiraCartaoCredito;
use App\Enum\SimNao;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartaoCredito extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'cartoes_credito';

    protected $primaryKey = 'id_cartao_credito';

    protected $fillable = [
        'id_usuario',
        'nome',
        'limite_total',
        'dia_fechamento',
        'dia_vencimento',
        'bandeira',
        'padrao',
        'arquivada',
    ];

    protected function casts(): array
    {
        return [
            'limite_total' => 'decimal:2',
            'dia_fechamento' => 'integer',
            'dia_vencimento' => 'integer',
            'bandeira' => BandeiraCartaoCredito::class,
            'padrao' => SimNao::class,
            'arquivada' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
