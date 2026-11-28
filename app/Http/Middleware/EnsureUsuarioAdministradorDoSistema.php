<?php

namespace App\Http\Middleware;

use App\Models\Usuario\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsuarioAdministradorDoSistema
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario || ! $usuario->ehAdministradorDoSistema()) {
            abort(Response::HTTP_FORBIDDEN, 'Acesso restrito ao administrador do sistema.');
        }

        return $next($request);
    }
}
