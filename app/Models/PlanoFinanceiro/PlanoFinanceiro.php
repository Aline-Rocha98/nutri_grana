<?php

namespace App\Models\PlanoFinanceiro;

use App\Enum\SimNao;
use App\Enum\SituacaoPlanoFinanceiro;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\Renda\Renda;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanoFinanceiro extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'planos_financeiros';

    protected $primaryKey = 'id_plano_financeiro';

    protected $fillable = [
        'id_usuario',
        'nome',
        'descricao',
        'situacao',
        'padrao',
    ];

    protected function casts(): array
    {
        return [
            'situacao' => SituacaoPlanoFinanceiro::class,
            'padrao' => SimNao::class,
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemPlanoFinanceiro::class, 'id_plano_financeiro', 'id_plano_financeiro')
            ->orderBy('ordem')
            ->orderBy('id_item_plano');
    }

    public function itensQueSeparamDoSaldo(): HasMany
    {
        return $this->itens()->where('separar_do_saldo', SimNao::Sim);
    }

    public function rendas(): HasMany
    {
        return $this->hasMany(Renda::class, 'id_plano_financeiro', 'id_plano_financeiro');
    }

    public function estaAtivo(): bool
    {
        return $this->situacao === SituacaoPlanoFinanceiro::Ativo;
    }

    public function ehPadrao(): bool
    {
        return $this->padrao === SimNao::Sim;
    }
}
