<?php

namespace App\Services\Orcamento;

use App\Enum\TipoLancamento;
use App\Enum\TipoOrcamento;
use App\Models\Categoria\Categoria;
use App\Models\Lancamento\Lancamento;
use App\Repositories\Lancamento\LancamentoRepository;
use App\Repositories\Orcamento\OrcamentoRepository;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class VerificadorUltrapassagemOrcamento
{
    public function __construct(
        private readonly OrcamentoRepository $orcamentoRepository,
        private readonly LancamentoRepository $lancamentoRepository,
        private readonly OrcamentoService $orcamentoService,
    ) {}

    public function garantirDentroDoLimiteOuConfirmado(int $idUsuario, array $dados, ?Lancamento $lancamentoAtual = null,): void 
    {
        if ($this->confirmouUltrapassagem($dados)) {
            return;
        }

        $tipo = $dados['tipo'] ?? null;
        $tipoEnum = $tipo instanceof TipoLancamento
            ? $tipo
            : (is_string($tipo) ? TipoLancamento::tryFrom($tipo) : null);

        if ($tipoEnum !== TipoLancamento::Despesa) {
            return;
        }

        $idCategoria = $dados['id_categoria'] ?? null;
        if ($idCategoria === null || $idCategoria === '') {
            return;
        }

        $categoriaPai = $this->resolverCategoriaPai((int) $idCategoria);
        if (!$categoriaPai) {
            return;
        }

        $orcamento = $this->orcamentoRepository->buscarPorCategoria(
            $idUsuario,
            (int) $categoriaPai->id_categoria,
            TipoOrcamento::PorCategoria,
        );

        if (!$orcamento) {
            return;
        }

        $valorImpacto = $this->calcularValorImpactoNoMes($dados);
        $dataReferencia = Carbon::parse($dados['data_vencimento'] ?? now());
        $idsCategorias = $this->orcamentoService->idsDaCategoriaEFilhas((int) $categoriaPai->id_categoria);

        $gastoAtual = $this->lancamentoRepository->somarDespesasDasCategoriasNoMes(
            $idUsuario,
            $idsCategorias,
            (int) $dataReferencia->year,
            (int) $dataReferencia->month,
            $lancamentoAtual?->id_lancamento,
        );

        $gastoProjetado = round($gastoAtual + $valorImpacto, 2);
        $valorMensal = (float) $orcamento->valor_mensal;

        if ($gastoProjetado <= $valorMensal) {
            return;
        }

        $nomeCategoria = $categoriaPai->nome;
        $gastoFormatado = number_format($gastoProjetado, 2, ',', '.');
        $limiteFormatado = number_format($valorMensal, 2, ',', '.');

        throw ValidationException::withMessages([
            'confirmar_ultrapassagem_orcamento' => sprintf(
                "Este lançamento ultrapassa o valor mensal de R$ %s que você definiu para a categoria %s.\n\nCom este lançamento, o mês fica em R$ %s.\n\nDeseja confirmar mesmo assim?",
                $limiteFormatado,
                $nomeCategoria,
                $gastoFormatado,
            ),
        ]);
    }

    private function calcularValorImpactoNoMes(array $dados): float
    {
        $valor = (float) ($dados['valor'] ?? 0);
        $parcelas = (int) ($dados['total_parcelas'] ?? 1);

        if ($parcelas > 1) {
            $valorParcela = round($valor / $parcelas, 2);

            return $valorParcela;
        }

        return round($valor, 2);
    }

    private function resolverCategoriaPai(int $idCategoria): ?Categoria
    {
        $categoria = Categoria::query()->find($idCategoria);

        if (! $categoria) {
            return null;
        }

        if ($categoria->ehPrincipal()) {
            return $categoria;
        }

        return $categoria->pai;
    }

    private function confirmouUltrapassagem(array $dados): bool
    {
        $valor = $dados['confirmar_ultrapassagem_orcamento'] ?? false;

        return $valor === true
            || $valor === 1
            || $valor === '1'
            || $valor === 'true';
    }
}
