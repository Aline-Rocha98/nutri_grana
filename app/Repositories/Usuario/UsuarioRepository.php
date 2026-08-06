<?php

namespace App\Repositories\Usuario;

use App\Models\Usuario\CodigoAlteracaoSenha;
use App\Models\Usuario\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UsuarioRepository
{
    public function atualizar(Usuario $usuario, array $dados): Usuario
    {
        $usuario->fill($dados);
        $usuario->save();

        return $usuario->refresh();
    }

    public function excluir(Usuario $usuario): void
    {
        $usuario->delete();
    }

    public function invalidarCodigosAlteracaoSenha(int $idUsuario): void
    {
        CodigoAlteracaoSenha::query()
            ->where('id_usuario', $idUsuario)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);
    }

    public function criarCodigoAlteracaoSenha(int $idUsuario, string $codigoHash, \DateTimeInterface $expiresAt): CodigoAlteracaoSenha
    {
        return CodigoAlteracaoSenha::query()->create([
            'id_usuario' => $idUsuario,
            'codigo_hash' => $codigoHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public function buscarCodigoAlteracaoSenhaValido(int $idUsuario): ?CodigoAlteracaoSenha
    {
        return CodigoAlteracaoSenha::query()
            ->where('id_usuario', $idUsuario)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    public function marcarCodigoComoUsado(CodigoAlteracaoSenha $codigo): void
    {
        $codigo->update(['used_at' => now()]);
    }

    public function limparTokensResetSenha(string $email): void
    {
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();
    }

    public function limparSessoesDoUsuario(int $idUsuario): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        DB::table('sessions')
            ->where('user_id', $idUsuario)
            ->delete();
    }
}
