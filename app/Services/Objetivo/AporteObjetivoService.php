<?php

namespace App\Services\Objetivo;

use App\Enum\FormaPagamento;
use App\Enum\SimNao;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoAporteObjetivo;
use App\Enum\TipoLancamento;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Objetivo\AporteObjetivo;
use App\Models\Objetivo\Objetivo;
use App\Repositories\ContaBancaria\ContaBancariaRepository;
use App\Repositories\Lancamento\LancamentoRepository;
use App\Repositories\Objetivo\AporteObjetivoRepository;
use App\Support\Dashboard\DashboardCache;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AporteObjetivoService
{
    public function __construct(
        private readonly AporteObjetivoRepository $aporteObjetivoRepository,
        private readonly ObjetivoService $objetivoService,
        private readonly ContaBancariaRepository $contaBancariaRepository,
        private readonly LancamentoRepository $lancamentoRepository,
    ) {}

    public function listarDoObjetivo(Objetivo $objetivo, int $idUsuario): Collection
    {
        $this->objetivoService->garantirPropriedade($objetivo, $idUsuario);

        return $this->aporteObjetivoRepository->listarPorObjetivo((int) $objetivo->id_objetivo);
    }

    public function registrar(Objetivo $objetivo, int $idUsuario, array $dados): AporteObjetivo
    {
        $this->objetivoService->garantirPropriedade($objetivo, $idUsuario);

        $tipo = TipoAporteObjetivo::from($dados['tipo']);
        $valor = round((float) $dados['valor'], 2);

        if ($valor <= 0) {
            throw ValidationException::withMessages([
                'valor' => 'O valor do aporte deve ser maior que zero.',
            ]);
        }

        $aporte = match ($tipo) {
            TipoAporteObjetivo::Manual => $this->registrarAporteManual($objetivo, $idUsuario, $dados, $valor),
            TipoAporteObjetivo::ContaBancaria => $this->registrarAporteComConta($objetivo, $idUsuario, $dados, $valor),
        };

        DashboardCache::invalidar($idUsuario);

        return $aporte;
    }

    private function registrarAporteManual(Objetivo $objetivo, int $idUsuario, array $dados, float $valor): AporteObjetivo 
    {
        return $this->aporteObjetivoRepository->criar([
            'id_objetivo' => $objetivo->id_objetivo,
            'id_usuario' => $idUsuario,
            'tipo' => TipoAporteObjetivo::Manual,
            'valor' => $valor,
            'data_aporte' => $dados['data_aporte'],
            'id_conta_bancaria' => null,
            'id_lancamento' => null,
            'observacao' => $dados['observacao'] ?? null,
        ]);
    }

    private function registrarAporteComConta(Objetivo $objetivo, int $idUsuario, array $dados, float $valor): AporteObjetivo 
    {
        $idContaBancaria = (int) ($dados['id_conta_bancaria'] ?? 0);

        $conta = $this->contaBancariaRepository->buscarParaUsuario($idContaBancaria, $idUsuario);

        if (!$conta) {
            throw ValidationException::withMessages([
                'id_conta_bancaria' => 'Conta bancária inválida ou não pertence ao usuário.',
            ]);
        }

        if ($conta->arquivada === SimNao::Sim) {
            throw ValidationException::withMessages([
                'id_conta_bancaria' => 'Não é possível retirar valor de uma conta arquivada.',
            ]);
        }

        $saldoAtual = $this->obterSaldoAtualDaConta($conta);

        if ($saldoAtual < $valor) {
            throw ValidationException::withMessages([
                'valor' => 'Saldo insuficiente na conta bancária selecionada.',
            ]);
        }

        $lancamento = $this->lancamentoRepository->criar([
            'id_usuario' => $idUsuario,
            'descricao' => 'Aporte objetivo: '.$objetivo->descricao,
            'valor' => $valor,
            'data_vencimento' => $dados['data_aporte'],
            'data_pagamento' => $dados['data_aporte'],
            'tipo' => TipoLancamento::Despesa,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'id_cartao_credito' => null,
            'id_fatura_cartao' => null,
            'situacao' => SituacaoLancamento::Pago,
            'id_categoria' => null,
            'observacao' => $dados['observacao'] ?? null,
            'eh_recorrencia' => SimNao::Nao,
        ]);

        return $this->aporteObjetivoRepository->criar([
            'id_objetivo' => $objetivo->id_objetivo,
            'id_usuario' => $idUsuario,
            'tipo' => TipoAporteObjetivo::ContaBancaria,
            'valor' => $valor,
            'data_aporte' => $dados['data_aporte'],
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'id_lancamento' => $lancamento->id_lancamento,
            'observacao' => $dados['observacao'] ?? null,
        ]);
    }

    private function obterSaldoAtualDaConta(ContaBancaria $conta): float
    {
        $movimentado = $this->contaBancariaRepository->saldoMovimentado((int) $conta->id_conta_bancaria);

        return round((float) $conta->saldo_inicial + $movimentado, 2);
    }
}
