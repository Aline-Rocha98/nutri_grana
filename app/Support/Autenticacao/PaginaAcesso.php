<?php

namespace App\Support\Autenticacao;

use App\Enum\MotivosControleFinanceiro;
use Inertia\Inertia;
use Inertia\Response;

final class PaginaAcesso
{
    public const MODO_LOGIN = 'login';

    public const MODO_CADASTRO = 'cadastro';

    public static function renderizarPaginaAcesso(string $modoInicial): Response
    {
        return Inertia::render('Auth/Acesso', [
            'modoInicial' => $modoInicial,
            'status' => session('status'),
            'motivos' => MotivosControleFinanceiro::opcoesParaSelect(),
        ]);
    }
}
