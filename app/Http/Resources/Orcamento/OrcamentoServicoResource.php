<?php

namespace App\Http\Resources\Orcamento;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrcamentoServicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $comparativo = $this->comparativo ?? null;

        return [
            'id' => $this->id_orcamento_servico,
            'descricao' => $this->descricao,
            'valor' => number_format((float) $this->valor, 2, ',', '.'),
            'valor_numero' => (float) $this->valor,
            'data_orcamento' => $this->data_orcamento?->format('Y-m-d'),
            'data_orcamento_formatada' => $this->data_orcamento?->format('d/m/Y'),
            'data_validade' => $this->data_validade?->format('Y-m-d'),
            'data_validade_formatada' => $this->data_validade?->format('d/m/Y'),
            'observacao' => $this->observacao,
            'saldo_atual_contas' => $this->saldo_atual_contas_formatado
                ?? number_format((float) ($this->saldo_atual_contas ?? 0), 2, ',', '.'),
            'saldo_atual_contas_numero' => (float) ($this->saldo_atual_contas ?? 0),
            'saldo_disponivel_planejamento' => $this->saldo_disponivel_planejamento_formatado
                ?? number_format((float) ($this->saldo_disponivel_planejamento ?? 0), 2, ',', '.'),
            'saldo_disponivel_planejamento_numero' => (float) ($this->saldo_disponivel_planejamento ?? 0),
            'pago_integralmente_agora' => (bool) ($this->pago_integralmente_agora ?? false),
            'meses_ate_pagar' => $this->meses_ate_pagar,
            'compromete_fluxo' => (bool) ($this->compromete_fluxo ?? false),
            'expirado' => (bool) ($this->expirado ?? false),
            'mensagem_principal' => $this->mensagem_principal ?? null,
            'mensagem_disponivel' => $this->mensagem_disponivel ?? null,
            'mensagem_alerta' => $this->mensagem_alerta ?? null,
            'comparativo' => $comparativo ? [
                'mes_rotulo' => $comparativo['mes_rotulo'] ?? null,
                'saldo_sem_orcamento' => $comparativo['saldo_sem_orcamento_formatado']
                    ?? number_format((float) ($comparativo['saldo_sem_orcamento'] ?? 0), 2, ',', '.'),
                'saldo_sem_orcamento_numero' => (float) ($comparativo['saldo_sem_orcamento'] ?? 0),
                'saldo_com_orcamento' => $comparativo['saldo_com_orcamento_formatado']
                    ?? number_format((float) ($comparativo['saldo_com_orcamento'] ?? 0), 2, ',', '.'),
                'saldo_com_orcamento_numero' => (float) ($comparativo['saldo_com_orcamento'] ?? 0),
            ] : null,
            'url_atualizar' => route('orcamentos.servico.atualizar', $this->resource),
            'url_excluir' => route('orcamentos.servico.excluir', $this->resource),
        ];
    }
}
