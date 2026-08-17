<?php

use App\Http\Controllers\CartaoCredito\CartaoCreditoController;
use App\Http\Controllers\Categoria\CategoriaController;
use App\Http\Controllers\ContaBancaria\ContaBancariaController;
use App\Http\Controllers\FaturaCartao\FaturaCartaoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Lancamento\LancamentoController;
use App\Http\Controllers\Objetivo\ObjetivoController;
use App\Http\Controllers\Orcamento\OrcamentoController;
use App\Http\Controllers\Orcamento\OrcamentoServicoController;
use App\Http\Controllers\Renda\RendaController;
use App\Http\Controllers\Usuario\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::redirect('/dashboard', '/home');

    // Usuário
    Route::redirect('/profile', '/usuario/perfil');
    Route::get('/usuario/perfil', [UsuarioController::class, 'perfil'])->name('usuario.perfil');
    Route::patch('/usuario', [UsuarioController::class, 'atualizar'])->name('usuario.atualizar');
    Route::post('/usuario/senha/enviar-codigo', [UsuarioController::class, 'solicitarCodigoSenha'])
        ->middleware('throttle:5,1')
        ->name('usuario.senha.enviar-codigo');
    Route::put('/usuario/senha', [UsuarioController::class, 'confirmarAlteracaoSenha'])
        ->middleware('throttle:5,1')
        ->name('usuario.senha.confirmar');
    Route::delete('/usuario', [UsuarioController::class, 'excluir'])->name('usuario.excluir');

    // Contas Bancárias
    Route::get('/contas-bancarias', [ContaBancariaController::class, 'index'])->name('contas-bancarias.index');
    Route::post('/contas-bancarias', [ContaBancariaController::class, 'criarContaBancaria'])->name('contas-bancarias.criar');
    Route::put('/contas-bancarias/{contaBancaria}', [ContaBancariaController::class, 'atualizarContaBancaria'])->name('contas-bancarias.atualizar');
    Route::delete('/contas-bancarias/{contaBancaria}', [ContaBancariaController::class, 'excluirContaBancaria'])->name('contas-bancarias.excluir');

    // Cartões de Crédito
    Route::get('/cartoes-credito', [CartaoCreditoController::class, 'index'])->name('cartoes-credito.index');
    Route::post('/cartoes-credito', [CartaoCreditoController::class, 'criarCartaoCredito'])->name('cartoes-credito.criar');
    Route::put('/cartoes-credito/{cartaoCredito}', [CartaoCreditoController::class, 'atualizarCartaoCredito'])->name('cartoes-credito.atualizar');
    Route::patch('/cartoes-credito/{cartaoCredito}/arquivar', [CartaoCreditoController::class, 'arquivarCartaoCredito'])->name('cartoes-credito.arquivar');
    Route::delete('/cartoes-credito/{cartaoCredito}', [CartaoCreditoController::class, 'excluirCartaoCredito'])->name('cartoes-credito.excluir');

    // Categorias
    Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
    Route::post('/categorias', [CategoriaController::class, 'criarCategoria'])->name('categorias.criar');
    Route::put('/categorias/{categoria}', [CategoriaController::class, 'atualizarCategoria'])->name('categorias.atualizar');
    Route::patch('/categorias/{categoria}/arquivar', [CategoriaController::class, 'arquivarCategoria'])->name('categorias.arquivar');
    Route::delete('/categorias/{categoria}', [CategoriaController::class, 'excluirCategoria'])->name('categorias.excluir');

    // Lançamentos
    Route::get('/lancamentos/{ano?}/{mes?}', [LancamentoController::class, 'index'])
        ->whereNumber('ano')
        ->whereNumber('mes')
        ->name('lancamentos.index');
    Route::post('/lancamentos', [LancamentoController::class, 'criar'])->name('lancamentos.criar');
    Route::put('/lancamentos/{lancamento}', [LancamentoController::class, 'atualizar'])->name('lancamentos.atualizar');
    Route::patch('/lancamentos/{lancamento}/situacao', [LancamentoController::class, 'alterarSituacao'])->name('lancamentos.situacao');
    Route::patch('/lancamentos/{lancamento}/confirmar-receita', [LancamentoController::class, 'confirmarReceita'])
        ->name('lancamentos.confirmar-receita');
    Route::delete('/lancamentos/{lancamento}', [LancamentoController::class, 'excluir'])->name('lancamentos.excluir');

    // Rendas
    Route::get('/rendas', [RendaController::class, 'index'])->name('rendas.index');
    Route::post('/rendas', [RendaController::class, 'criar'])->name('rendas.criar');
    Route::put('/rendas/{renda}', [RendaController::class, 'atualizar'])->name('rendas.atualizar');
    Route::delete('/rendas/{renda}', [RendaController::class, 'excluir'])->name('rendas.excluir');

    // Faturas de cartão
    Route::get('/cartoes-credito/{cartaoCredito}/faturas', [FaturaCartaoController::class, 'indexPorCartao'])
        ->name('faturas-cartao.index');
    Route::get('/faturas-cartao/{faturaCartao}', [FaturaCartaoController::class, 'show'])
        ->name('faturas-cartao.show');
    Route::post('/faturas-cartao/{faturaCartao}/baixar', [FaturaCartaoController::class, 'baixar'])
        ->name('faturas-cartao.baixar');

    // Objetivos
    Route::get('/objetivos', [ObjetivoController::class, 'index'])->name('objetivos.index');
    Route::post('/objetivos', [ObjetivoController::class, 'criar'])->name('objetivos.criar');
    Route::put('/objetivos/{objetivo}', [ObjetivoController::class, 'atualizar'])->name('objetivos.atualizar');
    Route::delete('/objetivos/{objetivo}', [ObjetivoController::class, 'excluir'])->name('objetivos.excluir');
    Route::post('/objetivos/{objetivo}/aportes', [ObjetivoController::class, 'criarAporte'])
        ->name('objetivos.aportes.criar');

    // Orçamentos
    Route::get('/orcamentos/{ano?}/{mes?}', [OrcamentoController::class, 'index'])
        ->whereNumber('ano')
        ->whereNumber('mes')
        ->name('orcamentos.index');
    Route::post('/orcamentos', [OrcamentoController::class, 'criar'])->name('orcamentos.criar');
    Route::put('/orcamentos/{orcamento}', [OrcamentoController::class, 'atualizar'])->name('orcamentos.atualizar');
    Route::delete('/orcamentos/{orcamento}', [OrcamentoController::class, 'excluir'])->name('orcamentos.excluir');

    Route::post('/orcamentos/servico', [OrcamentoServicoController::class, 'criar'])
        ->name('orcamentos.servico.criar');
    Route::post('/orcamentos/servico/simular', [OrcamentoServicoController::class, 'simular'])
        ->name('orcamentos.servico.simular');
    Route::put('/orcamentos/servico/{orcamentoServico}', [OrcamentoServicoController::class, 'atualizar'])
        ->name('orcamentos.servico.atualizar');
    Route::delete('/orcamentos/servico/{orcamentoServico}', [OrcamentoServicoController::class, 'excluir'])
        ->name('orcamentos.servico.excluir');
});

require __DIR__.'/auth.php';
