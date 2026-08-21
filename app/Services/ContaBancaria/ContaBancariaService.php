<?php

namespace App\Services\ContaBancaria;

use App\Enum\SimNao;
use App\Models\ContaBancaria\ContaBancaria;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Support\Dashboard\DashboardCache;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ContaBancariaService
{
    public function __construct(
        private readonly ContaBancariaRepository $contaBancariaRepository,
    ) {}

    public function listarPorUsuario(int $idUsuario): Collection
    {
        return $this->contaBancariaRepository->listarPorUsuario($idUsuario);
    }

    public function criar(int $idUsuario, array $dados): ContaBancaria
    {
        $padraoDesconto = $dados['padrao_desconto'] ?? null;
        $exibirResumo = $dados['exibir_resumo'] ?? null;

        if ($padraoDesconto === SimNao::Sim || $padraoDesconto === SimNao::Sim->value) {
            $this->contaBancariaRepository->limparPadraoDescontoDoUsuario($idUsuario);
        }

        $conta = $this->contaBancariaRepository->criar([
            'id_usuario' => $idUsuario,
            'nome' => $dados['nome'],
            'saldo_inicial' => $dados['saldo_inicial'],
            'tipo' => $dados['tipo'],
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => $padraoDesconto,
            'exibir_resumo' => $exibirResumo,
        ]);

        DashboardCache::invalidar($idUsuario);

        return $conta;
    }

    public function atualizar(ContaBancaria $contaBancaria, int $idUsuario, array $dados): ContaBancaria
    {
        $this->garantirPropriedade($contaBancaria, $idUsuario);

        $dadosAtualizacao = [
            'nome' => $dados['nome'],
            'tipo' => $dados['tipo'],
        ];

        if (array_key_exists('arquivada', $dados)) {
            $dadosAtualizacao['arquivada'] = $dados['arquivada'];
        }

        if (array_key_exists('padrao_desconto', $dados)) {
            $padraoDesconto = $dados['padrao_desconto'];
            $dadosAtualizacao['padrao_desconto'] = $padraoDesconto;

            if ($padraoDesconto === SimNao::Sim || $padraoDesconto === SimNao::Sim->value) {
                $this->contaBancariaRepository->limparPadraoDescontoDoUsuario(
                    $idUsuario,
                    (int) $contaBancaria->id_conta_bancaria
                );
            }
        }

        if (array_key_exists('exibir_resumo', $dados)) {
            $dadosAtualizacao['exibir_resumo'] = $dados['exibir_resumo'];
        }

        $atualizada = $this->contaBancariaRepository->atualizar($contaBancaria, $dadosAtualizacao);

        DashboardCache::invalidar($idUsuario);

        return $atualizada;
    }

    public function arquivar(ContaBancaria $contaBancaria, int $idUsuario, SimNao $arquivada = SimNao::Sim): ContaBancaria
    {
        $this->garantirPropriedade($contaBancaria, $idUsuario);

        $atualizada = $this->contaBancariaRepository->atualizar($contaBancaria, [
            'arquivada' => $arquivada,
        ]);

        DashboardCache::invalidar($idUsuario);

        return $atualizada;
    }

    public function excluir(ContaBancaria $contaBancaria, int $idUsuario): void
    {
        $this->garantirPropriedade($contaBancaria, $idUsuario);

        if ($this->contaBancariaRepository->temLancamentos($contaBancaria)) {
            throw ValidationException::withMessages([
                'conta_bancaria' => 'Não é possível excluir uma conta com lançamentos vinculados. Arquive a conta bancária em vez de excluí-la.',
            ]);
        }

        $this->contaBancariaRepository->excluir($contaBancaria);

        DashboardCache::invalidar($idUsuario);
    }

    private function garantirPropriedade(ContaBancaria $contaBancaria, int $idUsuario): void
    {
        if ((int) $contaBancaria->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Esta conta bancária não pertence ao usuário autenticado.');
        }
    }
}
