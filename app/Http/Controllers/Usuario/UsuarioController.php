<?php

namespace App\Http\Controllers\Usuario;

use App\Enum\MotivosControleFinanceiro;
use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\AtualizarPerfilRequest;
use App\Http\Requests\Usuario\ConfirmarAlteracaoSenhaRequest;
use App\Http\Requests\Usuario\ExcluirContaRequest;
use App\Http\Requests\Usuario\SolicitarCodigoAlteracaoSenhaRequest;
use App\Http\Resources\Usuario\UsuarioResource;
use App\Services\Usuario\UsuarioService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly UsuarioService $usuarioService,
    ) {}

    public function perfil(Request $request): Response
    {
        $usuario = $request->user();

        $this->authorize('view', $usuario);

        return Inertia::render('Usuario/Perfil', [
            'usuario' => (new UsuarioResource($usuario))->resolve(),
            'motivos' => MotivosControleFinanceiro::opcoesParaSelect(),
        ]);
    }

    public function atualizar(AtualizarPerfilRequest $request): RedirectResponse
    {
        $usuario = $request->user();

        $dados = $request->safe()->only([
            'nome',
            'email',
            'data_nascimento',
            'motivo_controle_financeiro',
        ]);

        $this->usuarioService->atualizarPerfil(
            $usuario,
            $dados,
            $request->file('foto'),
        );

        return Redirect::route('usuario.perfil')->with('sucesso', 'Perfil atualizado com sucesso.');
    }

    public function solicitarCodigoSenha(SolicitarCodigoAlteracaoSenhaRequest $request): RedirectResponse
    {
        $this->usuarioService->solicitarCodigoAlteracaoSenha($request->user());

        return back()->with('sucesso', 'Enviamos um código de confirmação para o seu e-mail.');
    }

    public function confirmarAlteracaoSenha(ConfirmarAlteracaoSenhaRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->usuarioService->confirmarAlteracaoSenha(
            $request->user(),
            $validated['codigo'],
            $validated['password'],
        );

        return back()->with('sucesso', 'Senha alterada com sucesso.');
    }

    public function excluir(ExcluirContaRequest $request): RedirectResponse
    {
        $usuario = $request->user();

        Auth::logout();

        DB::transaction(function () use ($usuario) {
            $this->usuarioService->excluirConta($usuario);
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
