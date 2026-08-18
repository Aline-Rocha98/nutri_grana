<?php

namespace App\Models\Lancamento;

use App\Enum\FormaPagamento;
use App\Enum\FrequenciaRecorrencia;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\Categoria\Categoria;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\FaturaCartao\FaturaCartao;
use App\Models\Orcamento\OrcamentoServico;
use App\Models\Renda\Renda;
use App\Models\Usuario\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lancamento extends Model
{
    use TemChaveRotaCriptografada;

    protected $table = 'lancamentos';

    protected $primaryKey = 'id_lancamento';

    protected $fillable = [
        'id_usuario',
        'id_lancamento_pai',
        'id_grupo_parcela',
        'descricao',
        'valor',
        'valor_previsto',
        'data_vencimento',
        'data_pagamento',
        'tipo',
        'forma_pagamento',
        'id_conta_bancaria',
        'id_cartao_credito',
        'id_fatura_cartao',
        'situacao',
        'id_categoria',
        'id_renda',
        'id_orcamento_servico',
        'observacao',
        'parcela_atual',
        'total_parcelas',
        'eh_recorrencia',
        'frequencia_recorrencia',
        'recorrencia_ate',
        'recorrencia_gerada_ate',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'valor_previsto' => 'decimal:2',
            'data_vencimento' => 'date',
            'data_pagamento' => 'date',
            'tipo' => TipoLancamento::class,
            'forma_pagamento' => FormaPagamento::class,
            'situacao' => SituacaoLancamento::class,
            'eh_recorrencia' => SimNao::class,
            'frequencia_recorrencia' => FrequenciaRecorrencia::class,
            'recorrencia_ate' => 'date',
            'recorrencia_gerada_ate' => 'date',
            'parcela_atual' => 'integer',
            'total_parcelas' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function pai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_lancamento_pai', 'id_lancamento');
    }

    public function ocorrencias(): HasMany
    {
        return $this->hasMany(self::class, 'id_lancamento_pai', 'id_lancamento');
    }

    public function contaBancaria(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class, 'id_conta_bancaria', 'id_conta_bancaria');
    }

    public function cartaoCredito(): BelongsTo
    {
        return $this->belongsTo(CartaoCredito::class, 'id_cartao_credito', 'id_cartao_credito');
    }

    public function faturaCartao(): BelongsTo
    {
        return $this->belongsTo(FaturaCartao::class, 'id_fatura_cartao', 'id_fatura_cartao');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    public function renda(): BelongsTo
    {
        return $this->belongsTo(Renda::class, 'id_renda', 'id_renda');
    }

    public function orcamentoServico(): BelongsTo
    {
        return $this->belongsTo(OrcamentoServico::class, 'id_orcamento_servico', 'id_orcamento_servico');
    }

    public function ehRenda(): bool
    {
        return $this->id_renda !== null;
    }

    public function scopeOcorrenciasDoMes(Builder $query, int $idUsuario, int $ano, int $mes): Builder
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        return $query
            ->where('id_usuario', $idUsuario)
            ->where('eh_recorrencia', SimNao::Nao)
            ->whereBetween('data_vencimento', [$inicio, $fim]);
    }

    public function ehPaiRecorrencia(): bool
    {
        return $this->eh_recorrencia === SimNao::Sim;
    }
}
