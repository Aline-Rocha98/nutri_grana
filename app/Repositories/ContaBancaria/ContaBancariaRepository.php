<?php

namespace App\Repositories\ContaBancaria;

use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Lancamento\Lancamento;
use Illuminate\Support\Collection;

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

        return $contas->each(function (ContaBancaria $conta): void {
            $movimentado = $this->saldoMovimentado((int) $conta->id_conta_bancaria);
            $totalLancamentos = $this->contarLancamentos((int) $conta->id_conta_bancaria);

            $conta->setAttribute('saldo_atual', (float) $conta->saldo_inicial + $movimentado);
            $conta->setAttribute('total_lancamentos', $totalLancamentos);
        });
    }

    public function buscarParaUsuario(int $id, int $idUsuario): ?ContaBancaria
    {
        return ContaBancaria::query()
            ->where('id_conta_bancaria', $id)
            ->where('id_usuario', $idUsuario)
            ->first();
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

    public function contarLancamentos(int $idContaBancaria): int
    {
        return Lancamento::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->count();
    }

    public function saldoMovimentado(int $idContaBancaria): float
    {
        $receitas = (float) Lancamento::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->where('tipo', TipoLancamento::Receita)
            ->where('situacao', SituacaoLancamento::Pago)
            ->where('eh_recorrencia', SimNao::Nao)
            ->sum('valor');

        $despesas = (float) Lancamento::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->where('tipo', TipoLancamento::Despesa)
            ->where('situacao', SituacaoLancamento::Pago)
            ->where('eh_recorrencia', SimNao::Nao)
            ->sum('valor');

        return $receitas - $despesas;
    }
}
