<?php

namespace App\Services\Categoria;

use App\Data\CategoriaPadrao;
use App\Enum\SimNao;
use App\Models\Categoria\Categoria;
use App\Repositories\Categoria\CategoriaRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CategoriaService
{
    public function __construct(
        private readonly CategoriaRepository $categoriaRepository,
    ) {}

    public function listarPorUsuario(int $idUsuario): Collection
    {
        $this->garantirPadroesDoUsuario($idUsuario);

        return $this->categoriaRepository->listarPorUsuario($idUsuario);
    }

    public function criar(int $idUsuario, array $dados): Categoria
    {
        return $this->categoriaRepository->criar([
            'id_usuario' => $idUsuario,
            'padrao' => SimNao::Nao,
            'nome' => $dados['nome'],
            'tipo' => $dados['tipo'],
            'icone' => $dados['icone'],
            'cor' => CategoriaPadrao::COR_PADRAO,
            'arquivada' => SimNao::Nao,
        ]);
    }

    public function atualizar(Categoria $categoria, int $idUsuario, array $dados): Categoria
    {
        $this->garantirPropriedade($categoria, $idUsuario);

        return $this->categoriaRepository->atualizar($categoria, [
            'nome' => $dados['nome'],
            'tipo' => $dados['tipo'],
            'icone' => $dados['icone'],
        ]);
    }

    public function arquivar(Categoria $categoria, int $idUsuario, SimNao $arquivada = SimNao::Sim): Categoria
    {
        $this->garantirPropriedade($categoria, $idUsuario);

        return $this->categoriaRepository->atualizar($categoria, [
            'arquivada' => $arquivada,
        ]);
    }

    public function excluir(Categoria $categoria, int $idUsuario): void
    {
        $this->garantirPropriedade($categoria, $idUsuario);

        if ($this->categoriaRepository->temLancamentos($categoria)) {
            throw ValidationException::withMessages([
                'categoria' => 'Não é possível excluir uma categoria com lançamentos vinculados. Arquive a categoria em vez de excluí-la.',
            ]);
        }

        $this->categoriaRepository->excluir($categoria);
    }

    public function garantirPadroesDoUsuario(int $idUsuario): void
    {
        if ($this->categoriaRepository->usuarioTemCategorias($idUsuario)) {
            return;
        }

        $agora = now();

        $linhas = array_map(
            fn (array $padrao) => [
                'id_usuario' => $idUsuario,
                'padrao' => SimNao::Sim->value,
                'nome' => $padrao['nome'],
                'tipo' => $padrao['tipo'],
                'icone' => $padrao['icone'],
                'cor' => $padrao['cor'],
                'arquivada' => SimNao::Nao->value,
                'created_at' => $agora,
                'updated_at' => $agora,
            ],
            CategoriaPadrao::todas()
        );

        $this->categoriaRepository->criarEmMassa($linhas);
    }

    private function garantirPropriedade(Categoria $categoria, int $idUsuario): void
    {
        if ((int) $categoria->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Esta categoria não pertence ao usuário autenticado.');
        }
    }
}
