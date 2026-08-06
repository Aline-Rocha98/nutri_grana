<?php

namespace App\Repositories\Categoria;

use App\Models\Categoria\Categoria;
use Illuminate\Support\Collection;

class CategoriaRepository
{
    public function listarPorUsuario(int $idUsuario): Collection
    {
        return Categoria::query()
            ->where('id_usuario', $idUsuario)
            ->principais()
            ->with(['subcategorias'])
            ->orderBy('arquivada')
            ->orderBy('tipo')
            ->orderBy('nome')
            ->get();
    }

    public function encontrarDoUsuario(int $idCategoria, int $idUsuario): ?Categoria
    {
        return Categoria::query()
            ->where('id_categoria', $idCategoria)
            ->where('id_usuario', $idUsuario)
            ->first();
    }

    public function usuarioTemCategorias(int $idUsuario): bool
    {
        return Categoria::query()
            ->where('id_usuario', $idUsuario)
            ->exists();
    }

    public function nomeExiste(int $idUsuario, string $nome, ?int $idCategoriaPai, ?int $ignorarId = null): bool 
    {
        $query = Categoria::query()
            ->where('id_usuario', $idUsuario)
            ->where('nome', $nome);

        if ($idCategoriaPai === null) {
            $query->whereNull('id_categoria_pai');
        } else {
            $query->where('id_categoria_pai', $idCategoriaPai);
        }

        if ($ignorarId !== null) {
            $query->where('id_categoria', '!=', $ignorarId);
        }

        return $query->exists();
    }

    public function criar(array $dados): Categoria
    {
        return Categoria::query()->create($dados);
    }

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

    public function atualizarSubcategorias(Categoria $categoria, array $dados): void
    {
        Categoria::query()
            ->where('id_categoria_pai', $categoria->id_categoria)
            ->update($dados);
    }

    public function excluir(Categoria $categoria): void
    {
        $categoria->delete();
    }

    public function temLancamentos(Categoria $categoria): bool
    {
        return false;
    }

    public function temLancamentosNaArvore(Categoria $categoria): bool
    {
        if ($this->temLancamentos($categoria)) {
            return true;
        }

        if ($categoria->ehPrincipal()) {
            $categoria->loadMissing('subcategorias');

            foreach ($categoria->subcategorias as $subcategoria) {
                if ($this->temLancamentos($subcategoria)) {
                    return true;
                }
            }
        }

        return false;
    }
}
