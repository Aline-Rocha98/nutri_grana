<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $usuario = Auth::user();

        return Inertia::render('Home', [
            'usuario' => [
                'nome' => $usuario->nome,
            ],
            'dataHoje' => now()->format('d/m/Y'),
        ]);
    }
}
