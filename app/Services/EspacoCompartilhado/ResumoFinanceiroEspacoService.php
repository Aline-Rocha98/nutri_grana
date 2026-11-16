<?php

namespace App\Services\EspacoCompartilhado;

use App\Enum\StatusLancamentoCompartilhado;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\MembroEspacoCompartilhado;
use App\Repositories\EspacoCompartilhado\LancamentoCompartilhadoRepository;
use App\Support\Data\IniciaisNome;
use App\Support\Data\ValorMonetario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ResumoFinanceiroEspacoService
{
    private const CHAVE_SEM_RESPONSAVEL_ATIVO = 'sem_responsavel_ativo';

    public function __construct(
        private readonly LancamentoCompartilhadoRepository $lancamentoCompartilhadoRepository,
    ) {}

    public function montarResumoDoMes(
        EspacoCompartilhado $espaco,
        Collection $membrosAtivos,
        int $idUsuarioAtual,
        int $ano,
        int $mes,
    ): array {
        $linhas = $this->lancamentoCompartilhadoRepository->somarTotaisDoMesPorResponsavelEStatus(
            (int) $espaco->id_espaco_compartilhado,
            $ano,
            $mes,
            Carbon::today()
        );

        $idsMembrosAtivos = $membrosAtivos->pluck('id_usuario')->map(fn ($id) => (int) $id)->all();
        $totais = $this->criarAcumuladorDeTotais();
        $totaisPorResponsavel = [];

        foreach ($linhas as $linha) {
            $centavosTotal = ValorMonetario::converterParaCentavos($linha->total);
            $quantidade = (int) $linha->quantidade;
            $idResponsavel = $linha->id_usuario_responsavel !== null ? (int) $linha->id_usuario_responsavel : null;
            $chaveResponsavel = $idResponsavel !== null && in_array($idResponsavel, $idsMembrosAtivos, true)
                ? $idResponsavel
                : self::CHAVE_SEM_RESPONSAVEL_ATIVO;

            $totaisPorResponsavel[$chaveResponsavel] ??= ['pago' => 0, 'pendente' => 0];
            $totais['total'] += $centavosTotal;
            $totais['quantidade_total'] += $quantidade;

            if ($linha->status === StatusLancamentoCompartilhado::Pago->value) {
                $totais['pago'] += $centavosTotal;
                $totais['quantidade_pagos'] += $quantidade;
                $totaisPorResponsavel[$chaveResponsavel]['pago'] += $centavosTotal;

                continue;
            }

            $centavosVencidos = ValorMonetario::converterParaCentavos($linha->total_vencido);
            $quantidadeVencida = (int) $linha->quantidade_vencida;

            $totais['vencido'] += $centavosVencidos;
            $totais['quantidade_vencidos'] += $quantidadeVencida;
            $totais['a_vencer'] += $centavosTotal - $centavosVencidos;
            $totais['quantidade_a_vencer'] += $quantidade - $quantidadeVencida;
            $totaisPorResponsavel[$chaveResponsavel]['pendente'] += $centavosTotal;
        }

        return [
            'total' => ValorMonetario::formatarCentavosEmReais($totais['total']),
            'quantidade_total' => $totais['quantidade_total'],
            'pago' => ValorMonetario::formatarCentavosEmReais($totais['pago']),
            'quantidade_pagos' => $totais['quantidade_pagos'],
            'a_vencer' => ValorMonetario::formatarCentavosEmReais($totais['a_vencer']),
            'quantidade_a_vencer' => $totais['quantidade_a_vencer'],
            'vencido' => ValorMonetario::formatarCentavosEmReais($totais['vencido']),
            'quantidade_vencidos' => $totais['quantidade_vencidos'],
            'percentual_pago' => $totais['total'] > 0 ? round(($totais['pago'] * 100) / $totais['total'], 1) : 0.0,
            'por_membro' => $this->montarResumoPorMembro($membrosAtivos, $totaisPorResponsavel, $idUsuarioAtual),
        ];
    }

    private function montarResumoPorMembro(Collection $membrosAtivos, array $totaisPorResponsavel, int $idUsuarioAtual): array
    {
        $resumo = $membrosAtivos
            ->map(function (MembroEspacoCompartilhado $membro) use ($totaisPorResponsavel, $idUsuarioAtual) {
                $idUsuario = (int) $membro->id_usuario;
                $totaisDoMembro = $totaisPorResponsavel[$idUsuario] ?? ['pago' => 0, 'pendente' => 0];

                return $this->formatarResumoDoResponsavel(
                    $membro->usuario?->nome ?? 'Membro',
                    $membro->usuario?->foto_url,
                    $totaisDoMembro,
                    [
                        'id_usuario' => $idUsuario,
                        'papel' => $membro->papel?->value,
                        'papel_rotulo' => $membro->papel?->rotulo(),
                        'eh_usuario_atual' => $idUsuario === $idUsuarioAtual,
                    ]
                );
            })
            ->values()
            ->all();

        $totaisSemResponsavel = $totaisPorResponsavel[self::CHAVE_SEM_RESPONSAVEL_ATIVO] ?? null;

        if ($totaisSemResponsavel !== null) {
            $resumo[] = $this->formatarResumoDoResponsavel(
                'Ex-membros ou sem responsável',
                null,
                $totaisSemResponsavel,
                ['id_usuario' => null, 'papel' => null, 'papel_rotulo' => null, 'eh_usuario_atual' => false]
            );
        }

        return $resumo;
    }

    private function formatarResumoDoResponsavel(string $nome, ?string $fotoUrl, array $totais, array $dadosAdicionais): array
    {
        return array_merge($dadosAdicionais, [
            'nome' => $nome,
            'iniciais' => IniciaisNome::extrairIniciais($nome, '?'),
            'foto_url' => $fotoUrl,
            'pago' => ValorMonetario::formatarCentavosEmReais($totais['pago']),
            'pendente' => ValorMonetario::formatarCentavosEmReais($totais['pendente']),
            'total' => ValorMonetario::formatarCentavosEmReais($totais['pago'] + $totais['pendente']),
            'percentual_pago' => ($totais['pago'] + $totais['pendente']) > 0
                ? round(($totais['pago'] * 100) / ($totais['pago'] + $totais['pendente']), 1)
                : 0.0,
        ]);
    }

    private function criarAcumuladorDeTotais(): array
    {
        return [
            'total' => 0,
            'quantidade_total' => 0,
            'pago' => 0,
            'quantidade_pagos' => 0,
            'a_vencer' => 0,
            'quantidade_a_vencer' => 0,
            'vencido' => 0,
            'quantidade_vencidos' => 0,
        ];
    }
}
