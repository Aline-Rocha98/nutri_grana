<?php

use App\Http\Controllers\CartaoCredito\CartaoCreditoController;
use App\Http\Controllers\Categoria\CategoriaController;
use App\Http\Controllers\ContaBancaria\ContaBancariaController;
use App\Http\Controllers\FaturaCartao\FaturaCartaoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Lancamento\LancamentoController;
use App\Http\Controllers\Objetivo\ObjetivoController;
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
    Route::delete('/lancamentos/{lancamento}', [LancamentoController::class, 'excluir'])->name('lancamentos.excluir');

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
});

require __DIR__.'/auth.php';
