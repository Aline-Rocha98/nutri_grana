<?php

namespace App\Services\FaturaCartao;

use App\Enum\FormaPagamento;
use App\Enum\SituacaoFatura;
use App\Enum\SituacaoLancamento;
use App\Enum\TipoLancamento;
use App\Models\CartaoCredito\CartaoCredito;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\FaturaCartao\FaturaCartao;
use App\Models\Lancamento\Lancamento;
use App\Repositories\FaturaCartao\FaturaCartaoRepository;
use App\Repositories\Lancamento\LancamentoRepository;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FaturaCartaoService
{
    public function __construct(
        private readonly FaturaCartaoRepository $faturaCartaoRepository,
        private readonly LancamentoRepository $lancamentoRepository,
    ) {}

    public function buscarOuCriar(CartaoCredito $cartao, Carbon $data): FaturaCartao
    {
        $ano = (int) $data->year;
        $mes = (int) $data->month;

        $existente = $this->faturaCartaoRepository->buscarPorCompetencia(
            (int) $cartao->id_cartao_credito,
            $ano,
            $mes
        );

        if ($existente) {
            return $existente;
        }

        return $this->faturaCartaoRepository->criar([
            'id_usuario' => $cartao->id_usuario,
            'id_cartao_credito' => $cartao->id_cartao_credito,
            'ano' => $ano,
            'mes' => $mes,
            'data_fechamento' => $this->dataComDia($ano, $mes, (int) $cartao->dia_fechamento),
            'data_vencimento' => $this->dataComDia($ano, $mes, (int) $cartao->dia_vencimento),
            'situacao' => SituacaoFatura::Aberta,
        ]);
    }

    public function listarPorCartao(CartaoCredito $cartao, int $idUsuario): Collection
    {
        $this->garantirPropriedade($cartao, $idUsuario);

        return $this->faturaCartaoRepository->listarPorCartao((int) $cartao->id_cartao_credito);
    }

    public function listarAbertasPorUsuario(int $idUsuario): Collection
    {
        return $this->faturaCartaoRepository->listarAbertasPorUsuario($idUsuario);
    }

    public function baixar(FaturaCartao $fatura, int $idUsuario, ContaBancaria $contaBancaria, ?Carbon $dataPagamento = null): FaturaCartao 
    {
        $this->garantirPropriedade($fatura, $idUsuario);

        if ((int) $contaBancaria->id_usuario !== $idUsuario) {
            throw new AuthorizationException('A conta bancária não pertence ao usuário autenticado.');
        }

        if ($fatura->situacao === SituacaoFatura::Paga) {
            throw ValidationException::withMessages([
                'fatura' => 'Esta fatura já está paga.',
            ]);
        }

        $valor = $this->faturaCartaoRepository->valorTotal($fatura);

        if ($valor <= 0) {
            throw ValidationException::withMessages([
                'fatura' => 'Não há lançamentos para pagar nesta fatura.',
            ]);
        }

        $dataPagamento ??= Carbon::today();
        $cartao = $fatura->cartaoCredito;
        $mesNome = $this->nomeMes($fatura->mes);

        $pagamento = $this->lancamentoRepository->criar([
            'id_usuario' => $idUsuario,
            'descricao' => sprintf('Pagamento fatura %s %s/%04d', $cartao?->nome ?? 'cartão', $mesNome, $fatura->ano),
            'valor' => $valor,
            'data_vencimento' => $dataPagamento->toDateString(),
            'data_pagamento' => $dataPagamento->toDateString(),
            'tipo' => TipoLancamento::Despesa,
            'forma_pagamento' => FormaPagamento::ContaBancaria,
            'id_conta_bancaria' => $contaBancaria->id_conta_bancaria,
            'situacao' => SituacaoLancamento::Pago,
            'observacao' => 'Baixa automática da fatura de cartão',
            'eh_recorrencia' => 'N',
        ]);

        $fatura->lancamentos()
            ->where('situacao', SituacaoLancamento::Pendente)
            ->update([
                'situacao' => SituacaoLancamento::Pago,
                'data_pagamento' => $dataPagamento->toDateString(),
            ]);

        return $this->faturaCartaoRepository->atualizar($fatura, [
            'situacao' => SituacaoFatura::Paga,
            'id_conta_bancaria_pagamento' => $contaBancaria->id_conta_bancaria,
            'id_lancamento_pagamento' => $pagamento->id_lancamento,
        ]);
    }

    public function temFaturaAberta(CartaoCredito $cartao): bool
    {
        return $this->faturaCartaoRepository->temFaturaAbertaNoCartao((int) $cartao->id_cartao_credito);
    }

    private function dataComDia(int $ano, int $mes, int $dia): string
    {
        $ultimoDia = (int) Carbon::create($ano, $mes, 1)->endOfMonth()->day;
        $diaAjustado = min(max($dia, 1), $ultimoDia);

        return Carbon::create($ano, $mes, $diaAjustado)->toDateString();
    }

    private function nomeMes(int $mes): string
    {
        $nomes = [
            1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
            5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
        ];

        return $nomes[$mes] ?? (string) $mes;
    }

    private function garantirPropriedade(CartaoCredito|FaturaCartao $modelo, int $idUsuario): void
    {
        if ((int) $modelo->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Registro não pertence ao usuário autenticado.');
        }
    }
}
