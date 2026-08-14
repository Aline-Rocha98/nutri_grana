<?php

namespace App\Services\Orcamento;

use App\Models\Orcamento\OrcamentoServico;
use App\Repositories\Orcamento\OrcamentoServicoRepository;
use App\Services\Financeiro\ProjetorFluxoCaixa;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrcamentoServicoService
{
    public function __construct(
        private readonly OrcamentoServicoRepository $orcamentoServicoRepository,
        private readonly ProjetorFluxoCaixa $projetorFluxoCaixa,
        private readonly CalculadoraViabilidadeOrcamentoServico $calculadoraViabilidade,
    ) {}

    public function listarDoUsuario(int $idUsuario, ?Carbon $referencia = null): Collection
    {
        return $this->orcamentoServicoRepository
            ->listarPorUsuario($idUsuario)
            ->map(fn (OrcamentoServico $orcamento) => $this->anexarResumo($orcamento, $idUsuario, $referencia));
    }

    public function criar(int $idUsuario, array $dados): OrcamentoServico
    {
        $this->validarDatas($dados);

        $orcamento = $this->orcamentoServicoRepository->criar([
            'id_usuario' => $idUsuario,
            'descricao' => $dados['descricao'],
            'valor' => $dados['valor'],
            'data_orcamento' => $dados['data_orcamento'],
            'data_validade' => $dados['data_validade'],
            'observacao' => $dados['observacao'] ?? null,
        ]);

        return $this->anexarResumo($orcamento, $idUsuario);
    }

    public function atualizar(OrcamentoServico $orcamento, int $idUsuario, array $dados): OrcamentoServico
    {
        $this->garantirPropriedade($orcamento, $idUsuario);
        $this->validarDatas($dados);

        $atualizado = $this->orcamentoServicoRepository->atualizar($orcamento, [
            'descricao' => $dados['descricao'],
            'valor' => $dados['valor'],
            'data_orcamento' => $dados['data_orcamento'],
            'data_validade' => $dados['data_validade'],
            'observacao' => $dados['observacao'] ?? null,
        ]);

        return $this->anexarResumo($atualizado, $idUsuario);
    }

    public function excluir(OrcamentoServico $orcamento, int $idUsuario): void
    {
        $this->garantirPropriedade($orcamento, $idUsuario);
        $this->orcamentoServicoRepository->excluir($orcamento);
    }
    
    public function simular(int $idUsuario, array $dados, ?Carbon $referencia = null): array
    {
        $this->validarDatas($dados);

        $valor = (float) $dados['valor'];
        $dataValidade = Carbon::parse($dados['data_validade'])->startOfDay();
        $referencia = ($referencia ?? Carbon::today())->copy()->startOfDay();

        return $this->montarViabilidade($idUsuario, $valor, $dataValidade, $referencia);
    }

    public function anexarResumo(OrcamentoServico $orcamento, int $idUsuario, ?Carbon $referencia = null): OrcamentoServico 
    {
        $referencia = ($referencia ?? Carbon::today())->copy()->startOfDay();
        $dataValidade = $orcamento->data_validade instanceof Carbon
            ? $orcamento->data_validade->copy()->startOfDay()
            : Carbon::parse($orcamento->data_validade)->startOfDay();

        $resumo = $this->montarViabilidade(
            $idUsuario,
            (float) $orcamento->valor,
            $dataValidade,
            $referencia,
        );

        foreach ($resumo as $chave => $valor) {
            $orcamento->setAttribute($chave, $valor);
        }

        return $orcamento;
    }

    public function garantirPropriedade(OrcamentoServico $orcamento, int $idUsuario): void
    {
        if ((int) $orcamento->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Este orçamento não pertence ao usuário autenticado.');
        }
    }

    private function montarViabilidade(int $idUsuario, float $valor, Carbon $dataValidade, Carbon $referencia): array 
    {
        $projecaoSem = $this->projetorFluxoCaixa->projetar(
            $idUsuario,
            $dataValidade,
            null,
            null,
            $referencia,
        );

        $projecaoCom = $this->projetorFluxoCaixa->projetar(
            $idUsuario,
            $dataValidade,
            $valor,
            $dataValidade,
            $referencia,
        );

        return $this->calculadoraViabilidade->montarResumo(
            $valor,
            $projecaoSem,
            $projecaoCom,
            $dataValidade,
            $referencia,
        );
    }

    private function validarDatas(array $dados): void
    {
        $dataOrcamento = Carbon::parse($dados['data_orcamento'])->startOfDay();
        $dataValidade = Carbon::parse($dados['data_validade'])->startOfDay();

        if ($dataValidade->lt($dataOrcamento)) {
            throw ValidationException::withMessages([
                'data_validade' => 'A data de validade deve ser igual ou posterior à data do orçamento.',
            ]);
        }
    }
}
