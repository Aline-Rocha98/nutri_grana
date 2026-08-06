<?php

namespace Tests\Feature;

use App\Enum\SimNao;
use App\Enum\TipoCategoria;
use App\Models\Categoria\Categoria;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaSubcategoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_criar_subcategoria(): void
    {
        $usuario = Usuario::factory()->create();
        $pai = $this->criarCategoriaPrincipal($usuario, 'Transporte');

        $response = $this
            ->actingAs($usuario)
            ->post('/categorias', [
                'nome' => 'Uber',
                'tipo' => TipoCategoria::Entrada->value,
                'icone' => 'directions_car',
                'id_categoria_pai' => $pai->id_categoria,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/categorias');

        $this->assertDatabaseHas('categorias', [
            'id_usuario' => $usuario->id_usuario,
            'id_categoria_pai' => $pai->id_categoria,
            'nome' => 'Uber',
            'tipo' => TipoCategoria::Saida->value,
            'nivel' => Categoria::NIVEL_SUBCATEGORIA,
        ]);
    }

    public function test_nao_permite_subcategoria_sob_outra_subcategoria(): void
    {
        $usuario = Usuario::factory()->create();
        $pai = $this->criarCategoriaPrincipal($usuario, 'Transporte');
        $sub = $this->criarSubcategoria($usuario, $pai, 'Uber');

        $response = $this
            ->actingAs($usuario)
            ->post('/categorias', [
                'nome' => 'Uber Black',
                'tipo' => TipoCategoria::Saida->value,
                'icone' => 'directions_car',
                'id_categoria_pai' => $sub->id_categoria,
            ]);

        $response->assertSessionHasErrors('id_categoria_pai');

        $this->assertDatabaseMissing('categorias', [
            'nome' => 'Uber Black',
            'id_usuario' => $usuario->id_usuario,
        ]);
    }

    public function test_nome_deve_ser_unico_entre_irmaos(): void
    {
        $usuario = Usuario::factory()->create();
        $pai = $this->criarCategoriaPrincipal($usuario, 'Transporte');
        $this->criarSubcategoria($usuario, $pai, 'Uber');

        $response = $this
            ->actingAs($usuario)
            ->post('/categorias', [
                'nome' => 'Uber',
                'tipo' => TipoCategoria::Saida->value,
                'icone' => 'directions_car',
                'id_categoria_pai' => $pai->id_categoria,
            ]);

        $response->assertSessionHasErrors('nome');
    }

    public function test_arquivar_categoria_principal_arquiva_subcategorias(): void
    {
        $usuario = Usuario::factory()->create();
        $pai = $this->criarCategoriaPrincipal($usuario, 'Transporte');
        $sub = $this->criarSubcategoria($usuario, $pai, 'Uber');

        $response = $this
            ->actingAs($usuario)
            ->patch("/categorias/{$pai->getRouteKey()}/arquivar", [
                'arquivada' => 'S',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/categorias');

        $this->assertSame(SimNao::Sim, $pai->fresh()->arquivada);
        $this->assertSame(SimNao::Sim, $sub->fresh()->arquivada);
    }

    public function test_alterar_tipo_da_principal_propaga_para_subcategorias(): void
    {
        $usuario = Usuario::factory()->create();
        $pai = $this->criarCategoriaPrincipal($usuario, 'Transporte');
        $sub = $this->criarSubcategoria($usuario, $pai, 'Uber');

        $response = $this
            ->actingAs($usuario)
            ->put("/categorias/{$pai->getRouteKey()}", [
                'nome' => 'Transporte',
                'tipo' => TipoCategoria::Entrada->value,
                'icone' => 'directions_car',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/categorias');

        $this->assertSame(TipoCategoria::Entrada, $pai->fresh()->tipo);
        $this->assertSame(TipoCategoria::Entrada, $sub->fresh()->tipo);
    }

    public function test_excluir_categoria_principal_remove_subcategorias(): void
    {
        $usuario = Usuario::factory()->create();
        $pai = $this->criarCategoriaPrincipal($usuario, 'Transporte');
        $sub = $this->criarSubcategoria($usuario, $pai, 'Uber');

        $response = $this
            ->actingAs($usuario)
            ->delete("/categorias/{$pai->getRouteKey()}");

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/categorias');

        $this->assertDatabaseMissing('categorias', [
            'id_categoria' => $pai->id_categoria,
        ]);
        $this->assertDatabaseMissing('categorias', [
            'id_categoria' => $sub->id_categoria,
        ]);
    }

    public function test_listagem_retorna_subcategorias_aninhadas(): void
    {
        $usuario = Usuario::factory()->create();
        $pai = $this->criarCategoriaPrincipal($usuario, 'Transporte Custom');
        $this->criarSubcategoria($usuario, $pai, '99POP');

        $response = $this
            ->actingAs($usuario)
            ->get('/categorias');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Categoria/Index')
            ->has('categorias')
            ->where('categorias', function ($categorias) {
                $transporte = collect($categorias)->firstWhere('nome', 'Transporte Custom');

                if ($transporte === null) {
                    return false;
                }

                $subs = collect($transporte['subcategorias'] ?? []);

                return $subs->contains(fn ($sub) => $sub['nome'] === '99POP');
            })
        );
    }

    private function criarCategoriaPrincipal(Usuario $usuario, string $nome): Categoria
    {
        return Categoria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'id_categoria_pai' => null,
            'nivel' => Categoria::NIVEL_PRINCIPAL,
            'padrao' => SimNao::Nao,
            'nome' => $nome,
            'tipo' => TipoCategoria::Saida,
            'icone' => 'directions_car',
            'cor' => '#3b82f6',
            'arquivada' => SimNao::Nao,
        ]);
    }

    private function criarSubcategoria(Usuario $usuario, Categoria $pai, string $nome): Categoria
    {
        return Categoria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'id_categoria_pai' => $pai->id_categoria,
            'nivel' => Categoria::NIVEL_SUBCATEGORIA,
            'padrao' => SimNao::Nao,
            'nome' => $nome,
            'tipo' => $pai->tipo,
            'icone' => 'directions_car',
            'cor' => $pai->cor,
            'arquivada' => SimNao::Nao,
        ]);
    }
}
