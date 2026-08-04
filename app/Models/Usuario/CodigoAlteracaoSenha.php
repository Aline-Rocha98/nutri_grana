<?php

namespace App\Models\Usuario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodigoAlteracaoSenha extends Model
{
    protected $table = 'password_change_codes';

    protected $fillable = [
        'id_usuario',
        'codigo_hash',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function estaValido(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
