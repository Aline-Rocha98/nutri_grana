<?php

namespace App\Models\Orcamento;

use App\Enum\SimNao;
use App\Enum\TipoOrcamento;
use App\Models\Categoria\Categoria;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Orcamento extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'orcamentos';

    protected $primaryKey = 'id_orcamento';

    protected $fillable = [
        'id_usuario',
        'tipo',
        'id_categoria',
        'valor_mensal',
        'exibir_dashboard',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoOrcamento::class,
            'valor_mensal' => 'decimal:2',
            'exibir_dashboard' => SimNao::class,
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }
}
