<?php

namespace App\Services\CartaoCredito;

use App\Enum\SimNao;
use App\Models\CartaoCredito\CartaoCredito;
use App\Repositories\CartaoCredito\CartaoCreditoRepository;
use App\Support\Dashboard\DashboardCache;
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

    public function listarLancamentosRecentesPorCartao(Collection $cartoesCredito, int $limitePorCartao = 10): array
    {
        return $cartoesCredito
            ->reject(fn (CartaoCredito $cartao) => $cartao->arquivada === SimNao::Sim)
            ->mapWithKeys(fn (CartaoCredito $cartao) => [
                $cartao->id_cartao_credito => $this->cartaoCreditoRepository->listarLancamentosRecentesDoCartao(
                    (int) $cartao->id_cartao_credito,
                    $limitePorCartao
                ),
            ])
            ->all();
    }

    public function criar(int $idUsuario, array $dados): CartaoCredito
    {
        $padrao = $dados['padrao'] ?? null;

        if ($this->ehSim($padrao)) {
            $this->cartaoCreditoRepository->limparPadraoDoUsuario($idUsuario);
        }

        $cartao = $this->cartaoCreditoRepository->criar([
            ...$this->montarDadosCartaoCredito($dados),
            'id_usuario' => $idUsuario,
            'padrao' => $padrao,
            'arquivada' => SimNao::Nao,
        ]);

        DashboardCache::invalidar($idUsuario);

        return $cartao;
    }

    public function atualizar(CartaoCredito $cartaoCredito, int $idUsuario, array $dados): CartaoCredito
    {
        $this->garantirPropriedade($cartaoCredito, $idUsuario);

        $dadosAtualizacao = $this->montarDadosCartaoCredito($dados);

        if (array_key_exists('padrao', $dados)) {
            $padrao = $dados['padrao'];
            $dadosAtualizacao['padrao'] = $padrao;

            if ($this->ehSim($padrao)) {
                $this->cartaoCreditoRepository->limparPadraoDoUsuario(
                    $idUsuario,
                    (int) $cartaoCredito->id_cartao_credito
                );
            }
        }

        $atualizado = $this->cartaoCreditoRepository->atualizar($cartaoCredito, $dadosAtualizacao);

        DashboardCache::invalidar($idUsuario);

        return $atualizado;
    }

    public function definirCartaoCreditoPadrao(CartaoCredito $cartaoCredito, int $idUsuario): CartaoCredito
    {
        $this->garantirPropriedade($cartaoCredito, $idUsuario);

        $this->cartaoCreditoRepository->limparPadraoDoUsuario(
            $idUsuario,
            (int) $cartaoCredito->id_cartao_credito
        );

        $atualizado = $this->cartaoCreditoRepository->atualizar($cartaoCredito, [
            'padrao' => SimNao::Sim,
        ]);

        DashboardCache::invalidar($idUsuario);

        return $atualizado;
    }

    public function arquivar(CartaoCredito $cartaoCredito, int $idUsuario, SimNao $arquivada = SimNao::Sim): CartaoCredito
    {
        $this->garantirPropriedade($cartaoCredito, $idUsuario);

        $atualizado = $this->cartaoCreditoRepository->atualizar($cartaoCredito, [
            'arquivada' => $arquivada,
        ]);

        DashboardCache::invalidar($idUsuario);

        return $atualizado;
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

        DashboardCache::invalidar($idUsuario);
    }

    private function montarDadosCartaoCredito(array $dados): array
    {
        return [
            'nome' => $dados['nome'],
            'nome_titular' => $dados['nome_titular'],
            'limite_total' => $dados['limite_total'],
            'dia_fechamento' => $dados['dia_fechamento'],
            'dia_vencimento' => $dados['dia_vencimento'],
            'bandeira' => $dados['bandeira'],
            'ultimos_digitos' => $dados['ultimos_digitos'] ?? null,
            'acabamento' => $dados['acabamento'],
        ];
    }

    private function ehSim(mixed $valor): bool
    {
        return $valor === SimNao::Sim || $valor === SimNao::Sim->value;
    }

    private function garantirPropriedade(CartaoCredito $cartaoCredito, int $idUsuario): void
    {
        if ((int) $cartaoCredito->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Este cartão de crédito não pertence ao usuário autenticado.');
        }
    }
}
