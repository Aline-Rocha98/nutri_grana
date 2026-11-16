<?php

namespace App\Models\EspacoCompartilhado;

use App\Enum\FrequenciaRecorrencia;
use App\Enum\StatusRecorrenciaCompartilhada;
use App\Models\Categoria\Categoria;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecorrenciaCompartilhada extends Model
{
    protected $table = 'recorrencias_compartilhadas';

    protected $primaryKey = 'id_recorrencia_compartilhada';

    protected $fillable = [
        'id_espaco_compartilhado',
        'id_usuario_responsavel',
        'id_categoria',
        'descricao',
        'valor',
        'frequencia',
        'data_inicio',
        'data_fim',
        'gerada_ate',
        'status',
        'criado_por',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'frequencia' => FrequenciaRecorrencia::class,
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'gerada_ate' => 'date',
            'status' => StatusRecorrenciaCompartilhada::class,
        ];
    }

    public function espaco(): BelongsTo
    {
        return $this->belongsTo(EspacoCompartilhado::class, 'id_espaco_compartilhado', 'id_espaco_compartilhado');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_responsavel', 'id_usuario');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    public function ocorrencias(): HasMany
    {
        return $this->hasMany(LancamentoCompartilhado::class, 'id_recorrencia_compartilhada', 'id_recorrencia_compartilhada');
    }

    public function estaAtiva(): bool
    {
        return $this->status === StatusRecorrenciaCompartilhada::Ativa;
    }
}
