<?php

namespace App\Http\Controllers\Auth;

use App\Enum\MotivosControleFinanceiro;
use App\Http\Controllers\Controller;
use App\Models\Usuario\Usuario;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
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

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:100',
                Rule::unique(Usuario::class, 'email'),
            ],
            'data_nascimento' => ['required', 'date', 'before:today'],
            'motivo_controle_financeiro' => ['required', Rule::enum(MotivosControleFinanceiro::class)],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.unique' => __('validation.usuario.email.unique'),
        ]);

        $user = Usuario::create([
            'nome' => $request->nome,
            'email' => $request->email,
            'data_nascimento' => $request->data_nascimento,
            'motivo_controle_financeiro' => $request->motivo_controle_financeiro,
            'senha' => $request->password,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('home', absolute: false));
    }
}
