<?php

namespace App\View\Components;

use App\Support\Menu\MenuPainel;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BarraLateral extends Component
{
    public array $itensMenu;

    public array $gruposAbertos;

    public array $perfilUsuario;

    public function __construct()
    {
        $this->itensMenu = MenuPainel::prepararItens();
        $this->gruposAbertos = MenuPainel::gruposInicialmenteAbertos();
        $this->perfilUsuario = MenuPainel::obterPerfilUsuario(auth()->user());
    }

    public function render(): View|Closure|string
    {
        return view('components.barra-lateral');
    }
}
