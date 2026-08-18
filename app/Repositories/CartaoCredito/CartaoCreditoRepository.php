<?php

namespace App\Repositories\CartaoCredito;

use App\Enum\SituacaoFatura;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\FaturaCartao\FaturaCartao;
use App\Models\Lancamento\Lancamento;
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
            $usado = $this->somarUsoEmAberto((int) $cartao->id_cartao_credito);
            $limiteDisponivel = round(max(0, (float) $cartao->limite_total - $usado), 2);

            $cartao->setAttribute('tem_fatura_aberta', $this->temFaturaAberta($cartao));
            $cartao->setAttribute('limite_usado', $usado);
            $cartao->setAttribute('limite_disponivel', $limiteDisponivel);
        });
    }

    public function somarUsoEmAberto(int $idCartaoCredito): float
    {
        return (float) Lancamento::query()
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
            })
            ->sum('valor');
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
}
