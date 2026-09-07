<?php

namespace App\Http\Controllers\Auth;

use App\Enum\MotivosControleFinanceiro;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAbsoluteSessionTimeout;
use App\Http\Requests\Auth\StoreRegisteredUserRequest;
use App\Models\Usuario\Usuario;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'motivos' => MotivosControleFinanceiro::opcoesParaSelect(),
        ]);
    }

    public function store(StoreRegisteredUserRequest $request): RedirectResponse
    {
        $dados = $request->safe()->only([
            'nome',
            'email',
            'data_nascimento',
            'motivo_controle_financeiro',
        ]);

        $user = new Usuario($dados);
        $user->senha = $request->validated('password');
        $user->save();

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();
        $request->session()->put(
            EnsureAbsoluteSessionTimeout::SESSION_LOGIN_AT,
            now()->getTimestamp()
        );

        return redirect(route('home', absolute: false));
    }
}
