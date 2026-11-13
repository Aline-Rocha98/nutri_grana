<?php

namespace App\Repositories\Renda;

use App\Models\Renda\Renda;
use Illuminate\Support\Collection;

class RendaRepository
{
    public function listarPorUsuario(int $idUsuario): Collection
    {
        return Renda::query()
            ->where('id_usuario', $idUsuario)
            ->with([
                'contaBancaria',
                'planoFinanceiro:id_plano_financeiro,nome',
                'planoFinanceiro.itens.contaBancariaDestino:id_conta_bancaria,nome',
                'planoFinanceiro.itens.objetivo:id_objetivo,descricao',
            ])
            ->orderBy('descricao')
            ->get();
    }

    public function listarAtivasPorUsuario(int $idUsuario): Collection
    {
        return Renda::query()
            ->where('id_usuario', $idUsuario)
            ->orderBy('id_renda')
            ->get();
    }

    public function criar(array $dados): Renda
    {
        return Renda::query()->create($dados);
    }

    public function atualizar(Renda $renda, array $dados): Renda
    {
        $renda->update($dados);

        return $renda->refresh();
    }

    public function excluir(Renda $renda): void
    {
        $renda->delete();
    }
}
