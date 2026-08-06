<?php

namespace App\Services\Categoria;

use App\Data\CategoriaPadrao;
use App\Enum\SimNao;
use App\Enum\TipoCategoria;
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
        $idCategoriaPai = isset($dados['id_categoria_pai'])
            ? (int) $dados['id_categoria_pai']
            : null;

        $pai = null;
        $tipo = TipoCategoria::from($dados['tipo']);
        $nivel = Categoria::NIVEL_PRINCIPAL;
        $cor = CategoriaPadrao::COR_PADRAO;

        if ($idCategoriaPai !== null) {
            $pai = $this->resolverCategoriaPrincipal($idCategoriaPai, $idUsuario);
            $tipo = $pai->tipo;
            $nivel = Categoria::NIVEL_SUBCATEGORIA;
            $cor = $pai->cor;
        }

        $this->garantirNomeUnico($idUsuario, $dados['nome'], $idCategoriaPai);

        return $this->categoriaRepository->criar([
            'id_usuario' => $idUsuario,
            'id_categoria_pai' => $idCategoriaPai,
            'nivel' => $nivel,
            'padrao' => SimNao::Nao,
            'nome' => $dados['nome'],
            'tipo' => $tipo,
            'icone' => $dados['icone'],
            'cor' => $cor,
            'arquivada' => SimNao::Nao,
        ]);
    }

    public function atualizar(Categoria $categoria, int $idUsuario, array $dados): Categoria
    {
        $idCategoriaPai = $categoria->id_categoria_pai !== null ? (int) $categoria->id_categoria_pai : null;
        $this->garantirPropriedade($categoria, $idUsuario);
        $this->garantirNomeUnico(
            $idUsuario,
            $dados['nome'],
            $idCategoriaPai,
            $categoria->id_categoria,
        );

        $payload = [
            'nome' => $dados['nome'],
            'icone' => $dados['icone'],
        ];

        if ($categoria->ehPrincipal()) {
            $tipo = TipoCategoria::from($dados['tipo']);
            $payload['tipo'] = $tipo;

            $atualizada = $this->categoriaRepository->atualizar($categoria, $payload);

            $this->categoriaRepository->atualizarSubcategorias($atualizada, [
                'tipo' => $tipo->value,
            ]);

            return $atualizada->load('subcategorias');
        }

        return $this->categoriaRepository->atualizar($categoria, $payload);
    }

    public function arquivar(Categoria $categoria, int $idUsuario, SimNao $arquivada = SimNao::Sim): Categoria
    {
        $this->garantirPropriedade($categoria, $idUsuario);

        $atualizada = $this->categoriaRepository->atualizar($categoria, [
            'arquivada' => $arquivada,
        ]);

        if ($atualizada->ehPrincipal()) {
            $this->categoriaRepository->atualizarSubcategorias($atualizada, [
                'arquivada' => $arquivada->value,
            ]);
        }

        return $atualizada->load('subcategorias');
    }

    public function excluir(Categoria $categoria, int $idUsuario): void
    {
        $this->garantirPropriedade($categoria, $idUsuario);

        if ($this->categoriaRepository->temLancamentosNaArvore($categoria)) {
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
                'id_categoria_pai' => null,
                'nivel' => Categoria::NIVEL_PRINCIPAL,
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

    private function resolverCategoriaPrincipal(int $idCategoriaPai, int $idUsuario): Categoria
    {
        $pai = $this->categoriaRepository->encontrarDoUsuario($idCategoriaPai, $idUsuario);

        if ($pai === null) {
            throw ValidationException::withMessages([
                'id_categoria_pai' => 'A categoria principal informada é inválida.',
            ]);
        }

        if (!$pai->ehPrincipal()) {
            throw ValidationException::withMessages([
                'id_categoria_pai' => 'Só é possível criar subcategorias sob uma categoria principal.',
            ]);
        }

        if ($pai->arquivada === SimNao::Sim) {
            throw ValidationException::withMessages([
                'id_categoria_pai' => 'Não é possível criar subcategorias em uma categoria arquivada.',
            ]);
        }

        return $pai;
    }

    private function garantirNomeUnico(int $idUsuario, string $nome, ?int $idCategoriaPai, ?int $ignorarId = null): void 
    {
        if ($this->categoriaRepository->nomeExiste($idUsuario, $nome, $idCategoriaPai, $ignorarId)) {
            $mensagem = $idCategoriaPai === null
                ? 'Já existe uma categoria principal com este nome.'
                : 'Já existe uma subcategoria com este nome nesta categoria.';

            throw ValidationException::withMessages([
                'nome' => $mensagem,
            ]);
        }
    }

    private function garantirPropriedade(Categoria $categoria, int $idUsuario): void
    {
        if ((int) $categoria->id_usuario !== $idUsuario) {
            throw new AuthorizationException('Esta categoria não pertence ao usuário autenticado.');
        }
    }
}
