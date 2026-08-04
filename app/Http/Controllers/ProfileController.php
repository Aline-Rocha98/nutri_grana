<?php

namespace App\Http\Controllers;

use App\Enum\MotivosControleFinanceiro;
use App\Http\Requests\Usuario\AtualizarPerfilRequest;
use App\Http\Requests\Usuario\ConfirmarAlteracaoSenhaRequest;
use App\Http\Requests\Usuario\ExcluirContaRequest;
use App\Http\Requests\Usuario\SolicitarCodigoAlteracaoSenhaRequest;
use App\Http\Resources\Usuario\UsuarioResource;
use App\Services\Usuario\PerfilService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PerfilService $perfilService,
    ) {}

    public function edit(Request $request): Response
    {
        $usuario = $request->user();

        $this->authorize('view', $usuario);

        return Inertia::render('Perfil/Editar', [
            'usuario' => (new UsuarioResource($usuario))->resolve(),
            'motivos' => MotivosControleFinanceiro::opcoesParaSelect(),
        ]);
    }

    public function update(AtualizarPerfilRequest $request): RedirectResponse
    {
        $usuario = $request->user();

        $dados = $request->safe()->only([
            'nome',
            'email',
            'data_nascimento',
            'motivo_controle_financeiro',
        ]);

        $this->perfilService->atualizarPerfil(
            $usuario,
            $dados,
            $request->file('foto'),
        );

        return Redirect::route('profile.edit')->with('sucesso', 'Perfil atualizado com sucesso.');
    }

    public function solicitarCodigoSenha(SolicitarCodigoAlteracaoSenhaRequest $request): RedirectResponse
    {
        $this->perfilService->solicitarCodigoAlteracaoSenha($request->user());

        return back()->with('sucesso', 'Enviamos um código de confirmação para o seu e-mail.');
    }

    public function confirmarAlteracaoSenha(ConfirmarAlteracaoSenhaRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->perfilService->confirmarAlteracaoSenha(
            $request->user(),
            $validated['codigo'],
            $validated['password'],
        );

        return back()->with('sucesso', 'Senha alterada com sucesso.');
    }

    public function destroy(ExcluirContaRequest $request): RedirectResponse
    {
        $usuario = $request->user();

        Auth::logout();

        DB::transaction(function () use ($usuario) {
            $this->perfilService->excluirConta($usuario);
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
