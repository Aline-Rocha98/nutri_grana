<?php

namespace App\Repositories\ContaBancaria;

use App\Enum\TipoMovimentacaoSaldoGuardado;
use App\Models\ContaBancaria\MovimentacaoSaldoGuardado;
use Illuminate\Support\Collection;

class MovimentacaoSaldoGuardadoRepository
{
    public function somarSaldoGuardadoDaConta(int $idContaBancaria): float
    {
        $separado = $this->somarSeparacoesComDestinoNaConta($idContaBancaria);

        $resgatado = (float) MovimentacaoSaldoGuardado::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->where('tipo', TipoMovimentacaoSaldoGuardado::Resgate)
            ->sum('valor');

        return round($separado - $resgatado, 2);
    }

    public function somarSeparacoesComDestinoNaConta(int $idContaBancaria): float
    {
        return round((float) MovimentacaoSaldoGuardado::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->where('tipo', TipoMovimentacaoSaldoGuardado::Separacao)
            ->sum('valor'), 2);
    }

    public function somarSeparacoesComOrigemNaConta(int $idContaBancaria): float
    {
        return round((float) MovimentacaoSaldoGuardado::query()
            ->where('id_conta_bancaria_origem', $idContaBancaria)
            ->where('tipo', TipoMovimentacaoSaldoGuardado::Separacao)
            ->sum('valor'), 2);
    }

    public function somarResgatesRecebidosNaConta(int $idContaBancaria): float
    {
        return round((float) MovimentacaoSaldoGuardado::query()
            ->where('id_conta_bancaria_resgate', $idContaBancaria)
            ->where('tipo', TipoMovimentacaoSaldoGuardado::Resgate)
            ->sum('valor'), 2);
    }

    public function somarResgatesEnviadosDaConta(int $idContaBancaria): float
    {
        return round((float) MovimentacaoSaldoGuardado::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->whereNotNull('id_conta_bancaria_resgate')
            ->where('tipo', TipoMovimentacaoSaldoGuardado::Resgate)
            ->sum('valor'), 2);
    }

    public function somarSeparacoesNoPeriodo(int $idUsuario, string $inicio, string $fim): float
    {
        return round((float) MovimentacaoSaldoGuardado::query()
            ->where('id_usuario', $idUsuario)
            ->where('tipo', TipoMovimentacaoSaldoGuardado::Separacao)
            ->whereBetween('data_movimentacao', [$inicio, $fim])
            ->sum('valor'), 2);
    }

    public function somarSaldoDaAlocacao(int $idAlocacao): float
    {
        return round(array_sum($this->somarSaldoDaAlocacaoPorConta($idAlocacao)), 2);
    }

    public function somarSaldoDaAlocacaoPorConta(int $idAlocacao): array
    {
        return $this->somarSaldosDasAlocacoesPorConta([$idAlocacao])[$idAlocacao] ?? [];
    }

    public function somarSaldosDasAlocacoesPorConta(array $idsAlocacoes): array
    {
        if ($idsAlocacoes === []) {
            return [];
        }

        $saldos = [];

        $entradasESaidasDiretas = MovimentacaoSaldoGuardado::query()
            ->whereIn('id_alocacao', $idsAlocacoes)
            ->groupBy('id_alocacao', 'id_conta_bancaria')
            ->selectRaw(
                'id_alocacao, id_conta_bancaria, SUM(CASE WHEN tipo = ? THEN -valor ELSE valor END) as total',
                [TipoMovimentacaoSaldoGuardado::Resgate->value],
            )
            ->get();

        foreach ($entradasESaidasDiretas as $linha) {
            $this->acumularSaldo($saldos, (int) $linha->id_alocacao, (int) $linha->id_conta_bancaria, (float) $linha->total);
        }

        $transferenciasEnviadas = MovimentacaoSaldoGuardado::query()
            ->whereIn('id_alocacao_origem', $idsAlocacoes)
            ->where('tipo', TipoMovimentacaoSaldoGuardado::Transferencia)
            ->groupBy('id_alocacao_origem', 'id_conta_bancaria')
            ->selectRaw('id_alocacao_origem, id_conta_bancaria, SUM(valor) as total')
            ->get();

        foreach ($transferenciasEnviadas as $linha) {
            $this->acumularSaldo($saldos, (int) $linha->id_alocacao_origem, (int) $linha->id_conta_bancaria, -(float) $linha->total);
        }

        return $saldos;
    }

