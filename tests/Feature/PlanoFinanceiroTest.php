<?php

namespace Tests\Feature;

use App\Enum\CorItemPlanoFinanceiro;
use App\Enum\FrequenciaRecorrencia;
use App\Enum\SimNao;
use App\Enum\SituacaoPlanoFinanceiro;
use App\Enum\TipoCalculoItemPlano;
use App\Enum\TipoContaBancaria;
use App\Models\Alocacao\Alocacao;
use App\Models\ContaBancaria\ContaBancaria;
use App\Models\Objetivo\Objetivo;
use App\Models\PlanoFinanceiro\ItemPlanoFinanceiro;
use App\Models\PlanoFinanceiro\PlanoFinanceiro;
use App\Models\Renda\Renda;
use App\Models\Usuario\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanoFinanceiroTest extends TestCase
{
    use RefreshDatabase;

    private const URL_PLANOS_FINANCEIROS = '/rendas/planos-financeiros';

    public function test_usuario_pode_criar_plano_financeiro_com_itens(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $objetivo = $this->criarObjetivo($usuario);

        $this->actingAs($usuario)
            ->post('/planos-financeiros', [
                'nome' => 'Essencial',
                'descricao' => 'Regra 50/30/20',
                'itens' => [
                    $this->montarItemPercentual('Sobrevivência', 50),
                    $this->montarItemPercentual('Reserva', 30, $conta, $objetivo),
                    $this->montarItemPercentual('Livre', 20),
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(self::URL_PLANOS_FINANCEIROS);

        $plano = PlanoFinanceiro::query()->where('id_usuario', $usuario->id_usuario)->firstOrFail();

        $this->assertSame('Essencial', $plano->nome);
        $this->assertSame(SituacaoPlanoFinanceiro::Ativo, $plano->situacao);
        $this->assertSame(SimNao::Sim, $plano->padrao);
        $this->assertDatabaseCount('itens_plano', 3);
        $this->assertDatabaseHas('itens_plano', [
            'id_plano_financeiro' => $plano->id_plano_financeiro,
            'nome' => 'Reserva',
            'tipo_calculo' => TipoCalculoItemPlano::Percentual->value,
            'valor' => 30.00,
            'separar_do_saldo' => SimNao::Sim->value,
            'id_conta_bancaria_destino' => $conta->id_conta_bancaria,
            'id_objetivo' => $objetivo->id_objetivo,
            'id_alocacao' => Alocacao::query()->where('nome', 'Reserva')->value('id_alocacao'),
            'ordem' => 1,
        ]);
        $this->assertDatabaseHas('itens_plano', [
            'id_plano_financeiro' => $plano->id_plano_financeiro,
            'nome' => 'Sobrevivência',
            'separar_do_saldo' => SimNao::Nao->value,
            'id_conta_bancaria_destino' => null,
            'ordem' => 0,
        ]);
    }

    public function test_item_orientativo_descarta_conta_e_objetivo_enviados(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $objetivo = $this->criarObjetivo($usuario);

        $itemOrientativo = [
            ...$this->montarItemPercentual('Lazer', 20),
            'id_conta_bancaria_destino' => $conta->id_conta_bancaria,
            'id_objetivo' => $objetivo->id_objetivo,
        ];

        $this->actingAs($usuario)
            ->post('/planos-financeiros', ['nome' => 'Plano', 'itens' => [$itemOrientativo]])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('itens_plano', [
            'nome' => 'Lazer',
            'separar_do_saldo' => SimNao::Nao->value,
            'id_conta_bancaria_destino' => null,
            'id_objetivo' => null,
        ]);
    }

    public function test_item_valor_fixo_aceita_valor_monetario(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);

        $this->actingAs($usuario)
            ->post('/planos-financeiros', [
                'nome' => 'Investidor',
                'itens' => [$this->montarItemValorFixo('Investimentos', '1.250,50', $conta)],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('itens_plano', [
            'nome' => 'Investimentos',
            'tipo_calculo' => TipoCalculoItemPlano::ValorFixo->value,
            'valor' => 1250.50,
            'separar_do_saldo' => SimNao::Sim->value,
        ]);
    }

    public function test_nao_permite_soma_percentual_acima_de_cem(): void
    {
        $usuario = Usuario::factory()->create();

        $this->actingAs($usuario)
            ->from(self::URL_PLANOS_FINANCEIROS)
            ->post('/planos-financeiros', [
                'nome' => 'Excedido',
                'itens' => [
                    $this->montarItemPercentual('Sobrevivência', 60),
                    $this->montarItemPercentual('Livre', 50),
                ],
            ])
            ->assertSessionHasErrors('itens');

        $this->assertDatabaseCount('planos_financeiros', 0);
        $this->assertDatabaseCount('itens_plano', 0);
    }

    public function test_plano_exige_ao_menos_um_item(): void
    {
        $usuario = Usuario::factory()->create();

        $this->actingAs($usuario)
            ->from(self::URL_PLANOS_FINANCEIROS)
            ->post('/planos-financeiros', ['nome' => 'Vazio', 'itens' => []])
            ->assertSessionHasErrors('itens');

        $this->assertDatabaseCount('planos_financeiros', 0);
    }

    public function test_item_que_separa_do_saldo_exige_conta_destino(): void
    {
        $usuario = Usuario::factory()->create();

        $itemSemConta = [...$this->montarItemPercentual('Reserva', 10), 'separar_do_saldo' => true];

        $this->actingAs($usuario)
            ->from(self::URL_PLANOS_FINANCEIROS)
            ->post('/planos-financeiros', ['nome' => 'Plano', 'itens' => [$itemSemConta]])
            ->assertSessionHasErrors('itens.0.id_conta_bancaria_destino');

        $this->assertDatabaseCount('planos_financeiros', 0);
    }

    public function test_item_que_separa_do_saldo_exige_alocacao(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);

        $itemSemAlocacao = [...$this->montarItemPercentual('Reserva', 10, $conta), 'id_alocacao' => null];

        $this->actingAs($usuario)
            ->from(self::URL_PLANOS_FINANCEIROS)
            ->post('/planos-financeiros', ['nome' => 'Plano', 'itens' => [$itemSemAlocacao]])
            ->assertSessionHasErrors('itens.0.id_alocacao');

        $this->assertDatabaseCount('planos_financeiros', 0);
    }

    public function test_item_que_separa_do_saldo_nao_aceita_alocacao_de_outro_usuario(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $alocacaoDeOutroUsuario = $this->buscarOuCriarAlocacao((int) Usuario::factory()->create()->id_usuario, 'Carro');

        $item = [...$this->montarItemPercentual('Carro', 10, $conta), 'id_alocacao' => $alocacaoDeOutroUsuario->id_alocacao];

        $this->actingAs($usuario)
            ->from(self::URL_PLANOS_FINANCEIROS)
            ->post('/planos-financeiros', ['nome' => 'Plano', 'itens' => [$item]])
            ->assertSessionHasErrors('itens.0.id_alocacao');
    }

    public function test_item_orientativo_descarta_alocacao_enviada(): void
    {
        $usuario = Usuario::factory()->create();
        $alocacao = $this->buscarOuCriarAlocacao((int) $usuario->id_usuario, 'Lazer');

        $this->actingAs($usuario)
            ->post('/planos-financeiros', [
                'nome' => 'Plano',
                'itens' => [[...$this->montarItemPercentual('Lazer', 20), 'id_alocacao' => $alocacao->id_alocacao]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('itens_plano', [
            'nome' => 'Lazer',
            'separar_do_saldo' => SimNao::Nao->value,
            'id_alocacao' => null,
        ]);
    }

    public function test_planos_diferentes_reutilizam_a_mesma_alocacao(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $alocacaoCarro = $this->buscarOuCriarAlocacao((int) $usuario->id_usuario, 'Carro');

        $this->actingAs($usuario)
            ->post('/planos-financeiros', [
                'nome' => 'Essencial',
                'itens' => [[...$this->montarItemPercentual('Carro', 10, $conta), 'id_alocacao' => $alocacaoCarro->id_alocacao]],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($usuario)
            ->post('/planos-financeiros', [
                'nome' => 'Novo ciclo',
                'itens' => [[...$this->montarItemPercentual('Carro novo', 15, $conta), 'id_alocacao' => $alocacaoCarro->id_alocacao]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, ItemPlanoFinanceiro::query()->where('id_alocacao', $alocacaoCarro->id_alocacao)->count());
    }

    public function test_item_que_separa_do_saldo_nao_aceita_conta_arquivada(): void
    {
        $usuario = Usuario::factory()->create();
        $contaArquivada = $this->criarConta($usuario, SimNao::Sim);

        $this->actingAs($usuario)
            ->from(self::URL_PLANOS_FINANCEIROS)
            ->post('/planos-financeiros', [
                'nome' => 'Plano',
                'itens' => [$this->montarItemPercentual('Reserva', 10, $contaArquivada)],
            ])
            ->assertSessionHasErrors('itens.0.id_conta_bancaria_destino');
    }

    public function test_item_que_separa_do_saldo_nao_aceita_conta_de_outro_usuario(): void
    {
        $usuario = Usuario::factory()->create();
        $contaDeOutroUsuario = $this->criarConta(Usuario::factory()->create());

        $this->actingAs($usuario)
            ->from(self::URL_PLANOS_FINANCEIROS)
            ->post('/planos-financeiros', [
                'nome' => 'Plano',
                'itens' => [$this->montarItemPercentual('Reserva', 10, $contaDeOutroUsuario)],
            ])
            ->assertSessionHasErrors('itens.0.id_conta_bancaria_destino');
    }

    public function test_somente_o_primeiro_plano_e_criado_como_padrao(): void
    {
        $usuario = Usuario::factory()->create();
        $planoExistente = $this->criarPlanoComItens($usuario, 'Essencial', padrao: SimNao::Sim);

        $this->actingAs($usuario)
            ->post('/planos-financeiros', [
                'nome' => 'Investidor',
                'itens' => [$this->montarItemPercentual('Livre', 100)],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('planos_financeiros', ['nome' => 'Investidor', 'padrao' => SimNao::Nao->value]);
        $this->assertDatabaseHas('planos_financeiros', [
            'id_plano_financeiro' => $planoExistente->id_plano_financeiro,
            'padrao' => SimNao::Sim->value,
        ]);
    }

    public function test_atualizar_plano_sincroniza_itens(): void
    {
        $usuario = Usuario::factory()->create();
        $plano = $this->criarPlanoComItens($usuario, 'Essencial');
        $itemMantido = $this->criarItemNoPlano($plano, 'Sobrevivência', TipoCalculoItemPlano::Percentual, 50);
        $itemRemovido = $this->criarItemNoPlano($plano, 'Livre', TipoCalculoItemPlano::Percentual, 20);

        $this->actingAs($usuario)
            ->put('/planos-financeiros/'.$plano->getRouteKey(), [
                'nome' => 'Essencial ajustado',
                'itens' => [
                    [...$this->montarItemPercentual('Necessidades', 60), 'id' => $itemMantido->id_item_plano],
                    $this->montarItemPercentual('Desejos', 40),
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(self::URL_PLANOS_FINANCEIROS);

        $this->assertDatabaseHas('planos_financeiros', [
            'id_plano_financeiro' => $plano->id_plano_financeiro,
            'nome' => 'Essencial ajustado',
        ]);
        $this->assertDatabaseHas('itens_plano', [
            'id_item_plano' => $itemMantido->id_item_plano,
            'nome' => 'Necessidades',
            'valor' => 60.00,
            'ordem' => 0,
        ]);
        $this->assertDatabaseMissing('itens_plano', [
            'id_item_plano' => $itemRemovido->id_item_plano,
        ]);
        $this->assertDatabaseHas('itens_plano', [
            'id_plano_financeiro' => $plano->id_plano_financeiro,
            'nome' => 'Desejos',
            'ordem' => 1,
        ]);
        $this->assertDatabaseCount('itens_plano', 2);
    }

    public function test_atualizar_plano_vinculado_nao_permite_itens_acima_do_valor_da_renda(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $plano = $this->criarPlanoComItens($usuario, 'Essencial');
        $this->criarItemNoPlano($plano, 'Reserva', TipoCalculoItemPlano::ValorFixo, 500, $conta);
        $this->criarRenda($usuario, $conta, 1000, $plano);

        $this->actingAs($usuario)
            ->from(self::URL_PLANOS_FINANCEIROS)
            ->put('/planos-financeiros/'.$plano->getRouteKey(), [
                'nome' => 'Essencial',
                'itens' => [$this->montarItemValorFixo('Reserva', '1.500,00', $conta)],
            ])
            ->assertSessionHasErrors('itens');

        $this->assertDatabaseHas('itens_plano', [
            'id_plano_financeiro' => $plano->id_plano_financeiro,
            'nome' => 'Reserva',
            'valor' => 500.00,
        ]);
    }

    public function test_definir_padrao_mantem_apenas_um_plano_padrao(): void
    {
        $usuario = Usuario::factory()->create();
        $planoPadraoAtual = $this->criarPlanoComItens($usuario, 'Essencial', padrao: SimNao::Sim);
        $novoPlanoPadrao = $this->criarPlanoComItens($usuario, 'Investidor');

        $this->actingAs($usuario)
            ->patch('/planos-financeiros/'.$novoPlanoPadrao->getRouteKey().'/padrao')
            ->assertSessionHasNoErrors()
            ->assertRedirect(self::URL_PLANOS_FINANCEIROS);

        $this->assertSame(SimNao::Sim, $novoPlanoPadrao->fresh()->padrao);
        $this->assertSame(SimNao::Nao, $planoPadraoAtual->fresh()->padrao);
    }

    public function test_plano_inativo_nao_pode_ser_definido_como_padrao(): void
    {
        $usuario = Usuario::factory()->create();
        $planoInativo = $this->criarPlanoComItens($usuario, 'Antigo', SituacaoPlanoFinanceiro::Inativo);

        $this->actingAs($usuario)
            ->patch('/planos-financeiros/'.$planoInativo->getRouteKey().'/padrao')
            ->assertSessionHas('erro');

        $this->assertSame(SimNao::Nao, $planoInativo->fresh()->padrao);
    }

    public function test_inativar_plano_remove_padrao(): void
    {
        $usuario = Usuario::factory()->create();
        $plano = $this->criarPlanoComItens($usuario, 'Essencial', padrao: SimNao::Sim);

        $this->actingAs($usuario)
            ->patch('/planos-financeiros/'.$plano->getRouteKey().'/situacao')
            ->assertSessionHasNoErrors()
            ->assertRedirect(self::URL_PLANOS_FINANCEIROS);

        $planoAtualizado = $plano->fresh();
        $this->assertSame(SituacaoPlanoFinanceiro::Inativo, $planoAtualizado->situacao);
        $this->assertSame(SimNao::Nao, $planoAtualizado->padrao);
    }

    public function test_nao_permite_inativar_plano_aplicado_em_renda(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $plano = $this->criarPlanoComItens($usuario, 'Essencial');
        $this->criarRenda($usuario, $conta, 4000, $plano);

        $this->actingAs($usuario)
            ->patch('/planos-financeiros/'.$plano->getRouteKey().'/situacao')
            ->assertRedirect(self::URL_PLANOS_FINANCEIROS)
            ->assertSessionHas('erro');

        $this->assertSame(SituacaoPlanoFinanceiro::Ativo, $plano->fresh()->situacao);
    }

    public function test_nao_permite_excluir_plano_aplicado_em_renda(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $plano = $this->criarPlanoComItens($usuario, 'Essencial');
        $this->criarRenda($usuario, $conta, 4000, $plano);

        $this->actingAs($usuario)
            ->delete('/planos-financeiros/'.$plano->getRouteKey())
            ->assertRedirect(self::URL_PLANOS_FINANCEIROS)
            ->assertSessionHas('erro');

        $this->assertDatabaseHas('planos_financeiros', ['id_plano_financeiro' => $plano->id_plano_financeiro]);
    }

    public function test_usuario_pode_excluir_plano_sem_rendas(): void
    {
        $usuario = Usuario::factory()->create();
        $plano = $this->criarPlanoComItens($usuario, 'Essencial');
        $this->criarItemNoPlano($plano, 'Livre', TipoCalculoItemPlano::Percentual, 100);

        $this->actingAs($usuario)
            ->delete('/planos-financeiros/'.$plano->getRouteKey())
            ->assertSessionHasNoErrors()
            ->assertRedirect(self::URL_PLANOS_FINANCEIROS);

        $this->assertDatabaseCount('planos_financeiros', 0);
        $this->assertDatabaseCount('itens_plano', 0);
    }

    public function test_usuario_nao_pode_alterar_plano_de_outro_usuario(): void
    {
        $dono = Usuario::factory()->create();
        $intruso = Usuario::factory()->create();
        $plano = $this->criarPlanoComItens($dono, 'Essencial');

        $this->actingAs($intruso)
            ->put('/planos-financeiros/'.$plano->getRouteKey(), [
                'nome' => 'Invadido',
                'itens' => [$this->montarItemPercentual('Livre', 100)],
            ])
            ->assertForbidden();

        $this->actingAs($intruso)
            ->delete('/planos-financeiros/'.$plano->getRouteKey())
            ->assertForbidden();

        $this->assertDatabaseHas('planos_financeiros', [
            'id_plano_financeiro' => $plano->id_plano_financeiro,
            'nome' => 'Essencial',
        ]);
    }

    public function test_renda_pode_ser_criada_com_plano_financeiro(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $plano = $this->criarPlanoComItens($usuario, 'Essencial');
        $this->criarItemNoPlano($plano, 'Reserva', TipoCalculoItemPlano::Percentual, 30, $conta);

        $this->actingAs($usuario)
            ->post('/rendas', $this->montarDadosRenda($conta, '4.000,00', $plano))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/rendas');

        $this->assertDatabaseHas('rendas', [
            'id_usuario' => $usuario->id_usuario,
            'id_plano_financeiro' => $plano->id_plano_financeiro,
        ]);
    }

    public function test_renda_nao_aceita_plano_inativo(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $planoInativo = $this->criarPlanoComItens($usuario, 'Antigo', SituacaoPlanoFinanceiro::Inativo);

        $this->actingAs($usuario)
            ->from('/rendas')
            ->post('/rendas', $this->montarDadosRenda($conta, '4.000,00', $planoInativo))
            ->assertSessionHasErrors('id_plano_financeiro');

        $this->assertDatabaseCount('rendas', 0);
    }

    public function test_renda_nao_aceita_plano_de_outro_usuario(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $planoDeOutroUsuario = $this->criarPlanoComItens(Usuario::factory()->create(), 'Alheio');

        $this->actingAs($usuario)
            ->from('/rendas')
            ->post('/rendas', $this->montarDadosRenda($conta, '4.000,00', $planoDeOutroUsuario))
            ->assertSessionHasErrors('id_plano_financeiro');

        $this->assertDatabaseCount('rendas', 0);
    }

    public function test_nao_permite_reduzir_renda_abaixo_dos_itens_que_separam_do_saldo(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $plano = $this->criarPlanoComItens($usuario, 'Essencial');
        $this->criarItemNoPlano($plano, 'Reserva', TipoCalculoItemPlano::ValorFixo, 3000, $conta);
        $renda = $this->criarRenda($usuario, $conta, 4000, $plano);

        $this->actingAs($usuario)
            ->from('/rendas')
            ->put('/rendas/'.$renda->getRouteKey(), $this->montarDadosRenda($conta, '2.000,00', $plano))
            ->assertSessionHasErrors('id_plano_financeiro');

        $this->assertDatabaseHas('rendas', [
            'id_renda' => $renda->id_renda,
            'valor_esperado' => 4000.00,
        ]);
    }

    public function test_item_orientativo_nao_limita_valor_da_renda(): void
    {
        $usuario = Usuario::factory()->create();
        $conta = $this->criarConta($usuario);
        $plano = $this->criarPlanoComItens($usuario, 'Orientativo');
        $this->criarItemNoPlano($plano, 'Sobrevivência', TipoCalculoItemPlano::ValorFixo, 5000);

        $this->actingAs($usuario)
            ->post('/rendas', $this->montarDadosRenda($conta, '1.000,00', $plano))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('rendas', ['id_plano_financeiro' => $plano->id_plano_financeiro]);
    }

    private function montarItemPercentual(
        string $nome,
        float $percentual,
        ?ContaBancaria $contaDestino = null,
        ?Objetivo $objetivo = null,
    ): array {
        return [
            'nome' => $nome,
            'cor' => CorItemPlanoFinanceiro::padrao()->value,
            'tipo_calculo' => TipoCalculoItemPlano::Percentual->value,
            'valor' => $percentual,
            'separar_do_saldo' => $contaDestino !== null,
            'id_conta_bancaria_destino' => $contaDestino?->id_conta_bancaria,
            'id_objetivo' => $objetivo?->id_objetivo,
            'id_alocacao' => $this->idAlocacaoParaConta($contaDestino, $nome),
        ];
    }

    private function montarItemValorFixo(string $nome, string $valor, ?ContaBancaria $contaDestino = null): array
    {
        return [
            'nome' => $nome,
            'cor' => CorItemPlanoFinanceiro::padrao()->value,
            'tipo_calculo' => TipoCalculoItemPlano::ValorFixo->value,
            'valor' => $valor,
            'separar_do_saldo' => $contaDestino !== null,
            'id_conta_bancaria_destino' => $contaDestino?->id_conta_bancaria,
            'id_objetivo' => null,
            'id_alocacao' => $this->idAlocacaoParaConta($contaDestino, $nome),
        ];
    }

    private function idAlocacaoParaConta(?ContaBancaria $contaDestino, string $nome): ?int
    {
        if (! $contaDestino) {
            return null;
        }

        return (int) $this->buscarOuCriarAlocacao((int) $contaDestino->id_usuario, $nome)->id_alocacao;
    }

    private function buscarOuCriarAlocacao(int $idUsuario, string $nome): Alocacao
    {
        return Alocacao::query()->firstOrCreate(
            ['id_usuario' => $idUsuario, 'nome' => $nome],
            ['saldo_consolidado' => 0],
        );
    }

    private function montarDadosRenda(ContaBancaria $conta, string $valorEsperado, ?PlanoFinanceiro $plano = null): array
    {
        return [
            'descricao' => 'Salário',
            'valor_esperado' => $valorEsperado,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'frequencia' => FrequenciaRecorrencia::Mensal->value,
            'dia_esperado' => 5,
            'id_plano_financeiro' => $plano?->id_plano_financeiro,
        ];
    }

    private function criarPlanoComItens(
        Usuario $usuario,
        string $nome,
        SituacaoPlanoFinanceiro $situacao = SituacaoPlanoFinanceiro::Ativo,
        SimNao $padrao = SimNao::Nao,
    ): PlanoFinanceiro {
        return PlanoFinanceiro::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => $nome,
            'situacao' => $situacao,
            'padrao' => $padrao,
        ]);
    }

    private function criarItemNoPlano(
        PlanoFinanceiro $plano,
        string $nome,
        TipoCalculoItemPlano $tipoCalculo,
        float $valor,
        ?ContaBancaria $contaDestino = null,
    ): ItemPlanoFinanceiro {
        return ItemPlanoFinanceiro::query()->create([
            'id_plano_financeiro' => $plano->id_plano_financeiro,
            'id_usuario' => $plano->id_usuario,
            'id_alocacao' => $this->idAlocacaoParaConta($contaDestino, $nome),
            'nome' => $nome,
            'cor' => CorItemPlanoFinanceiro::padrao(),
            'tipo_calculo' => $tipoCalculo,
            'valor' => $valor,
            'separar_do_saldo' => $contaDestino ? SimNao::Sim : SimNao::Nao,
            'id_conta_bancaria_destino' => $contaDestino?->id_conta_bancaria,
            'ordem' => ItemPlanoFinanceiro::query()->where('id_plano_financeiro', $plano->id_plano_financeiro)->count(),
        ]);
    }

    private function criarConta(Usuario $usuario, SimNao $arquivada = SimNao::Nao): ContaBancaria
    {
        return ContaBancaria::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nome' => 'Conta teste',
            'saldo_inicial' => 0,
            'tipo' => TipoContaBancaria::Corrente,
            'arquivada' => $arquivada,
            'padrao_desconto' => SimNao::Nao,
            'exibir_resumo' => SimNao::Sim,
        ]);
    }

    private function criarRenda(Usuario $usuario, ContaBancaria $conta, float $valorEsperado, ?PlanoFinanceiro $plano = null): Renda
    {
        return Renda::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Salário',
            'valor_esperado' => $valorEsperado,
            'id_conta_bancaria' => $conta->id_conta_bancaria,
            'id_plano_financeiro' => $plano?->id_plano_financeiro,
            'frequencia' => FrequenciaRecorrencia::Mensal,
            'dia_esperado' => 5,
            'data_inicio' => now()->startOfMonth()->addDays(4)->toDateString(),
        ]);
    }

    private function criarObjetivo(Usuario $usuario): Objetivo
    {
        return Objetivo::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'descricao' => 'Reserva de emergência',
            'valor_meta' => 10000,
            'data_limite' => now()->addYear()->toDateString(),
            'exibir_dashboard' => SimNao::Nao,
        ]);
    }
}
