<?php

namespace Tests\Concerns;

use App\Enum\SimNao;
use App\Enum\TipoCategoria;
use App\Enum\TipoContaBancaria;
use App\Http\Middleware\EnsureAbsoluteSessionTimeout;
use App\Models\Categoria\Categoria;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\EspacoCompartilhado\LancamentoCompartilhado;
use App\Models\Usuario\Usuario;
use Database\Factories\EspacoCompartilhadoFactory;

trait PreparaEspacoCompartilhado
{
    protected function autenticarComo(Usuario $usuario): static
    {
        return $this->actingAs($usuario)
            ->withSession([EnsureAbsoluteSessionTimeout::SESSION_LOGIN_AT => now()->getTimestamp()]);
    }

    protected function criarUsuarioComCompartilhamento(string $nome): Usuario
    {
        return Usuario::factory()->comCompartilhamentoHabilitado()->create(['nome' => $nome]);
    }

    protected function criarEspacoComMembros(Usuario $administrador, Usuario ...$membros): EspacoCompartilhado
    {
        $espaco = EspacoCompartilhado::factory()
            ->criadoPorAdministrador($administrador)
            ->create(['nome' => 'Casa']);

        foreach ($membros as $membro) {
            EspacoCompartilhadoFactory::vincularMembroAoEspaco($espaco, $membro);
        }

        return $espaco;
    }

    protected function criarLancamentoCompartilhado(
        EspacoCompartilhado $espaco,
        Usuario $responsavel,
        array $atributos = [],
    ): LancamentoCompartilhado {
        return LancamentoCompartilhado::factory()
            ->doEspaco($espaco)
            ->sobResponsabilidadeDe($responsavel)
            ->create($atributos);
    }

    protected function criarContaBancariaPadrao(Usuario $usuario): ContaBancaria
    {
        return ContaBancaria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Banco Inter',
            'saldo_inicial' => 0,
            'tipo' => TipoContaBancaria::Corrente,
            'arquivada' => SimNao::Nao,
            'padrao_desconto' => SimNao::Sim,
            'exibir_resumo' => SimNao::Sim,
        ]);
    }

    protected function criarCategoriaDeSaidaDoUsuario(Usuario $usuario, string $nome = 'Moradia'): Categoria
    {
        return Categoria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nivel' => 1,
            'padrao' => SimNao::Nao,
            'nome' => $nome,
            'tipo' => TipoCategoria::Saida,
            'icone' => 'home',
            'cor' => '#1fa67e',
            'arquivada' => SimNao::Nao,
        ]);
    }

    protected function rotaDoLancamento(string $nomeRota, EspacoCompartilhado $espaco, LancamentoCompartilhado $lancamento): string
    {
        return route($nomeRota, [
            'espacoCompartilhado' => $espaco,
            'lancamentoCompartilhado' => $lancamento,
        ]);
    }
}
