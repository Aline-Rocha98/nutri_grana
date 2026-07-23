<?php

namespace App\Services\ContasBancarias;

use App\Enum\SimNao;
use App\Models\ContasBancarias\ContaBancaria;
use App\Repositories\ContasBancarias\ContaBancariaRepository;
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

        return $this->contaBancariaRepository->criar([
            'id_usuario' => $idUsuario,
            'nome' => $dados['nome'],
            'saldo_inicial' => $dados['saldo_inicial'],
            'tipo' => $dados['tipo'],
            'arquivada' => false,
            'padrao_desconto' => $padraoDesconto,
            'exibir_resumo' => $exibirResumo,
        ]);
    }

    public function atualizar(ContaBancaria $contaBancaria, int $idUsuario, array $dados): ContaBancaria
    {
        $this->garantirPropriedade($contaBancaria, $idUsuario);

        $dadosAtualizacao = [
            'nome' => $dados['nome'],
            'tipo' => $dados['tipo'],
        ];

        if (array_key_exists('arquivada', $dados)) {
            $dadosAtualizacao['arquivada'] = (bool) $dados['arquivada'];
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

        return $this->contaBancariaRepository->atualizar($contaBancaria, $dadosAtualizacao);
    }

    public function arquivar(ContaBancaria $contaBancaria, int $idUsuario, bool $arquivada = true): ContaBancaria
    {
        $this->garantirPropriedade($contaBancaria, $idUsuario);

        return $this->contaBancariaRepository->atualizar($contaBancaria, [
            'arquivada' => $arquivada,
        ]);
    }

    public function excluir(ContaBancaria $contaBancaria, int $idUsuario): void
    {
        $this->garantirPropriedade($contaBancaria, $idUsuario);

        if ($this->contaBancariaRepository->temLancamentos($contaBancaria)) {
            throw ValidationException::withMessages([
                'conta_bancaria' => 'Não é possível excluir uma conta com lançamentos vinculados.',
            ]);
        }

        $this->contaBancariaRepository->excluir($contaBancaria);
    }

    private function garantirPropriedade(ContaBancaria $contaBancaria, int $idUsuario): void
    {
        if ((int) $contaBancaria->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Esta conta bancária não pertence ao usuário autenticado.');
        }
    }
}
