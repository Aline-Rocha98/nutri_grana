<?php

namespace App\Repositories\ContasBancarias;

use App\Models\ContasBancarias\ContaBancaria;
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

        $contagens = DB::table('lancamentos')
            ->whereIn('id_conta_bancaria', $contas->pluck('id_conta_bancaria'))
            ->selectRaw('id_conta_bancaria, COUNT(*) as total')
            ->groupBy('id_conta_bancaria')
            ->pluck('total', 'id_conta_bancaria');

        return $contas->each(function (ContaBancaria $conta) use ($contagens): void {
            $conta->setAttribute(
                'total_lancamentos',
                (int) ($contagens[$conta->id_conta_bancaria] ?? 0)
            );
        });
    }

    public function buscarParaUsuario(int $id, int $idUsuario): ?ContaBancaria
    {
        $conta = ContaBancaria::query()
            ->where('id_conta_bancaria', $id)
            ->where('id_usuario', $idUsuario)
            ->first();

        if ($conta) {
            $conta->setAttribute(
                'total_lancamentos',
                $this->contarLancamentos((int) $conta->id_conta_bancaria)
            );
        }

        return $conta;
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
        return $this->contarLancamentos((int) $contaBancaria->id_conta_bancaria) > 0;
    }

    private function contarLancamentos(int $idContaBancaria): int
    {
        return (int) DB::table('lancamentos')
            ->where('id_conta_bancaria', $idContaBancaria)
            ->count();
    }
}