    public function listarIdsItensPlanoSeparadosDoLancamento(int $idLancamento): array
    {
        return MovimentacaoSaldoGuardado::query()
            ->where('id_lancamento_origem', $idLancamento)
            ->where('tipo', TipoMovimentacaoSaldoGuardado::Separacao)
            ->whereNotNull('id_item_plano')
            ->pluck('id_item_plano')
            ->map(fn ($idItem) => (int) $idItem)
            ->all();
    }

    public function listarSeparacoesDoLancamento(int $idLancamento): Collection
    {
        return MovimentacaoSaldoGuardado::query()
            ->where('id_lancamento_origem', $idLancamento)
            ->where('tipo', TipoMovimentacaoSaldoGuardado::Separacao)
            ->get();
    }

    public function listarMovimentacoesRecentesDaConta(int $idContaBancaria, int $limite): Collection
    {
        return MovimentacaoSaldoGuardado::query()
            ->where(function ($query) use ($idContaBancaria) {
                $query->where('id_conta_bancaria', $idContaBancaria)
                    ->orWhere('id_conta_bancaria_resgate', $idContaBancaria);
            })
            ->with([
                'itemPlanoFinanceiro:id_item_plano,nome',
                'alocacao:id_alocacao,nome',
                'alocacaoOrigem:id_alocacao,nome',
                'contaBancariaOrigem',
                'contaBancariaResgate',
            ])
            ->orderByDesc('data_movimentacao')
            ->orderByDesc('id_movimentacao_saldo_guardado')
            ->limit($limite)
            ->get();
    }

    public function listarMovimentacoesRecentesDasAlocacoes(array $idsAlocacoes, int $limitePorAlocacao): array
    {
        if ($idsAlocacoes === []) {
            return [];
        }

        $movimentacoes = MovimentacaoSaldoGuardado::query()
            ->where(function ($query) use ($idsAlocacoes) {
                $query->whereIn('id_alocacao', $idsAlocacoes)
                    ->orWhereIn('id_alocacao_origem', $idsAlocacoes);
            })
            ->with([
                'alocacao:id_alocacao,nome',
                'alocacaoOrigem:id_alocacao,nome',
                'contaBancaria:id_conta_bancaria,nome',
                'contaBancariaResgate:id_conta_bancaria,nome',
            ])
            ->orderByDesc('data_movimentacao')
            ->orderByDesc('id_movimentacao_saldo_guardado')
            ->get();

        $movimentacoesPorAlocacao = [];

        foreach ($idsAlocacoes as $idAlocacao) {
            $movimentacoesPorAlocacao[$idAlocacao] = $movimentacoes
                ->filter(fn (MovimentacaoSaldoGuardado $movimentacao) => (int) $movimentacao->id_alocacao === $idAlocacao
                    || (int) $movimentacao->id_alocacao_origem === $idAlocacao)
                ->take($limitePorAlocacao)
                ->values();
        }

        return $movimentacoesPorAlocacao;
    }

    public function contemMovimentacoesDaConta(int $idContaBancaria): bool
    {
        return MovimentacaoSaldoGuardado::query()
            ->where(function ($query) use ($idContaBancaria) {
                $query->where('id_conta_bancaria', $idContaBancaria)
                    ->orWhere('id_conta_bancaria_origem', $idContaBancaria)
                    ->orWhere('id_conta_bancaria_resgate', $idContaBancaria);
            })
            ->exists();
    }

    public function contemMovimentacoesDaAlocacao(int $idAlocacao): bool
    {
        return MovimentacaoSaldoGuardado::query()
            ->where(function ($query) use ($idAlocacao) {
                $query->where('id_alocacao', $idAlocacao)
                    ->orWhere('id_alocacao_origem', $idAlocacao);
            })
            ->exists();
    }

    public function criar(array $dados): MovimentacaoSaldoGuardado
    {
        return MovimentacaoSaldoGuardado::query()->create($dados);
    }

    public function excluir(MovimentacaoSaldoGuardado $movimentacao): void
    {
        $movimentacao->delete();
    }

    private function acumularSaldo(array &$saldos, int $idAlocacao, int $idContaBancaria, float $valor): void
    {
        $saldos[$idAlocacao][$idContaBancaria] = round(($saldos[$idAlocacao][$idContaBancaria] ?? 0) + $valor, 2);
    }
}
