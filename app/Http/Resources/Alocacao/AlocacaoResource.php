<?php

namespace App\Http\Resources\Alocacao;

use App\Enum\TipoMovimentacaoSaldoGuardado;
use App\Models\ContaBancaria\MovimentacaoSaldoGuardado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlocacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $idAlocacao = (int) $this->id_alocacao;
        $saldoConsolidado = (float) $this->saldo_consolidado;
        $saldosPorConta = $this->saldos_por_conta ?? [];
        $movimentacoesRecentes = $this->movimentacoes_recentes ?? collect();

        return [
            'id' => $idAlocacao,
            'chave' => $this->getRouteKey(),
            'nome' => $this->nome,
            'saldo' => $this->formatarMoeda($saldoConsolidado),
            'saldo_numero' => $saldoConsolidado,
            'saldo_consolidado_em' => $this->saldo_consolidado_em?->format('d/m/Y H:i'),
            'saldos_por_conta' => array_map(fn (array $saldoConta) => [
                ...$saldoConta,
                'saldo_formatado' => $this->formatarMoeda((float) $saldoConta['saldo']),
            ], $saldosPorConta),
            'movimentacoes_recentes' => $movimentacoesRecentes
                ->map(fn (MovimentacaoSaldoGuardado $movimentacao) => $this->montarMovimentacaoRecente($movimentacao, $idAlocacao))
                ->values()
                ->all(),
            'url_transferir' => route('alocacoes.transferir', $this->resource),
            'url_resgatar' => route('alocacoes.resgatar', $this->resource),
        ];
    }

    private function montarMovimentacaoRecente(MovimentacaoSaldoGuardado $movimentacao, int $idAlocacao): array
    {
        $ehEntrada = $movimentacao->tipo === TipoMovimentacaoSaldoGuardado::Separacao
            || ($movimentacao->tipo === TipoMovimentacaoSaldoGuardado::Transferencia && (int) $movimentacao->id_alocacao === $idAlocacao);

        return [
            'id' => $movimentacao->id_movimentacao_saldo_guardado,
            'tipo' => $movimentacao->tipo?->value,
            'eh_entrada' => $ehEntrada,
            'descricao' => $this->descreverMovimentacao($movimentacao, $ehEntrada),
            'conta_bancaria_nome' => $movimentacao->contaBancaria?->nome,
            'valor' => $this->formatarMoeda((float) $movimentacao->valor),
            'valor_numero' => (float) $movimentacao->valor,
            'data_movimentacao_formatada' => $movimentacao->data_movimentacao?->format('d/m/Y'),
            'observacao' => $movimentacao->observacao,
        ];
    }

    private function descreverMovimentacao(MovimentacaoSaldoGuardado $movimentacao, bool $ehEntrada): string
    {
        return match ($movimentacao->tipo) {
            TipoMovimentacaoSaldoGuardado::Separacao => 'Guardado pelo plano financeiro',
            TipoMovimentacaoSaldoGuardado::Transferencia => $ehEntrada
                ? 'Recebido de '.($movimentacao->alocacaoOrigem?->nome ?? 'outra alocação')
                : 'Enviado para '.($movimentacao->alocacao?->nome ?? 'outra alocação'),
            TipoMovimentacaoSaldoGuardado::Resgate => $movimentacao->contaBancariaResgate
                ? 'Resgate para '.$movimentacao->contaBancariaResgate->nome
                : 'Resgate para o saldo disponível',
            default => $movimentacao->tipo?->rotulo() ?? '',
        };
    }

    private function formatarMoeda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}
