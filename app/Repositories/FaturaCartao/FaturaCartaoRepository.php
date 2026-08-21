<?php

namespace App\Repositories\FaturaCartao;

use App\Enum\SituacaoFatura;
use App\Enum\SituacaoLancamento;
use App\Models\FaturaCartao\FaturaCartao;
use Illuminate\Support\Collection;

class FaturaCartaoRepository
{
    public function buscarPorCompetencia(int $idCartao, int $ano, int $mes): ?FaturaCartao
    {
        return FaturaCartao::query()
            ->where('id_cartao_credito', $idCartao)
            ->where('ano', $ano)
            ->where('mes', $mes)
            ->first();
    }

    public function criar(array $dados): FaturaCartao
    {
        return FaturaCartao::query()->create($dados);
    }

    public function atualizar(FaturaCartao $fatura, array $dados): FaturaCartao
    {
        $fatura->update($dados);

        return $fatura->refresh();
    }

    public function listarPorCartao(int $idCartao): Collection
    {
        return FaturaCartao::query()
            ->where('id_cartao_credito', $idCartao)
            ->withSum([
                'lancamentos as valor_total' => fn ($q) => $q
                    ->where('situacao', '!=', SituacaoLancamento::Cancelado),
            ], 'valor')
            ->orderByDesc('ano')
            ->orderByDesc('mes')
            ->get();
    }

    public function listarAbertasPorUsuario(int $idUsuario): Collection
    {
        return FaturaCartao::query()
            ->with('cartaoCredito')
            ->where('id_usuario', $idUsuario)
            ->whereIn('situacao', [SituacaoFatura::Aberta, SituacaoFatura::Fechada])
            ->withSum([
                'lancamentos as valor_total' => fn ($q) => $q
                    ->where('situacao', '!=', SituacaoLancamento::Cancelado),
            ], 'valor')
            ->orderByDesc('ano')
            ->orderByDesc('mes')
            ->get();
    }

    public function temFaturaAbertaNoCartao(int $idCartao): bool
    {
        return FaturaCartao::query()
            ->where('id_cartao_credito', $idCartao)
            ->whereIn('situacao', [SituacaoFatura::Aberta, SituacaoFatura::Fechada])
            ->whereHas('lancamentos', fn ($q) => $q->where('situacao', '!=', SituacaoLancamento::Cancelado))
            ->exists();
    }

    public function valorTotal(FaturaCartao $fatura): float
    {
        return (float) $fatura->lancamentos()
            ->where('situacao', '!=', SituacaoLancamento::Cancelado)
            ->sum('valor');
    }

    public function valorPorCompetencia(int $idCartao, int $ano, int $mes): float
    {
        $fatura = $this->buscarPorCompetencia($idCartao, $ano, $mes);

        if (!$fatura) {
            return 0.0;
        }

        return $this->valorTotal($fatura);
    }
}
