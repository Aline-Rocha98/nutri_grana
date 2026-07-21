<?php

namespace App\Services\ContasBancarias;

use App\Models\ContasBancarias\ContaBancaria;
use App\Repositories\ContasBancarias\ContaBancariaRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ContaBancariaService
{
    public function __construct(
        private readonly ContaBancariaRepository $repositorio,
    ) {}

    public function listarPorUsuario(int $idUsuario): Collection
    {
        return $this->repositorio->listarPorUsuario($idUsuario);
    }

    public function criar(int $idUsuario, array $dados): ContaBancaria
    {
        return $this->repositorio->criar([
            'id_usuario' => $idUsuario,
            'nome' => $dados['nome'],
            'saldo_inicial' => $dados['saldo_inicial'],
            'tipo' => $dados['tipo'],
            'arquivada' => false,
        ]);
    }

    public function atualizar(ContaBancaria $contaBancaria, int $idUsuario, array $dados): ContaBancaria
    {
        $this->garantirPropriedade($contaBancaria, $idUsuario);

        $dadosAtualizacao = [
            'nome' => $dados['nome'],
            'saldo_inicial' => $dados['saldo_inicial'],
            'tipo' => $dados['tipo'],
        ];

        if (array_key_exists('arquivada', $dados)) {
            $dadosAtualizacao['arquivada'] = (bool) $dados['arquivada'];
        }

        return $this->repositorio->atualizar($contaBancaria, $dadosAtualizacao);
    }

    public function arquivar(ContaBancaria $contaBancaria, int $idUsuario, bool $arquivada = true): ContaBancaria
    {
        $this->garantirPropriedade($contaBancaria, $idUsuario);

        return $this->repositorio->atualizar($contaBancaria, [
            'arquivada' => $arquivada,
        ]);
    }

    public function excluir(ContaBancaria $contaBancaria, int $idUsuario): void
    {
        $this->garantirPropriedade($contaBancaria, $idUsuario);

        if ($this->repositorio->temLancamentos($contaBancaria)) {
            throw ValidationException::withMessages([
                'conta_bancaria' => 'Não é possível excluir uma conta com lançamentos vinculados.',
            ]);
        }

        $this->repositorio->excluir($contaBancaria);
    }

    private function garantirPropriedade(ContaBancaria $contaBancaria, int $idUsuario): void
    {
        if ((int) $contaBancaria->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Esta conta bancária não pertence ao usuário autenticado.');
        }
    }
}
