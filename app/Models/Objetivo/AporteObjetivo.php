<?php

namespace App\Models\Objetivo;

use App\Enum\TipoAporteObjetivo;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AporteObjetivo extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'aportes_objetivo';

    protected $primaryKey = 'id_aporte_objetivo';

    protected $fillable = [
        'id_objetivo',
        'id_usuario',
        'tipo',
        'valor',
        'data_aporte',
        'id_conta_bancaria',
        'id_lancamento',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoAporteObjetivo::class,
            'valor' => 'decimal:2',
            'data_aporte' => 'date',
        ];
    }

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(Objetivo::class, 'id_objetivo', 'id_objetivo');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function contaBancaria(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class, 'id_conta_bancaria', 'id_conta_bancaria');
    }

    public function lancamento(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class, 'id_lancamento', 'id_lancamento');
    }
}
