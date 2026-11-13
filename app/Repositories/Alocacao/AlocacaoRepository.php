<?php

namespace App\Repositories\Alocacao;

use App\Models\Alocacao\Alocacao;
use App\Models\PlanoFinanceiro\ItemPlanoFinanceiro;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AlocacaoRepository
{
    public function listarAlocacoesDoUsuario(int $idUsuario): Collection
    {
        return Alocacao::query()
            ->where('id_usuario', $idUsuario)
            ->orderBy('nome')
            ->get();
    }

    public function listarOpcoesDoUsuario(int $idUsuario): Collection
    {
        return Alocacao::query()
            ->where('id_usuario', $idUsuario)
            ->orderBy('nome')
            ->get(['id_alocacao', 'nome']);
    }

    public function paginarAlocacoesCadastradas(int $idUsuario, int $porPagina, string $nomePagina): LengthAwarePaginator
    {
        return Alocacao::query()
            ->where('id_usuario', $idUsuario)
            ->withCount(['itensPlano', 'movimentacoes', 'movimentacoesComoOrigem'])
            ->orderBy('nome')
            ->paginate($porPagina, ['*'], $nomePagina);
    }

    public function buscarAlocacaoDoUsuario(int $idAlocacao, int $idUsuario): ?Alocacao
    {
        return Alocacao::query()
            ->where('id_alocacao', $idAlocacao)
            ->where('id_usuario', $idUsuario)
            ->first();
    }

    public function bloquearAlocacoesParaMovimentacao(array $idsAlocacoes): void
    {
        Alocacao::query()
            ->whereIn('id_alocacao', $idsAlocacoes)
            ->orderBy('id_alocacao')
            ->lockForUpdate()
            ->get(['id_alocacao']);
    }

    public function existeAlocacaoComNome(int $idUsuario, string $nome, ?int $idAlocacaoIgnorada = null): bool
    {
        return Alocacao::query()
            ->where('id_usuario', $idUsuario)
            ->where('nome', $nome)
            ->when($idAlocacaoIgnorada !== null, fn ($query) => $query->where('id_alocacao', '!=', $idAlocacaoIgnorada))
            ->exists();
    }

    public function possuiItensPlanoVinculados(int $idAlocacao): bool
    {
        return ItemPlanoFinanceiro::query()
            ->where('id_alocacao', $idAlocacao)
            ->exists();
    }

    public function listarIdsDeTodasAlocacoes(): Collection
    {
        return Alocacao::query()
            ->orderBy('id_alocacao')
            ->pluck('id_alocacao')
            ->map(fn ($idAlocacao) => (int) $idAlocacao);
    }

    public function criar(array $dados): Alocacao
    {
        return Alocacao::query()->create($dados);
    }

    public function atualizar(Alocacao $alocacao, array $dados): Alocacao
    {
        $alocacao->update($dados);

        return $alocacao;
    }

    public function excluir(Alocacao $alocacao): void
    {
        $alocacao->delete();
    }

    public function atualizarSaldoConsolidado(int $idAlocacao, float $saldoConsolidado): void
    {
        Alocacao::query()
            ->where('id_alocacao', $idAlocacao)
            ->update([
                'saldo_consolidado' => $saldoConsolidado,
                'saldo_consolidado_em' => Carbon::now(),
            ]);
    }
}
