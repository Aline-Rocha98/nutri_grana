<?php

namespace App\Models\Categoria;

use App\Enum\SimNao;
use App\Enum\TipoCategoria;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Categoria extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'categorias';

    protected $primaryKey = 'id_categoria';

    protected $fillable = [
        'id_usuario',
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
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
