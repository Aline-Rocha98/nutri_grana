<?php

namespace App\Repositories\CartaoCredito;

use App\Models\CartaoCredito\CartaoCredito;
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
            $cartao->setAttribute(
                'tem_fatura_aberta',
                $this->temFaturaAberta($cartao)
            );
        });
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

    /**
     * Implementação futura para verificar status de fatura aberta.
     */
    public function temFaturaAberta(CartaoCredito $cartaoCredito): bool
    {
        return false;
    }
}
