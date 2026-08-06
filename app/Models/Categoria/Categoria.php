<?php

namespace App\Models\Categoria;

use App\Enum\SimNao;
use App\Enum\TipoCategoria;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use TemChaveRotaCriptografada;

    public const NIVEL_PRINCIPAL = 1;

    public const NIVEL_SUBCATEGORIA = 2;

    protected $table = 'categorias';

    protected $primaryKey = 'id_categoria';

    protected $fillable = [
        'id_usuario',
        'id_categoria_pai',
        'nivel',
        'padrao',
        'nome',
        'tipo',
        'icone',
        'cor',
        'arquivada',
    ];

    protected function casts(): array
    {
        return [
            'padrao' => SimNao::class,
            'tipo' => TipoCategoria::class,
            'arquivada' => SimNao::class,
            'nivel' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function pai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_categoria_pai', 'id_categoria');
    }

    public function subcategorias(): HasMany
    {
        return $this->hasMany(self::class, 'id_categoria_pai', 'id_categoria')
            ->orderBy('arquivada')
            ->orderBy('nome');
    }

    public function scopePrincipais(Builder $query): Builder
    {
        return $query->whereNull('id_categoria_pai');
    }

    public function ehPrincipal(): bool
    {
        return $this->id_categoria_pai === null;
    }

    public function ehSubcategoria(): bool
    {
        return $this->id_categoria_pai !== null;
    }
}
