<?php

namespace App\Models\Orcamento;

use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrcamentoServico extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'orcamentos_servico';

    protected $primaryKey = 'id_orcamento_servico';

    protected $fillable = [
        'id_usuario',
        'descricao',
        'valor',
        'data_orcamento',
        'data_validade',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_orcamento' => 'date',
            'data_validade' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
