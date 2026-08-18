<?php

namespace App\Http\Resources\Orcamento;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrcamentoServicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cenarios = $this->cenarios ?? [];

        return [
            'id' => $this->id_orcamento_servico,
            'descricao' => $this->descricao,
            'fornecedor' => $this->fornecedor,
            'valor' => number_format((float) $this->valor, 2, ',', '.'),
            'valor_numero' => (float) $this->valor,
            'data_orcamento' => $this->data_orcamento?->format('Y-m-d'),
            'data_orcamento_formatada' => $this->data_orcamento?->format('d/m/Y'),
            'data_validade' => $this->data_validade?->format('Y-m-d'),
            'data_validade_formatada' => $this->data_validade?->format('d/m/Y'),
            'observacao' => $this->observacao,
            'status' => $this->status?->value ?? $this->status,
            'status_rotulo' => $this->status?->rotulo(),
            'id_categoria' => $this->id_categoria,
            'categoria_nome' => $this->categoria?->nome,
            'id_subcategoria' => $this->id_subcategoria,
            'subcategoria_nome' => $this->subcategoria?->nome,
            'modalidade_pagamento' => $this->modalidade_pagamento?->value ?? $this->modalidade_pagamento,
            'modalidade_pagamento_rotulo' => $this->modalidade_pagamento?->rotulo(),
            'forma_pagamento' => $this->forma_pagamento?->value ?? $this->forma_pagamento,
            'forma_pagamento_rotulo' => $this->forma_pagamento?->rotulo(),
            'id_conta_bancaria' => $this->id_conta_bancaria ?? $this->contaBancaria?->getKey(),
            'conta_bancaria_nome' => $this->contaBancaria?->nome,
            'id_cartao_credito' => $this->id_cartao_credito ?? $this->cartaoCredito?->getKey(),
            'cartao_credito_nome' => $this->cartaoCredito?->nome,
            'data_aprovacao_formatada' => $this->data_aprovacao?->format('d/m/Y H:i'),
            'total_parcelas' => (int) ($this->total_parcelas ?? 1),
            'compromissos_gerados' => (int) ($this->compromissos_gerados ?? 0),
            'pode_assumir_compromisso' => (bool) ($this->pode_assumir_compromisso ?? false),
            'resumo_compromisso' => $this->resumo_compromisso ?? null,
            'liquido_medio_mensal' => $this->formatarMoeda($this->liquido_medio_mensal),
            'liquido_medio_mensal_numero' => (float) ($this->liquido_medio_mensal ?? 0),
            'receita_prevista_mensal' => $this->formatarMoeda($this->receita_prevista_mensal),
            'receita_prevista_mensal_numero' => (float) ($this->receita_prevista_mensal ?? 0),
            'saldo_atual_contas' => $this->formatarMoeda($this->saldo_atual_contas),
            'saldo_atual_contas_numero' => (float) ($this->saldo_atual_contas ?? 0),
            'saldo_conta_selecionada' => $this->formatarMoeda($this->saldo_conta_selecionada),
            'saldo_conta_selecionada_numero' => (float) ($this->saldo_conta_selecionada ?? 0),
            'limite_disponivel_cartao' => $this->formatarMoeda($this->limite_disponivel_cartao),
            'limite_disponivel_cartao_numero' => (float) ($this->limite_disponivel_cartao ?? 0),
            'expirado' => (bool) ($this->expirado ?? false),
            'cenarios' => collect($cenarios)->map(fn (array $cenario) => [
                'rotulo' => $cenario['rotulo'] ?? null,
                'modalidade_pagamento' => $cenario['modalidade_pagamento'] ?? null,
                'total_parcelas' => (int) ($cenario['total_parcelas'] ?? 1),
                'valor_parcela' => $cenario['valor_parcela_formatado'] ?? null,
                'valor_parcela_numero' => (float) ($cenario['valor_parcela'] ?? 0),
                'compromete_fluxo' => (bool) ($cenario['compromete_fluxo'] ?? false),
                'compromete_mes_atual' => (bool) ($cenario['compromete_mes_atual'] ?? false),
                'compromete_meses_seguintes' => (bool) ($cenario['compromete_meses_seguintes'] ?? false),
                'rotulo_fluxo' => $cenario['rotulo_fluxo'] ?? (($cenario['compromete_fluxo'] ?? false) ? 'Compromete' : 'Ok'),
                'ultrapassa_limite_cartao' => (bool) ($cenario['ultrapassa_limite_cartao'] ?? false),
                'viavel' => (bool) ($cenario['viavel'] ?? false),
                'recomendado' => (bool) ($cenario['recomendado'] ?? false),
                'comparativo' => isset($cenario['comparativo']) ? [
                    'mes_rotulo' => $cenario['comparativo']['mes_rotulo'] ?? null,
                    'saldo_sem_orcamento' => $cenario['comparativo']['saldo_sem_orcamento_formatado'] ?? null,
                    'saldo_sem_orcamento_numero' => (float) ($cenario['comparativo']['saldo_sem_orcamento'] ?? 0),
                    'saldo_com_orcamento' => $cenario['comparativo']['saldo_com_orcamento_formatado'] ?? null,
                    'saldo_com_orcamento_numero' => (float) ($cenario['comparativo']['saldo_com_orcamento'] ?? 0),
                ] : null,
            ])->values()->all(),
            'url_atualizar' => route('orcamentos.servico.atualizar', $this->resource),
            'url_excluir' => route('orcamentos.servico.excluir', $this->resource),
            'url_aprovar' => route('orcamentos.servico.aprovar', $this->resource),
            'url_recusar' => route('orcamentos.servico.recusar', $this->resource),
        ];
    }

    private function formatarMoeda(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (is_string($valor) && str_contains($valor, ',')) {
            return $valor;
        }

        return number_format((float) $valor, 2, ',', '.');
    }
}
