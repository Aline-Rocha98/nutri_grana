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
                'usuarioPerfil' => route('usuario.perfil'),
                'usuarioAtualizar' => route('usuario.atualizar'),
                'usuarioExcluir' => route('usuario.excluir'),
                'usuarioSenhaEnviarCodigo' => route('usuario.senha.enviar-codigo'),
                'usuarioSenhaConfirmar' => route('usuario.senha.confirmar'),
                'contasBancariasIndex' => route('contas-bancarias.index'),
                'contasBancariasCriar' => route('contas-bancarias.criar'),
                'cartoesCreditoIndex' => route('cartoes-credito.index'),
                'cartoesCreditoCriar' => route('cartoes-credito.criar'),
                'categoriasIndex' => route('categorias.index'),
                'categoriasCriar' => route('categorias.criar'),
                'lancamentosIndex' => route('lancamentos.index'),
                'lancamentosCriar' => route('lancamentos.criar'),
                'objetivosIndex' => route('objetivos.index'),
                'objetivosCriar' => route('objetivos.criar'),
                'orcamentosIndex' => route('orcamentos.index'),
                'orcamentosCriar' => route('orcamentos.criar'),
                'orcamentosServicoCriar' => route('orcamentos.servico.criar'),
                'orcamentosServicoSimular' => route('orcamentos.servico.simular'),
                'rendasIndex' => route('rendas.index'),
                'rendasCriar' => route('rendas.criar'),
            ],
        ];
    }
}
