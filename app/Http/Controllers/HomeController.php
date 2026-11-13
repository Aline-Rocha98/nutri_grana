<?php

namespace App\Http\Controllers;

use App\Services\Home\HomeService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly HomeService $homeService,
    ) {}

    public function index(): Response
    {
        $usuario = Auth::user();

        return Inertia::render('Home', [
            'usuario' => [
                'nome' => $usuario->nome,
            ],
            'resumoDoDia' => fn () => $this->homeService->obterResumoDoDia((int) $usuario->id_usuario),
        ]);
    }
}
