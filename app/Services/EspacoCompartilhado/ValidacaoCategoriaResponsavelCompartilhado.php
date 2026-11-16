<?php

namespace App\Services\EspacoCompartilhado;

use App\Enum\TipoCategoria;
use App\Models\Categoria\Categoria;
use Illuminate\Validation\ValidationException;

class ValidacaoCategoriaResponsavelCompartilhado
{
    public function garantirCategoriaPertenceAoResponsavel(int $idUsuarioResponsavel, mixed $idCategoria): int
    {
        if ($idCategoria === null || $idCategoria === '') {
            throw ValidationException::withMessages([
                'id_categoria' => 'Selecione uma categoria do responsável.',
            ]);
        }

        $categoria = Categoria::query()
            ->where('id_categoria', (int) $idCategoria)
            ->where('id_usuario', $idUsuarioResponsavel)
            ->first();

        if (! $categoria) {
            throw ValidationException::withMessages([
                'id_categoria' => 'A categoria precisa pertencer ao responsável selecionado.',
            ]);
        }

        if ($categoria->tipo !== TipoCategoria::Saida) {
            throw ValidationException::withMessages([
                'id_categoria' => 'Use uma categoria de saída do responsável.',
            ]);
        }

        return (int) $categoria->id_categoria;
    }

    public function categoriaPertenceAoUsuario(?int $idCategoria, int $idUsuario): bool
    {
        if ($idCategoria === null) {
            return false;
        }

        return Categoria::query()
            ->where('id_categoria', $idCategoria)
            ->where('id_usuario', $idUsuario)
            ->exists();
    }
}
