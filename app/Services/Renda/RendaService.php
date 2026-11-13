<?php

namespace App\Services\Renda;

use App\Enum\FrequenciaRecorrencia;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Renda\Renda;
use App\Repositories\Renda\RendaRepository;
use App\Services\PlanoFinanceiro\CalculadoraPlanoFinanceiro;
use App\Services\PlanoFinanceiro\PlanoFinanceiroService;
use App\Support\Dashboard\DashboardCache;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RendaService
{
    public function __construct(
        private readonly RendaRepository $rendaRepository,
        private readonly RendaGeracaoService $rendaGeracaoService,
        private readonly PlanoFinanceiroService $planoFinanceiroService,
        private readonly CalculadoraPlanoFinanceiro $calculadoraPlanoFinanceiro,
    ) {}

    public function montarCapacidadeFinanceiraGeral(Collection $rendas): array
    {
        $capacidadesPorRenda = $rendas->map(
            fn (Renda $renda) => $renda->capacidade_financeira
                ?? $this->calcularCapacidadeFinanceiraDaRenda($renda),
        );

        $totalRenda = round($capacidadesPorRenda->sum('renda'), 2);
        $totalDestinado = round($capacidadesPorRenda->sum('total_destinado'), 2);

        return [
            'renda' => $totalRenda,
            'total_destinado' => $totalDestinado,
            'capacidade' => round($totalRenda - $totalDestinado, 2),
            'percentual_destinado' => $totalRenda > 0 ? round(($totalDestinado / $totalRenda) * 100, 1) : 0.0,
        ];
    }

    public function anexarCapacidadeFinanceira(Renda $renda): Renda
    {
        $renda->setAttribute('capacidade_financeira', $this->calcularCapacidadeFinanceiraDaRenda($renda));

        return $renda;
    }

    private function calcularCapacidadeFinanceiraDaRenda(Renda $renda): array
    {
        $itensDoPlano = $renda->relationLoaded('planoFinanceiro') && $renda->planoFinanceiro
            ? $renda->planoFinanceiro->itens
            : collect();

        return $this->calculadoraPlanoFinanceiro->calcularCapacidadeFinanceira(
            (float) $renda->valor_esperado,
            $itensDoPlano,
        );
    }

    public function listarDoUsuario(int $idUsuario): Collection
    {
        return $this->rendaRepository
            ->listarPorUsuario($idUsuario)
            ->each(fn (Renda $renda) => $this->anexarCapacidadeFinanceira($renda));
    }

    public function criar(int $idUsuario, array $dados): Renda
    {
        $this->garantirContaDoUsuario($idUsuario, (int) $dados['id_conta_bancaria']);
        $idPlanoFinanceiro = $this->garantirPlanoAplicavelNaRenda($idUsuario, $dados);

        $frequencia = FrequenciaRecorrencia::from($dados['frequencia']);
        $diaEsperado = (int) $dados['dia_esperado'];
        $dataInicio = $this->calcularDataInicio($diaEsperado);

        $renda = $this->rendaRepository->criar([
            'id_usuario' => $idUsuario,
            'descricao' => $dados['descricao'],
            'valor_esperado' => $dados['valor_esperado'],
            'id_conta_bancaria' => $dados['id_conta_bancaria'],
            'id_plano_financeiro' => $idPlanoFinanceiro,
            'frequencia' => $frequencia,
            'dia_esperado' => $diaEsperado,
            'data_inicio' => $dataInicio->toDateString(),
            'observacao' => $dados['observacao'] ?? null,
        ]);

        if ($idPlanoFinanceiro !== null) {
            DashboardCache::invalidar($idUsuario);
        }

        $hoje = Carbon::today();
        $this->rendaGeracaoService->gerarOcorrenciasDoMes(
            $renda,
            (int) $hoje->year,
            (int) $hoje->month
        );

        return $renda->load('contaBancaria');
    }

    public function atualizar(Renda $renda, int $idUsuario, array $dados): Renda
    {
        $this->garantirPropriedade($renda, $idUsuario);
        $this->garantirContaDoUsuario($idUsuario, (int) $dados['id_conta_bancaria']);
        $idPlanoFinanceiro = $this->garantirPlanoAplicavelNaRenda($idUsuario, $dados);

        $frequencia = FrequenciaRecorrencia::from($dados['frequencia']);
        $diaEsperado = (int) $dados['dia_esperado'];
        $dataInicio = $this->recalcularDataInicio($renda, $diaEsperado);

        $atualizada = $this->rendaRepository->atualizar($renda, [
            'descricao' => $dados['descricao'],
            'valor_esperado' => $dados['valor_esperado'],
            'id_conta_bancaria' => $dados['id_conta_bancaria'],
            'id_plano_financeiro' => $idPlanoFinanceiro,
            'frequencia' => $frequencia,
            'dia_esperado' => $diaEsperado,
            'data_inicio' => $dataInicio->toDateString(),
            'observacao' => $dados['observacao'] ?? null,
        ]);

        $this->rendaGeracaoService->sincronizarPrevistosComRenda($atualizada);

        DashboardCache::invalidar($idUsuario);

        return $atualizada->load('contaBancaria');
    }

    public function excluir(Renda $renda, int $idUsuario): void
    {
        $this->garantirPropriedade($renda, $idUsuario);

        $this->rendaGeracaoService->cancelarPrevistos($renda);
        $this->rendaRepository->excluir($renda);
    }

    public function garantirPropriedade(Renda $renda, int $idUsuario): void
    {
        if ((int) $renda->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Esta renda não pertence ao usuário autenticado.');
        }
    }

    private function garantirPlanoAplicavelNaRenda(int $idUsuario, array $dados): ?int
    {
        if (empty($dados['id_plano_financeiro'])) {
            return null;
        }

        $planoFinanceiro = $this->planoFinanceiroService->buscarPlanoAplicavelNaRenda(
            (int) $dados['id_plano_financeiro'],
            $idUsuario,
            (float) $dados['valor_esperado'],
        );

        return (int) $planoFinanceiro->id_plano_financeiro;
    }

    private function garantirContaDoUsuario(int $idUsuario, int $idContaBancaria): void
    {
        $existe = ContaBancaria::query()
            ->where('id_conta_bancaria', $idContaBancaria)
            ->where('id_usuario', $idUsuario)
            ->exists();

        if (! $existe) {
            throw ValidationException::withMessages([
                'id_conta_bancaria' => 'Conta bancária inválida.',
            ]);
        }
    }

    private function calcularDataInicio(int $diaEsperado): Carbon
    {
        $hoje = Carbon::today();
        $dia = min($diaEsperado, $hoje->daysInMonth);

        return Carbon::create($hoje->year, $hoje->month, $dia)->startOfDay();
    }

    private function recalcularDataInicio(Renda $renda, int $diaEsperado): Carbon
    {
        $base = Carbon::parse($renda->data_inicio);
        $dia = min($diaEsperado, $base->daysInMonth);

        return $base->copy()->day($dia)->startOfDay();
    }
}
