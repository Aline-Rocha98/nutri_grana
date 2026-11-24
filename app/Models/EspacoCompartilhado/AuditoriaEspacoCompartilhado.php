<?php

namespace App\Models\EspacoCompartilhado;

use App\Enum\AcaoAuditoriaCompartilhada;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaEspacoCompartilhado extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'auditoria_espaco_compartilhado';

    protected $primaryKey = 'id_auditoria';

    protected $fillable = [
        'id_espaco_compartilhado',
        'id_usuario',
        'acao',
        'entidade',
        'id_entidade',
        'dados_anteriores',
        'dados_novos',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'acao' => AcaoAuditoriaCompartilhada::class,
            'dados_anteriores' => 'array',
            'dados_novos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
