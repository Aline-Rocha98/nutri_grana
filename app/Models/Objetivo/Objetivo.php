<?php

namespace App\Models\Objetivo;

use App\Enum\SimNao;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Objetivo extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'objetivos';

    protected $primaryKey = 'id_objetivo';

    protected $fillable = [
        'id_usuario',
        'descricao',
        'valor_meta',
        'data_limite',
        'exibir_dashboard',
    ];

    protected function casts(): array
    {
        return [
            'valor_meta' => 'decimal:2',
            'data_limite' => 'date',
            'exibir_dashboard' => SimNao::class,
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function aportes(): HasMany
    {
        return $this->hasMany(AporteObjetivo::class, 'id_objetivo', 'id_objetivo');
    }
}
