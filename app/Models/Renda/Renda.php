<?php

namespace App\Models\Renda;

use App\Enum\FrequenciaRecorrencia;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Renda extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'rendas';

    protected $primaryKey = 'id_renda';

    protected $fillable = [
        'id_usuario',
        'descricao',
        'valor_esperado',
        'id_conta_bancaria',
        'frequencia',
        'dia_esperado',
        'data_inicio',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'valor_esperado' => 'decimal:2',
            'frequencia' => FrequenciaRecorrencia::class,
            'dia_esperado' => 'integer',
            'data_inicio' => 'date',
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

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'id_renda', 'id_renda');
    }
}
