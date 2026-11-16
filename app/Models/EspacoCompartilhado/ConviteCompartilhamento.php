<?php

namespace App\Models\EspacoCompartilhado;

use App\Enum\StatusConviteCompartilhamento;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConviteCompartilhamento extends Model
{
    use TemChaveRotaCriptografada;

    public const LIMITE_TENTATIVAS_CODIGO = 5;

    protected $table = 'convites_compartilhamento';

    protected $primaryKey = 'id_convite_compartilhamento';

    protected $fillable = [
        'id_espaco_compartilhado',
        'id_usuario_convidado',
        'email_destinatario',
        'codigo_hash',
        'tentativas_invalidas',
        'criado_por',
        'expira_em',
        'usado_em',
        'status',
    ];

    protected $hidden = [
        'codigo_hash',
    ];

    protected $attributes = [
        'tentativas_invalidas' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusConviteCompartilhamento::class,
            'tentativas_invalidas' => 'integer',
            'expira_em' => 'datetime',
            'usado_em' => 'datetime',
        ];
    }

    public function espaco(): BelongsTo
    {
        return $this->belongsTo(EspacoCompartilhado::class, 'id_espaco_compartilhado', 'id_espaco_compartilhado');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por', 'id_usuario');
    }

    public function convidado(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_convidado', 'id_usuario');
    }

    public function estaPendente(): bool
    {
        return $this->status === StatusConviteCompartilhamento::Pendente;
    }

    public function estaExpirado(): bool
    {
        return $this->expira_em !== null && $this->expira_em->isPast();
    }

    public function atingiuLimiteDeTentativas(): bool
    {
        return $this->tentativas_invalidas >= self::LIMITE_TENTATIVAS_CODIGO;
    }

    public function quantidadeDeTentativasRestantes(): int
    {
        return max(0, self::LIMITE_TENTATIVAS_CODIGO - $this->tentativas_invalidas);
    }
}
