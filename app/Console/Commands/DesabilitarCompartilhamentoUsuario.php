<?php

namespace App\Console\Commands;

use App\Enum\SimNao;
use App\Repositories\Usuario\UsuarioRepository;
use Illuminate\Console\Command;

class DesabilitarCompartilhamentoUsuario extends Command
{
    protected $signature = 'compartilhamento:desabilitar-usuario {email : E-mail do usuário}';

    protected $description = 'Desabilita o módulo de lançamentos compartilhados para um usuário';

    public function handle(UsuarioRepository $usuarioRepository): int
    {
        $usuario = $usuarioRepository->buscarUsuarioPorEmail((string) $this->argument('email'));

        if (! $usuario) {
            $this->error('Usuário não encontrado.');

            return self::FAILURE;
        }

        $usuarioRepository->definirCompartilhamentoHabilitado($usuario, SimNao::Nao);

        $this->info("Módulo compartilhado desabilitado para {$usuario->nome}.");

        return self::SUCCESS;
    }
}
