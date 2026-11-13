<?php

namespace App\Http\Resources\ContaBancaria;

use App\Enum\TipoMovimentacaoSaldoGuardado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimentacaoSaldoGuardadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $itemPlanoFinanceiro = $this->relationLoaded('itemPlanoFinanceiro') ? $this->itemPlanoFinanceiro : null;
        $alocacao = $this->relationLoaded('alocacao') ? $this->alocacao : null;
        $alocacaoOrigem = $this->relationLoaded('alocacaoOrigem') ? $this->alocacaoOrigem : null;
        $contaOrigem = $this->relationLoaded('contaBancariaOrigem') ? $this->contaBancariaOrigem : null;
        $contaResgate = $this->relationLoaded('contaBancariaResgate') ? $this->contaBancariaResgate : null;
        $ehSeparacao = $this->tipo === TipoMovimentacaoSaldoGuardado::Separacao;

        return [
            'id' => $this->id_movimentacao_saldo_guardado,
            'id_conta_bancaria' => $this->id_conta_bancaria,
            'tipo' => $this->tipo?->value,
            'tipo_rotulo' => $this->tipo?->rotulo(),
            'eh_separacao' => $ehSeparacao,
            'descricao' => match ($this->tipo) {
                TipoMovimentacaoSaldoGuardado::Separacao => $alocacao?->nome
                    ?? $itemPlanoFinanceiro?->nome
                    ?? $this->observacao
                    ?? 'Plano financeiro da renda',
                TipoMovimentacaoSaldoGuardado::Transferencia => ($alocacaoOrigem?->nome ?? 'Alocação').' → '.($alocacao?->nome ?? 'Alocação'),
                default => $alocacao
                    ? 'Resgate de '.$alocacao->nome
                    : 'Resgate do saldo guardado',
            },
            'alocacao_nome' => $alocacao?->nome,
            'conta_bancaria_origem_nome' => $contaOrigem?->nome,
            'conta_bancaria_resgate_nome' => $contaResgate?->nome,
            'valor' => number_format((float) $this->valor, 2, ',', '.'),
            'valor_numero' => (float) $this->valor,
            'data_movimentacao' => $this->data_movimentacao?->format('Y-m-d'),
            'data_movimentacao_formatada' => $this->data_movimentacao?->format('d/m/Y'),
            'observacao' => $this->observacao,
        ];
    }
}
