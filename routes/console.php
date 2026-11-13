<?php

use App\Repositories\Alocacao\AlocacaoRepository;
use App\Services\Alocacao\ConsolidadorSaldoAlocacao;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('alocacoes:reconsolidar-saldos', function (
    AlocacaoRepository $alocacaoRepository,
    ConsolidadorSaldoAlocacao $consolidadorSaldoAlocacao,
) {
    $idsAlocacoes = $alocacaoRepository->listarIdsDeTodasAlocacoes();

    $consolidadorSaldoAlocacao->recalcularSaldosConsolidadosDasAlocacoes($idsAlocacoes->all());

    $this->info("Saldo consolidado recalculado para {$idsAlocacoes->count()} alocação(ões).");
})->purpose('Recalcula o saldo consolidado das alocações a partir do histórico de movimentações');
