<?php

namespace App\Repositories\Orcamento;

use App\Models\Orcamento\OrcamentoServico;
use Illuminate\Support\Collection;

class OrcamentoServicoRepository
{
    public function listarPorUsuario(int $idUsuario): Collection
    {
        return OrcamentoServico::query()
            ->where('id_usuario', $idUsuario)
            ->with(['categoria', 'subcategoria', 'contaBancaria', 'cartaoCredito', 'compromissos'])
            ->orderByDesc('data_orcamento')
            ->orderByDesc('id_orcamento_servico')
            ->get();
    }

    public function buscarParaUsuario(int $id, int $idUsuario): ?OrcamentoServico
    {
        return OrcamentoServico::query()
            ->where('id_orcamento_servico', $id)
            ->where('id_usuario', $idUsuario)
            ->first();
    }

    public function criar(array $dados): OrcamentoServico
    {
        return OrcamentoServico::query()->create($dados);
    }

    public function atualizar(OrcamentoServico $orcamentoServico, array $dados): OrcamentoServico
    {
        $orcamentoServico->update($dados);

        return $orcamentoServico->refresh();
    }

    public function excluir(OrcamentoServico $orcamentoServico): void
    {
        $orcamentoServico->delete();
    }
}
