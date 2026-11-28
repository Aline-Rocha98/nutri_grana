<?php

namespace App\Models\Usuario;

use App\Enum\SimNao;
use Database\Factories\UsuarioFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Usuario extends Model implements AuthenticatableContract, CanResetPasswordContract, AuthorizableContract
{
    use Authenticatable, CanResetPassword, HasFactory, Notifiable, Authorizable;

    protected $table = 'usuario';

    protected $primaryKey = 'id_usuario';

    public $incrementing = true;

    protected $fillable = [
        'nome',
        'email',
        'data_nascimento',
        'motivo_controle_financeiro',
        'foto_perfil',
    ];

    protected $attributes = [
        'foto_perfil' => null,
        'compartilhamento_habilitado' => 'N',
        'eh_administrador' => 'N',
    ];

    protected $hidden = [
        'senha',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'compartilhamento_habilitado' => SimNao::class,
            'eh_administrador' => SimNao::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function possuiCompartilhamentoHabilitado(): bool
    {
        return $this->compartilhamento_habilitado === SimNao::Sim;
    }

    public function ehAdministradorDoSistema(): bool
    {
        return $this->eh_administrador === SimNao::Sim;
    }

    public static function usuarioDaSessaoEhAdministrador(): bool
    {
        $usuario = Auth::user();

        return $usuario instanceof self && $usuario->ehAdministradorDoSistema();
    }

    protected static function newFactory(): UsuarioFactory
    {
        return UsuarioFactory::new();
    }

    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    public function setSenhaAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        // Avoid double-hashing when an already hashed value is assigned.
        if (is_string($value) && $this->isHashedPassword($value)) {
            $this->attributes['senha'] = $value;

            return;
        }

        $this->attributes['senha'] = Hash::make($value);
    }

    private function isHashedPassword(string $value): bool
    {
        return str_starts_with($value, '$2y$')
            || str_starts_with($value, '$2a$')
            || str_starts_with($value, '$2b$')
            || str_starts_with($value, '$argon2i$')
            || str_starts_with($value, '$argon2id$');
    }

    public function hasVerifiedEmail(): bool
    {
        return true;
    }

    public function getFotoUrlAttribute(): ?string
    {
        $fotoPerfil = $this->attributes['foto_perfil'] ?? null;

        if (! $fotoPerfil) {
            return null;
        }

        $caminho = 'storage/'.ltrim($fotoPerfil, '/');

        if (! app()->runningInConsole()) {
            return rtrim(request()->root(), '/').'/'.$caminho;
        }

        return asset($caminho);
    }
}
