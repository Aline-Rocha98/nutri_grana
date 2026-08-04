<?php

namespace App\Models\Usuario;

use Database\Factories\UsuarioFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Notifications\Notifiable;

class Usuario extends Model implements AuthenticatableContract, CanResetPasswordContract,  AuthorizableContract
{
    use Authenticatable, CanResetPassword, HasFactory, Notifiable, Authorizable;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $incrementing = true;

    protected $fillable = [
        'nome',
        'email',
        'senha',
        'email_verificado',
        'data_nascimento',
        'motivo_controle_financeiro',
        'foto_perfil',
    ];

    protected $hidden = [
        'senha',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
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
        $this->attributes['senha'] = bcrypt($value);
    }

    public function hasVerifiedEmail(): bool
    {
        return true;
    }
}
