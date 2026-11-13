<?php

namespace App\Repositories\PlanoFinanceiro;

use App\Enum\SimNao;
use App\Enum\SituacaoPlanoFinanceiro;
use App\Models\PlanoFinanceiro\ItemPlanoFinanceiro;
use App\Models\PlanoFinanceiro\PlanoFinanceiro;
use App\Models\Renda\Renda;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanoFinanceiroRepository
{
    public function listarPlanosDoUsuario(int $idUsuario): Collection
    {
        return PlanoFinanceiro::query()
            ->where('id_usuario', $idUsuario)
            ->with(['itens', 'itens.alocacao:id_alocacao,nome'])
            ->withCount('rendas')
            ->orderByRaw("CASE WHEN padrao = 'S' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN situacao = 'ativo' THEN 0 ELSE 1 END")
            ->orderBy('nome')
            ->get();
    }

    public function listarPlanosAtivosParaSelecao(int $idUsuario): Collection
    {
        return PlanoFinanceiro::query()
            ->where('id_usuario', $idUsuario)
            ->where('situacao', SituacaoPlanoFinanceiro::Ativo)
            ->with([
                'itens',
                'itens.contaBancariaDestino:id_conta_bancaria,nome',
                'itens.objetivo:id_objetivo,descricao',
            ])
            ->orderByRaw("CASE WHEN padrao = 'S' THEN 0 ELSE 1 END")
            ->orderBy('nome')
            ->get();
    }

    public function buscarPlanoDoUsuario(int $idPlanoFinanceiro, int $idUsuario): ?PlanoFinanceiro
    {
        return PlanoFinanceiro::query()
            ->where('id_plano_financeiro', $idPlanoFinanceiro)
            ->where('id_usuario', $idUsuario)
            ->with(['itens'])
            ->first();
    }

    public function buscarNomeDoPlano(int $idPlanoFinanceiro): string
    {
        return (string) PlanoFinanceiro::query()
            ->where('id_plano_financeiro', $idPlanoFinanceiro)
            ->value('nome');
    }
    
    public function buscarItensQueSeparamDasRendas(array $idsRendas): Collection
    {
        if ($idsRendas === []) {
            return collect();
        }

        return ItemPlanoFinanceiro::query()
            ->select('itens_plano.*', 'rendas.id_renda as id_renda_vinculada')
            ->join('rendas', 'rendas.id_plano_financeiro', '=', 'itens_plano.id_plano_financeiro')
            ->whereIn('rendas.id_renda', $idsRendas)
            ->where('itens_plano.separar_do_saldo', SimNao::Sim)
            ->orderBy('itens_plano.ordem')
            ->orderBy('itens_plano.id_item_plano')
            ->get()
            ->groupBy('id_renda_vinculada');
    }

    public function listarRendasVinculadas(int $idPlanoFinanceiro): Collection
    {
        return Renda::query()
            ->where('id_plano_financeiro', $idPlanoFinanceiro)
            ->orderBy('descricao')
            ->get(['id_renda', 'descricao', 'valor_esperado']);
    }

    public function contarRendasVinculadas(int $idPlanoFinanceiro): int
    {
        return Renda::query()
            ->where('id_plano_financeiro', $idPlanoFinanceiro)
            ->count();
    }

    public function usuarioPossuiPlanos(int $idUsuario): bool
    {
        return PlanoFinanceiro::query()
            ->where('id_usuario', $idUsuario)
            ->exists();
    }

    public function criar(array $dados): PlanoFinanceiro
    {
        return PlanoFinanceiro::query()->create($dados);
    }

    public function atualizar(PlanoFinanceiro $planoFinanceiro, array $dados): PlanoFinanceiro
    {
        $planoFinanceiro->update($dados);

        return $planoFinanceiro;
    }

    public function excluir(PlanoFinanceiro $planoFinanceiro): void
    {
        $planoFinanceiro->delete();
    }

    public function removerPadraoDosDemaisPlanos(int $idUsuario, int $idPlanoMantido): void
    {
        PlanoFinanceiro::query()
            ->where('id_usuario', $idUsuario)
            ->where('id_plano_financeiro', '!=', $idPlanoMantido)
            ->where('padrao', SimNao::Sim)
            ->update(['padrao' => SimNao::Nao]);
    }

    public function sincronizarItensDoPlano(PlanoFinanceiro $planoFinanceiro, int $idUsuario, array $itensNormalizados): void
    {
        $idPlanoFinanceiro = (int) $planoFinanceiro->id_plano_financeiro;
        $idsExistentes = ItemPlanoFinanceiro::query()
            ->where('id_plano_financeiro', $idPlanoFinanceiro)
            ->pluck('id_item_plano')
            ->map(fn ($id) => (int) $id)
            ->all();

        $idsMantidos = [];
        $itensNovos = [];
        $agora = Carbon::now();

        foreach (array_values($itensNormalizados) as $ordem => $dadosItem) {
            $idItem = (int) ($dadosItem['id_item_plano'] ?? 0);
            $valoresItem = $this->montarValoresPersistenciaItem($dadosItem, $ordem);

            if ($idItem > 0 && in_array($idItem, $idsExistentes, true)) {
                ItemPlanoFinanceiro::query()
                    ->where('id_item_plano', $idItem)
                    ->update([...$valoresItem, 'updated_at' => $agora]);

                $idsMantidos[] = $idItem;

                continue;
            }

            $itensNovos[] = [
                ...$valoresItem,
                'id_plano_financeiro' => $idPlanoFinanceiro,
                'id_usuario' => $idUsuario,
                'created_at' => $agora,
                'updated_at' => $agora,
            ];
        }

        ItemPlanoFinanceiro::query()
            ->where('id_plano_financeiro', $idPlanoFinanceiro)
            ->when($idsMantidos !== [], fn ($query) => $query->whereNotIn('id_item_plano', $idsMantidos))
            ->delete();

        if ($itensNovos !== []) {
            ItemPlanoFinanceiro::query()->insert($itensNovos);
        }
    }

    private function montarValoresPersistenciaItem(array $dadosItem, int $ordem): array
    {
        return [
            'nome' => $dadosItem['nome'],
            'descricao' => $dadosItem['descricao'],
            'cor' => $dadosItem['cor']->value,
            'tipo_calculo' => $dadosItem['tipo_calculo']->value,
            'valor' => $dadosItem['valor'],
            'separar_do_saldo' => $dadosItem['separar_do_saldo']->value,
            'id_conta_bancaria_destino' => $dadosItem['id_conta_bancaria_destino'],
            'id_objetivo' => $dadosItem['id_objetivo'],
            'id_alocacao' => $dadosItem['id_alocacao'],
            'ordem' => $ordem,
        ];
    }
}
