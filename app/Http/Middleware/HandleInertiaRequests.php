<?php

namespace App\Http\Middleware;

use App\Support\Menu\MenuPainel;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $usuario = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'usuario' => $usuario ? [
                    'id' => $usuario->id_usuario,
                    'nome' => $usuario->nome,
                    'email' => $usuario->email,
                ] : null,
            ],
            'flash' => [
                'sucesso' => fn () => $request->session()->get('sucesso'),
                'erro' => fn () => $request->session()->get('erro'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'menu' => $usuario ? [
                'itens' => MenuPainel::prepararItens(),
                'gruposAbertos' => MenuPainel::gruposInicialmenteAbertos(),
                'perfil' => MenuPainel::obterPerfilUsuario($usuario),
                'urlLogout' => route('logout'),
            ] : null,
            'rotas' => [
                'home' => route('home'),
                'login' => route('login'),
                'register' => route('register'),
                'passwordRequest' => route('password.request'),
                'passwordEmail' => route('password.email'),
                'passwordStore' => route('password.store'),
                'passwordUpdate' => route('password.update'),
                'passwordConfirm' => route('password.confirm'),
                'verificationSend' => route('verification.send'),
                'profileEdit' => route('profile.edit'),
                'profileUpdate' => route('profile.update'),
                'profileDestroy' => route('profile.destroy'),
                'contasBancariasIndex' => route('contas-bancarias.index'),
                'contasBancariasCriar' => route('contas-bancarias.criar'),
                'cartoesCreditoIndex' => route('cartoes-credito.index'),
                'cartoesCreditoCriar' => route('cartoes-credito.criar'),
                'categoriasIndex' => route('categorias.index'),
                'categoriasCriar' => route('categorias.criar'),
            ],
        ];
    }
}
