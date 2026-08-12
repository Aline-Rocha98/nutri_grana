<?php

namespace App\Http\Controllers;

use App\Http\Resources\Objetivo\ObjetivoResource;
use App\Services\Objetivo\ObjetivoService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly ObjetivoService $objetivoService,
    ) {}

    public function index(): Response
    {
        $usuario = Auth::user();
        $objetivosDashboard = $this->objetivoService->listarParaDashboard((int) $usuario->id_usuario);

        return Inertia::render('Home', [
            'usuario' => [
                'nome' => $usuario->nome,
            ],
            'dataHoje' => now()->format('d/m/Y'),
            'objetivosDashboard' => ObjetivoResource::collection($objetivosDashboard)->resolve(),
        ]);
    }
}
