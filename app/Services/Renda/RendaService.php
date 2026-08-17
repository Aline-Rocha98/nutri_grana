<?php

namespace App\Services\Renda;

use App\Enum\FrequenciaRecorrencia;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Renda\Renda;
use App\Repositories\Renda\RendaRepository;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RendaService
{
    public function __construct(
        private readonly RendaRepository $rendaRepository,
        private readonly RendaGeracaoService $rendaGeracaoService,
    ) {}

    public function listarDoUsuario(int $idUsuario): Collection
    {
        return $this->rendaRepository->listarPorUsuario($idUsuario);
    }

    public function criar(int $idUsuario, array $dados): Renda
    {
        $this->garantirContaDoUsuario($idUsuario, (int) $dados['id_conta_bancaria']);

        $frequencia = FrequenciaRecorrencia::from($dados['frequencia']);
        $diaEsperado = (int) $dados['dia_esperado'];
        $dataInicio = $this->calcularDataInicio($diaEsperado);

        $renda = $this->rendaRepository->criar([
            'id_usuario' => $idUsuario,
            'descricao' => $dados['descricao'],
            'valor_esperado' => $dados['valor_esperado'],
            'id_conta_bancaria' => $dados['id_conta_bancaria'],
            'frequencia' => $frequencia,
            'dia_esperado' => $diaEsperado,
            'data_inicio' => $dataInicio->toDateString(),
            'observacao' => $dados['observacao'] ?? null,
        ]);

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

        $frequencia = FrequenciaRecorrencia::from($dados['frequencia']);
        $diaEsperado = (int) $dados['dia_esperado'];
        $dataInicio = $this->recalcularDataInicio($renda, $diaEsperado);

        $atualizada = $this->rendaRepository->atualizar($renda, [
            'descricao' => $dados['descricao'],
            'valor_esperado' => $dados['valor_esperado'],
            'id_conta_bancaria' => $dados['id_conta_bancaria'],
            'frequencia' => $frequencia,
            'dia_esperado' => $diaEsperado,
            'data_inicio' => $dataInicio->toDateString(),
            'observacao' => $dados['observacao'] ?? null,
        ]);

        $this->rendaGeracaoService->atualizarPrevistosFuturos($atualizada);

        $hoje = Carbon::today();
        $this->rendaGeracaoService->gerarOcorrenciasDoMes(
            $atualizada,
            (int) $hoje->year,
            (int) $hoje->month
        );

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
