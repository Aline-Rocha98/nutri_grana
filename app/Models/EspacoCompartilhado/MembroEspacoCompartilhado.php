<?php

namespace App\Models\EspacoCompartilhado;

use App\Enum\PapelMembroEspaco;
use App\Enum\StatusMembroEspaco;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembroEspacoCompartilhado extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'espaco_compartilhado_usuario';

    protected $primaryKey = 'id_membro_espaco';

    protected $fillable = [
        'id_espaco_compartilhado',
        'id_usuario',
        'papel',
        'status',
        'entrou_em',
    ];

    protected function casts(): array
    {
        return [
            'papel' => PapelMembroEspaco::class,
            'status' => StatusMembroEspaco::class,
            'entrou_em' => 'datetime',
        ];
    }

    public function espaco(): BelongsTo
    {
        return $this->belongsTo(EspacoCompartilhado::class, 'id_espaco_compartilhado', 'id_espaco_compartilhado');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function estaAtivo(): bool
    {
        return $this->status === StatusMembroEspaco::Ativo;
    }

    public function ehAdministrador(): bool
    {
        return $this->papel === PapelMembroEspaco::Administrador;
    }
}
