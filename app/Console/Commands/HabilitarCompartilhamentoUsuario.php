<?php

namespace App\Console\Commands;

use App\Enum\SimNao;
use App\Repositories\Usuario\UsuarioRepository;
use Illuminate\Console\Command;

class HabilitarCompartilhamentoUsuario extends Command
{
    protected $signature = 'compartilhamento:habilitar-usuario {email : E-mail do usuário}';

    protected $description = 'Habilita o módulo de lançamentos compartilhados para um usuário';

    public function handle(UsuarioRepository $usuarioRepository): int
    {
        $usuario = $usuarioRepository->buscarUsuarioPorEmail((string) $this->argument('email'));

        if (! $usuario) {
            $this->error('Usuário não encontrado.');

            return self::FAILURE;
        }

        $usuarioRepository->definirCompartilhamentoHabilitado($usuario, SimNao::Sim);

        $this->info("Módulo compartilhado habilitado para {$usuario->nome}.");

        return self::SUCCESS;
    }
}
