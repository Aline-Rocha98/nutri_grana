<?php

use App\Http\Controllers\CartoesCredito\CartaoCreditoController;
use App\Http\Controllers\ContasBancarias\ContaBancariaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::redirect('/dashboard', '/home');

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

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
