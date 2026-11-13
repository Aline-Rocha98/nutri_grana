<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    private const CONEXAO_PERMITIDA_NOS_TESTES = 'sqlite';

    private const BANCO_PERMITIDO_NOS_TESTES = ':memory:';

    public function createApplication(): Application
    {
        $aplicacao = parent::createApplication();

        $this->garantirBancoIsoladoParaTestes($aplicacao);

        return $aplicacao;
    }

    private function garantirBancoIsoladoParaTestes(Application $aplicacao): void
    {
        $conexao = (string) $aplicacao['config']->get('database.default');
        $banco = (string) $aplicacao['config']->get("database.connections.{$conexao}.database");

        if ($conexao !== self::CONEXAO_PERMITIDA_NOS_TESTES || $banco !== self::BANCO_PERMITIDO_NOS_TESTES) {
            throw new RuntimeException(
                "Testes bloqueados: a conexão [{$conexao}] com o banco [{$banco}] não é o SQLite em memória. "
                .'Os testes nunca devem usar o banco real.'
            );
        }
    }
}
