<?php

namespace App\Models\EspacoCompartilhado;

use App\Enum\FormaPagamento;
use App\Enum\StatusLancamentoCompartilhado;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\Categoria\Categoria;
use App\Models\Concerns\TemChaveRotaCriptografada;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Usuario\Usuario;
use Database\Factories\LancamentoCompartilhadoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LancamentoCompartilhado extends Model
{
    use HasFactory, TemChaveRotaCriptografada;

    protected $table = 'lancamentos_compartilhados';

    protected $primaryKey = 'id_lancamento_compartilhado';

    protected $fillable = [
        'id_espaco_compartilhado',
        'id_recorrencia_compartilhada',
        'id_usuario_responsavel',
        'id_categoria',
        'descricao',
        'valor',
        'data_vencimento',
        'status',
        'data_pagamento',
        'forma_pagamento',
        'id_conta_bancaria',
        'id_cartao_credito',
        'id_usuario_baixa',
        'versao',
        'observacao',
        'criado_por',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_vencimento' => 'date',
            'data_pagamento' => 'date',
            'status' => StatusLancamentoCompartilhado::class,
            'forma_pagamento' => FormaPagamento::class,
            'versao' => 'integer',
        ];
    }

    protected static function newFactory(): LancamentoCompartilhadoFactory
    {
        return LancamentoCompartilhadoFactory::new();
    }

    public function espaco(): BelongsTo
    {
        return $this->belongsTo(EspacoCompartilhado::class, 'id_espaco_compartilhado', 'id_espaco_compartilhado');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_responsavel', 'id_usuario');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    public function contaBancaria(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class, 'id_conta_bancaria', 'id_conta_bancaria');
    }

    public function cartaoCredito(): BelongsTo
    {
        return $this->belongsTo(CartaoCredito::class, 'id_cartao_credito', 'id_cartao_credito');
    }

    public function recorrencia(): BelongsTo
    {
        return $this->belongsTo(RecorrenciaCompartilhada::class, 'id_recorrencia_compartilhada', 'id_recorrencia_compartilhada');
    }

    public function estaPago(): bool
    {
        return $this->status === StatusLancamentoCompartilhado::Pago;
    }

    public function estaPendente(): bool
    {
        return $this->status === StatusLancamentoCompartilhado::Pendente;
    }

    public function pertenceARecorrencia(): bool
    {
        return $this->id_recorrencia_compartilhada !== null;
    }

    public function ehResponsavel(int $idUsuario): bool
    {
        return $this->id_usuario_responsavel !== null && (int) $this->id_usuario_responsavel === $idUsuario;
    }

    public function retratoParaAuditoria(): array
    {
        return [
            'descricao' => $this->descricao,
            'valor' => (string) $this->valor,
            'data_vencimento' => $this->data_vencimento?->toDateString(),
            'status' => $this->status?->value,
            'data_pagamento' => $this->data_pagamento?->toDateString(),
            'id_usuario_responsavel' => $this->id_usuario_responsavel,
            'id_categoria' => $this->id_categoria,
            'forma_pagamento' => $this->forma_pagamento?->value,
            'id_conta_bancaria' => $this->id_conta_bancaria,
            'id_cartao_credito' => $this->id_cartao_credito,
            'versao' => $this->versao,
        ];
    }
}
