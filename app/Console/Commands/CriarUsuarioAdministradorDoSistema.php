<?php

namespace App\Console\Commands;

use App\Models\Usuario\Usuario;
use App\Repositories\Usuario\UsuarioRepository;
use Illuminate\Console\Command;

class CriarUsuarioAdministradorDoSistema extends Command
{
    protected $signature = 'usuario:criar-admin
                            {--email= : E-mail do usuário administrador}
                            {--nome= : Nome do usuário administrador}';

    protected $description = 'Cria ou promove um usuário administrador do sistema (senha pedida de forma interativa)';

    public function handle(UsuarioRepository $usuarioRepository): int
    {
        $email = $this->resolverEmail();

        if ($email === null) {
            return self::FAILURE;
        }

        $usuarioExistente = $usuarioRepository->buscarUsuarioPorEmail($email);

        if ($usuarioExistente) {
            return $this->promoverUsuarioExistente($usuarioRepository, $usuarioExistente);
        }

        return $this->criarNovoUsuarioAdministrador($usuarioRepository, $email);
    }

    private function resolverEmail(): ?string
    {
        $email = trim((string) ($this->option('email') ?: $this->ask('E-mail do administrador')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Informe um e-mail válido.');

            return null;
        }

        return mb_strtolower($email);
    }

    private function resolverNome(): ?string
    {
        $nome = trim((string) ($this->option('nome') ?: $this->ask('Nome do administrador')));

        if ($nome === '') {
            $this->error('Informe o nome do administrador.');

            return null;
        }

        return $nome;
    }

    private function solicitarSenhaConfirmada(): ?string
    {
        $senha = (string) $this->secret('Senha do administrador');
        $confirmacao = (string) $this->secret('Confirme a senha');

        if ($senha === '') {
            $this->error('A senha não pode ser vazia.');

            return null;
        }

        if ($senha !== $confirmacao) {
            $this->error('As senhas não conferem.');

            return null;
        }

        return $senha;
    }

    private function criarNovoUsuarioAdministrador(UsuarioRepository $usuarioRepository, string $email): int
    {
        $nome = $this->resolverNome();

        if ($nome === null) {
            return self::FAILURE;
        }

        $senha = $this->solicitarSenhaConfirmada();

        if ($senha === null) {
            return self::FAILURE;
        }

        $usuario = $usuarioRepository->criarUsuarioAdministradorDoSistema($nome, $email, $senha);

        $this->info("Administrador criado com sucesso: {$usuario->nome} ({$usuario->email}).");

        return self::SUCCESS;
    }

    private function promoverUsuarioExistente(UsuarioRepository $usuarioRepository, Usuario $usuarioExistente): int
    {
        if ($usuarioExistente->ehAdministradorDoSistema()) {
            $this->warn("O usuário {$usuarioExistente->email} já é administrador do sistema.");

            return self::SUCCESS;
        }

        if (! $this->confirm("Usuário {$usuarioExistente->email} já existe. Deseja promovê-lo a administrador?")) {
            $this->warn('Operação cancelada.');

            return self::FAILURE;
        }

        $deveAtualizarSenha = $this->confirm('Deseja também definir uma nova senha?', false);
        $senha = null;

        if ($deveAtualizarSenha) {
            $senha = $this->solicitarSenhaConfirmada();

            if ($senha === null) {
                return self::FAILURE;
            }
        }

        $usuario = $usuarioRepository->promoverUsuarioAdministradorDoSistema($usuarioExistente, $senha);

        $this->info("Usuário promovido a administrador: {$usuario->nome} ({$usuario->email}).");

        return self::SUCCESS;
    }
}
