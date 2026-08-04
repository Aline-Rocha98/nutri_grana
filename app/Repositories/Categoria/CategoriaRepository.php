<?php

namespace App\Repositories\Categoria;

use App\Models\Categoria\Categoria;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoriaRepository
{
    public function listarPorUsuario(int $idUsuario): Collection
    {
        $categorias = Categoria::query()
            ->where('id_usuario', $idUsuario)
            ->orderBy('arquivada')
            ->orderBy('tipo')
            ->orderBy('nome')
            ->get();

        if ($categorias->isEmpty()) {
            return $categorias;
        }

        return $categorias;
    }

    public function usuarioTemCategorias(int $idUsuario): bool
    {
        return Categoria::query()
            ->where('id_usuario', $idUsuario)
            ->exists();
    }

    public function criar(array $dados): Categoria
    {
        return Categoria::query()->create($dados);
    }

    /**
     * @param  list<array<string, mixed>>  $linhas
     */
    public function criarEmMassa(array $linhas): void
    {
        if ($linhas === []) {
            return;
        }

        Categoria::query()->insert($linhas);
    }

    public function atualizar(Categoria $categoria, array $dados): Categoria
    {
        $categoria->update($dados);

        return $categoria->refresh();
    }

    public function excluir(Categoria $categoria): void
    {
        $categoria->delete();
    }

    public function temLancamentos(Categoria $categoria): bool
    {
        return false;
    }
}
