<?php

namespace App\Models\EspacoCompartilhado;

use App\Enum\StatusEspacoCompartilhado;
use App\Enum\StatusMembroEspaco;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\Usuario\Usuario;
use Database\Factories\EspacoCompartilhadoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EspacoCompartilhado extends Model
{
    use HasFactory, TemChaveRotaCriptografada;

    protected $table = 'espacos_compartilhados';

    protected $primaryKey = 'id_espaco_compartilhado';

    protected $fillable = [
        'nome',
        'criado_por',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusEspacoCompartilhado::class,
        ];
    }

    protected static function newFactory(): EspacoCompartilhadoFactory
    {
        return EspacoCompartilhadoFactory::new();
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por', 'id_usuario');
    }

    public function membros(): HasMany
    {
        return $this->hasMany(MembroEspacoCompartilhado::class, 'id_espaco_compartilhado', 'id_espaco_compartilhado');
    }

    public function membrosAtivos(): HasMany
    {
        return $this->membros()->where('status', StatusMembroEspaco::Ativo);
    }

    public function convites(): HasMany
    {
        return $this->hasMany(ConviteCompartilhamento::class, 'id_espaco_compartilhado', 'id_espaco_compartilhado');
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(LancamentoCompartilhado::class, 'id_espaco_compartilhado', 'id_espaco_compartilhado');
    }

    public function recorrencias(): HasMany
    {
        return $this->hasMany(RecorrenciaCompartilhada::class, 'id_espaco_compartilhado', 'id_espaco_compartilhado');
    }

    public function estaAtivo(): bool
    {
        return $this->status === StatusEspacoCompartilhado::Ativo;
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        $id = $this->decodificarChaveRota((string) $value);

        if ($id === null) {
            return null;
        }

        $relacao = match ($childType) {
            'lancamentoCompartilhado' => $this->lancamentos(),
            'membroEspaco' => $this->membros(),
            'conviteCompartilhamento' => $this->convites(),
            default => null,
        };

        return $relacao?->whereKey($id)->first();
    }
}
