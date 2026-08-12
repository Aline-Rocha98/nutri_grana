<?php

namespace App\Services\Orcamento;

use App\Enum\SimNao;
use App\Enum\TipoCategoria;
use App\Enum\TipoOrcamento;
use App\Models\Categoria\Categoria;
use App\Models\Orcamento\Orcamento;
use App\Repositories\Lancamento\LancamentoRepository;
use App\Repositories\Orcamento\OrcamentoRepository;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrcamentoService
{
    public function __construct(
        private readonly OrcamentoRepository $orcamentoRepository,
        private readonly LancamentoRepository $lancamentoRepository,
        private readonly CalculadoraProgressoOrcamento $calculadoraProgresso,
    ) {}

    public function listarDoUsuario(int $idUsuario, ?TipoOrcamento $tipo = null, ?Carbon $referencia = null): Collection
    {
        $referencia = $referencia ?? Carbon::today();

        return $this->orcamentoRepository
            ->listarPorUsuario($idUsuario, $tipo)
            ->map(fn (Orcamento $orcamento) => $this->anexarResumo($orcamento, $idUsuario, $referencia));
    }

    public function listarParaDashboard(int $idUsuario, ?Carbon $referencia = null): Collection
    {
        $referencia = $referencia ?? Carbon::today();

        return $this->orcamentoRepository
            ->listarParaDashboard($idUsuario)
            ->map(fn (Orcamento $orcamento) => $this->anexarResumo($orcamento, $idUsuario, $referencia));
    }

    public function criar(int $idUsuario, array $dados): Orcamento
    {
        $tipo = TipoOrcamento::from($dados['tipo'] ?? TipoOrcamento::PorCategoria->value);
        $idCategoria = (int) ($dados['id_categoria'] ?? 0);

        $this->validarCategoriaParaOrcamento($idUsuario, $idCategoria);

        if ($this->orcamentoRepository->existeParaCategoria($idUsuario, $idCategoria, $tipo)) {
            throw ValidationException::withMessages([
                'id_categoria' => 'Já existe um orçamento para esta categoria.',
            ]);
        }

        $orcamento = $this->orcamentoRepository->criar([
            'id_usuario' => $idUsuario,
            'tipo' => $tipo,
            'id_categoria' => $idCategoria,
            'valor_mensal' => $dados['valor_mensal'],
            'exibir_dashboard' => $dados['exibir_dashboard'] ?? SimNao::Nao,
        ]);

        $orcamento->load('categoria');

        return $this->anexarResumo($orcamento, $idUsuario);
    }

    public function atualizar(Orcamento $orcamento, int $idUsuario, array $dados): Orcamento
    {
        $this->garantirPropriedade($orcamento, $idUsuario);

        $tipo = $orcamento->tipo instanceof TipoOrcamento
            ? $orcamento->tipo
            : TipoOrcamento::from($orcamento->tipo);

        $idCategoria = (int) ($dados['id_categoria'] ?? $orcamento->id_categoria);
        $this->validarCategoriaParaOrcamento($idUsuario, $idCategoria);

        if ($this->orcamentoRepository->existeParaCategoria(
            $idUsuario,
            $idCategoria,
            $tipo,
            (int) $orcamento->id_orcamento,
        )) {
            throw ValidationException::withMessages([
                'id_categoria' => 'Já existe um orçamento para esta categoria.',
            ]);
        }

        $atualizado = $this->orcamentoRepository->atualizar($orcamento, [
            'id_categoria' => $idCategoria,
            'valor_mensal' => $dados['valor_mensal'],
            'exibir_dashboard' => $dados['exibir_dashboard'] ?? $orcamento->exibir_dashboard,
        ]);

        $atualizado->load('categoria');

        return $this->anexarResumo($atualizado, $idUsuario);
    }

    public function excluir(Orcamento $orcamento, int $idUsuario): void
    {
        $this->garantirPropriedade($orcamento, $idUsuario);

        $this->orcamentoRepository->excluir($orcamento);
    }

    public function anexarResumo(Orcamento $orcamento, int $idUsuario, ?Carbon $referencia = null): Orcamento
    {
        $referencia = $referencia ?? Carbon::today();
        $idsCategorias = $this->idsDaCategoriaEFilhas((int) $orcamento->id_categoria);
        $gastoMes = $this->lancamentoRepository->somarDespesasDasCategoriasNoMes(
            $idUsuario,
            $idsCategorias,
            (int) $referencia->year,
            (int) $referencia->month,
        );

        $resumo = $this->calculadoraProgresso->montarResumo(
            (float) $orcamento->valor_mensal,
            $gastoMes,
        );

        foreach ($resumo as $chave => $valor) {
            $orcamento->setAttribute($chave, $valor);
        }

        $orcamento->setAttribute('mes_referencia', (int) $referencia->month);
        $orcamento->setAttribute('ano_referencia', (int) $referencia->year);
        $orcamento->setAttribute(
            'mes_referencia_rotulo',
            ucfirst($referencia->copy()->locale('pt_BR')->translatedFormat('F')) . '/' . $referencia->year
        );

        return $orcamento;
    }

    public function idsDaCategoriaEFilhas(int $idCategoriaPai): array
    {
        $filhas = Categoria::query()
            ->where('id_categoria_pai', $idCategoriaPai)
            ->pluck('id_categoria')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge([$idCategoriaPai], $filhas)));
    }

    public function garantirPropriedade(Orcamento $orcamento, int $idUsuario): void
    {
        if ((int) $orcamento->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Este orçamento não pertence ao usuário autenticado.');
        }
    }

    private function validarCategoriaParaOrcamento(int $idUsuario, int $idCategoria): void
    {
        if ($idCategoria <= 0) {
            throw ValidationException::withMessages([
                'id_categoria' => 'Selecione a categoria pai.',
            ]);
        }

        $categoria = Categoria::query()
            ->where('id_categoria', $idCategoria)
            ->where(function ($query) use ($idUsuario) {
                $query->where('id_usuario', $idUsuario)
                    ->orWhereNull('id_usuario');
            })
            ->first();

        if (! $categoria) {
            throw ValidationException::withMessages([
                'id_categoria' => 'Categoria inválida.',
            ]);
        }

        if (! $categoria->ehPrincipal()) {
            throw ValidationException::withMessages([
                'id_categoria' => 'O orçamento deve ser definido em uma categoria pai.',
            ]);
        }

        if ($categoria->tipo !== TipoCategoria::Saida) {
            throw ValidationException::withMessages([
                'id_categoria' => 'O orçamento por categoria só pode usar categorias de despesa.',
            ]);
        }

        if ($categoria->arquivada === SimNao::Sim) {
            throw ValidationException::withMessages([
                'id_categoria' => 'Não é possível usar uma categoria arquivada.',
            ]);
        }
    }
}
