<?php

namespace App\Services\CartaoCredito;

use App\Enum\SimNao;
use App\Models\CartaoCredito\CartaoCredito;
use App\Repositories\CartaoCredito\CartaoCreditoRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartaoCreditoService
{
    public function __construct(
        private readonly CartaoCreditoRepository $cartaoCreditoRepository,
    ) {}

    public function listarPorUsuario(int $idUsuario): Collection
    {
        return $this->cartaoCreditoRepository->listarPorUsuario($idUsuario);
    }

    public function criar(int $idUsuario, array $dados): CartaoCredito
    {
        $padrao = $dados['padrao'] ?? null;

        if ($padrao === SimNao::Sim || $padrao === SimNao::Sim->value) {
            $this->cartaoCreditoRepository->limparPadraoDoUsuario($idUsuario);
        }

        return $this->cartaoCreditoRepository->criar([
            'id_usuario' => $idUsuario,
            'nome' => $dados['nome'],
            'limite_total' => $dados['limite_total'],
            'dia_fechamento' => $dados['dia_fechamento'],
            'dia_vencimento' => $dados['dia_vencimento'],
            'bandeira' => $dados['bandeira'],
            'padrao' => $padrao,
            'arquivada' => SimNao::Nao,
        ]);
    }

    public function atualizar(CartaoCredito $cartaoCredito, int $idUsuario, array $dados): CartaoCredito
    {
        $this->garantirPropriedade($cartaoCredito, $idUsuario);

        $dadosAtualizacao = [
            'nome' => $dados['nome'],
            'limite_total' => $dados['limite_total'],
            'dia_fechamento' => $dados['dia_fechamento'],
            'dia_vencimento' => $dados['dia_vencimento'],
            'bandeira' => $dados['bandeira'],
        ];

        if (array_key_exists('padrao', $dados)) {
            $padrao = $dados['padrao'];
            $dadosAtualizacao['padrao'] = $padrao;

            if ($padrao === SimNao::Sim || $padrao === SimNao::Sim->value) {
                $this->cartaoCreditoRepository->limparPadraoDoUsuario(
                    $idUsuario,
                    (int) $cartaoCredito->id_cartao_credito
                );
            }
        }

        return $this->cartaoCreditoRepository->atualizar($cartaoCredito, $dadosAtualizacao);
    }

    public function arquivar(CartaoCredito $cartaoCredito, int $idUsuario, SimNao $arquivada = SimNao::Sim): CartaoCredito
    {
        $this->garantirPropriedade($cartaoCredito, $idUsuario);

        return $this->cartaoCreditoRepository->atualizar($cartaoCredito, [
            'arquivada' => $arquivada,
        ]);
    }

    public function excluir(CartaoCredito $cartaoCredito, int $idUsuario): void
    {
        $this->garantirPropriedade($cartaoCredito, $idUsuario);

        if ($this->cartaoCreditoRepository->temFaturaAberta($cartaoCredito)) {
            throw ValidationException::withMessages([
                'cartao_credito' => 'Não é possível excluir um cartão com fatura em aberto. Dê baixa na fatura antes de excluí-lo.',
            ]);
        }

        $this->cartaoCreditoRepository->excluir($cartaoCredito);
    }

    private function garantirPropriedade(CartaoCredito $cartaoCredito, int $idUsuario): void
    {
        if ((int) $cartaoCredito->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Este cartão de crédito não pertence ao usuário autenticado.');
        }
    }
}
