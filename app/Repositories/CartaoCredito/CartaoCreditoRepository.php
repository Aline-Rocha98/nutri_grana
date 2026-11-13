<?php

namespace App\Repositories\CartaoCredito;

use App\Enum\SimNao;
use App\Enum\SituacaoFatura;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\FaturaCartao\FaturaCartao;
use App\Models\Lancamento\Lancamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CartaoCreditoRepository
{
    public function listarPorUsuario(int $idUsuario): Collection
    {
        $cartoes = CartaoCredito::query()
            ->where('id_usuario', $idUsuario)
            ->orderBy('arquivada')
            ->orderBy('nome')
            ->get();

        return $cartoes->each(function (CartaoCredito $cartao): void {
            $this->preencherUsoDoLimite($cartao);
            $cartao->setAttribute('tem_fatura_aberta', $this->temFaturaAberta($cartao));
        });
    }

    public function listarParaDashboard(int $idUsuario): Collection
    {
        $cartoes = CartaoCredito::query()
            ->where('id_usuario', $idUsuario)
            ->where('arquivada', SimNao::Nao)
            ->orderBy('nome')
            ->get();

        return $cartoes->each(fn (CartaoCredito $cartao) => $this->preencherUsoDoLimite($cartao));
    }

    public function buscarPreferencialDoUsuario(int $idUsuario): ?CartaoCredito
    {
        $cartao = CartaoCredito::query()
            ->where('id_usuario', $idUsuario)
            ->where('arquivada', SimNao::Nao)
            ->orderByRaw("CASE WHEN padrao = 'S' THEN 0 ELSE 1 END")
            ->orderBy('nome')
            ->first();

        if ($cartao) {
            $this->preencherUsoDoLimite($cartao);
        }

        return $cartao;
    }

    public function somarUsoEmAberto(int $idCartaoCredito): float
    {
        return (float) $this->consultaLancamentosEmAbertoDoCartao($idCartaoCredito)->sum('valor');
    }

    public function listarLancamentosRecentesDoCartao(int $idCartaoCredito, int $limite): Collection
    {
        return $this->consultaLancamentosEmAbertoDoCartao($idCartaoCredito)
            ->with(['categoria', 'faturaCartao'])
            ->orderByDesc('data_vencimento')
            ->orderByDesc('id_lancamento')
            ->limit($limite)
            ->get();
    }

    public function limparPadraoDoUsuario(int $idUsuario, ?int $excetoId = null): void
    {
        CartaoCredito::query()
            ->where('id_usuario', $idUsuario)
            ->when($excetoId !== null, fn ($query) => $query->where('id_cartao_credito', '!=', $excetoId))
            ->where('padrao', 'S')
            ->update(['padrao' => 'N']);
    }

    public function criar(array $dados): CartaoCredito
    {
        return CartaoCredito::query()->create($dados);
    }

    public function atualizar(CartaoCredito $cartaoCredito, array $dados): CartaoCredito
    {
        $cartaoCredito->update($dados);

        return $cartaoCredito->refresh();
    }

    public function excluir(CartaoCredito $cartaoCredito): void
    {
        $cartaoCredito->delete();
    }

    public function temFaturaAberta(CartaoCredito $cartaoCredito): bool
    {
        return FaturaCartao::query()
            ->where('id_cartao_credito', $cartaoCredito->id_cartao_credito)
            ->whereIn('situacao', [SituacaoFatura::Aberta, SituacaoFatura::Fechada])
            ->whereHas('lancamentos', fn ($q) => $q->where('situacao', '!=', SituacaoLancamento::Cancelado))
            ->exists();
    }

    private function preencherUsoDoLimite(CartaoCredito $cartao): void
    {
        $usado = $this->somarUsoEmAberto((int) $cartao->id_cartao_credito);
        $limiteTotal = (float) $cartao->limite_total;
        $limiteDisponivel = round(max(0, $limiteTotal - $usado), 2);
        $percentual = $limiteTotal > 0
            ? round(($usado / $limiteTotal) * 100, 1)
            : 0.0;

        $cartao->setAttribute('limite_usado', $usado);
        $cartao->setAttribute('limite_disponivel', $limiteDisponivel);
        $cartao->setAttribute('percentual_utilizado', $percentual);
    }

    private function consultaLancamentosEmAbertoDoCartao(int $idCartaoCredito): Builder
    {
        return Lancamento::query()
            ->where('id_cartao_credito', $idCartaoCredito)
            ->where('tipo', TipoLancamento::Despesa)
            ->where('eh_recorrencia', 'N')
            ->where('situacao', '!=', SituacaoLancamento::Cancelado)
            ->where(function ($query) {
                $query->whereNull('id_fatura_cartao')
                    ->orWhereHas('faturaCartao', function ($fatura) {
                        $fatura->whereIn('situacao', [
                            SituacaoFatura::Aberta,
                            SituacaoFatura::Fechada,
                        ]);
                    });
            });
    }
}
