<?php

use App\Http\Controllers\CartaoCredito\CartaoCreditoController;
use App\Http\Controllers\Categoria\CategoriaController;
use App\Http\Controllers\ContaBancaria\ContaBancariaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::redirect('/dashboard', '/home');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password/send-code', [ProfileController::class, 'solicitarCodigoSenha'])
        ->middleware('throttle:5,1')
        ->name('profile.password.send-code');
    Route::put('/profile/password/confirm', [ProfileController::class, 'confirmarAlteracaoSenha'])
        ->middleware('throttle:5,1')
        ->name('profile.password.confirm');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

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
});

require __DIR__.'/auth.php';
