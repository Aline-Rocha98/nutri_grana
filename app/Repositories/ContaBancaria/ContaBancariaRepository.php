<?php

namespace App\Repositories\ContaBancaria;

use App\Models\ContaBancaria\ContaBancaria;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContaBancariaRepository
{
    public function listarPorUsuario(int $idUsuario): Collection
    {
        $contas = ContaBancaria::query()
            ->where('id_usuario', $idUsuario)
            ->orderBy('arquivada')
            ->orderBy('nome')
            ->get();

        if ($contas->isEmpty()) {
            return $contas;
        }

        return $contas;
    }

    public function buscarParaUsuario(int $id, int $idUsuario): ?ContaBancaria
    {
        $conta = ContaBancaria::query()
            ->where('id_conta_bancaria', $id)
            ->where('id_usuario', $idUsuario)
            ->first();

        if ($conta) {
            return $conta;
        }

        return null;
    }

    public function limparPadraoDescontoDoUsuario(int $idUsuario, ?int $excetoId = null): void
    {
        ContaBancaria::query()
            ->where('id_usuario', $idUsuario)
            ->when($excetoId !== null, fn ($query) => $query->where('id_conta_bancaria', '!=', $excetoId))
            ->where('padrao_desconto', 'S')
            ->update(['padrao_desconto' => 'N']);
    }

    public function criar(array $dados): ContaBancaria
    {
        return ContaBancaria::query()->create($dados);
    }

    public function atualizar(ContaBancaria $contaBancaria, array $dados): ContaBancaria
    {
        $contaBancaria->update($dados);

        return $contaBancaria->refresh();
    }

    public function excluir(ContaBancaria $contaBancaria): void
    {
        $contaBancaria->delete();
    }

    public function temLancamentos(ContaBancaria $contaBancaria): bool
    {
        return false;
    }

}
