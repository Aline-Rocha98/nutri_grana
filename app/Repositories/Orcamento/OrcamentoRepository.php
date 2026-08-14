<?php

namespace App\Repositories\Orcamento;

use App\Enum\SimNao;
use App\Enum\TipoOrcamento;
use App\Models\Orcamento\Orcamento;
use Illuminate\Support\Collection;

class OrcamentoRepository
{
    public function listarPorUsuario(int $idUsuario, ?TipoOrcamento $tipo = null): Collection
    {
        return Orcamento::query()
            ->where('id_usuario', $idUsuario)
            ->when($tipo, fn ($query, TipoOrcamento $tipoFiltro) => $query->where('tipo', $tipoFiltro))
            ->with(['categoria.subcategorias'])
            ->orderBy('id_orcamento')
            ->get();
    }

    public function listarParaDashboard(int $idUsuario): Collection
    {
        return Orcamento::query()
            ->where('id_usuario', $idUsuario)
            ->where('exibir_dashboard', SimNao::Sim)
            ->with(['categoria'])
            ->orderBy('id_orcamento')
            ->get();
    }

    public function buscarPorCategoria(
        int $idUsuario,
        int $idCategoria,
        TipoOrcamento $tipo = TipoOrcamento::PorCategoria,
    ): ?Orcamento {
        return Orcamento::query()
            ->where('id_usuario', $idUsuario)
            ->where('tipo', $tipo)
            ->where('id_categoria', $idCategoria)
            ->first();
    }

    public function existeParaCategoria(
        int $idUsuario,
        int $idCategoria,
        TipoOrcamento $tipo = TipoOrcamento::PorCategoria,
        ?int $excetoIdOrcamento = null,
    ): bool {
        return Orcamento::query()
            ->where('id_usuario', $idUsuario)
            ->where('tipo', $tipo)
            ->where('id_categoria', $idCategoria)
            ->when(
                $excetoIdOrcamento,
                fn ($query, int $id) => $query->where('id_orcamento', '!=', $id)
            )
            ->exists();
    }

    public function criar(array $dados): Orcamento
    {
        return Orcamento::query()->create($dados);
    }

    public function atualizar(Orcamento $orcamento, array $dados): Orcamento
    {
        $orcamento->update($dados);

        return $orcamento->refresh();
    }

    public function excluir(Orcamento $orcamento): void
    {
        $orcamento->delete();
    }
}
