<?php

namespace App\Services\Usuario;

use App\Mail\CodigoAlteracaoSenhaMail;
use App\Models\Usuario\Usuario;
use App\Repositories\Usuario\UsuarioRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PerfilService
{
    private const DIRETORIO_FOTOS = 'avatars';

    private const TTL_CODIGO_MINUTOS = 15;

    public function __construct(
        private readonly UsuarioRepository $usuarioRepository,
    ) {}

    public function atualizarPerfil(Usuario $usuario, array $dados, ?UploadedFile $foto = null): Usuario
    {
        if ($foto !== null) {
            $dados['foto_perfil'] = $this->armazenarFotoPerfil($usuario, $foto);
        }

        return $this->usuarioRepository->atualizar($usuario, $dados);
    }

    public function solicitarCodigoAlteracaoSenha(Usuario $usuario): void
    {
        $this->usuarioRepository->invalidarCodigosAlteracaoSenha((int) $usuario->id_usuario);

        $codigo = (string) random_int(100000, 999999);

        $this->usuarioRepository->criarCodigoAlteracaoSenha(
            (int) $usuario->id_usuario,
            Hash::make($codigo),
            now()->addMinutes(self::TTL_CODIGO_MINUTOS),
        );

        Mail::to($usuario->email)->send(new CodigoAlteracaoSenhaMail(
            nome: $usuario->nome,
            codigo: $codigo,
            minutosValidade: self::TTL_CODIGO_MINUTOS,
        ));
    }

    public function confirmarAlteracaoSenha(Usuario $usuario, string $codigo, string $novaSenha): void
    {
        $registro = $this->usuarioRepository->buscarCodigoAlteracaoSenhaValido((int) $usuario->id_usuario);

        if (! $registro || ! Hash::check($codigo, $registro->codigo_hash)) {
            throw ValidationException::withMessages([
                'codigo' => 'Código inválido ou expirado.',
            ]);
        }

        $this->usuarioRepository->atualizar($usuario, [
            'senha' => $novaSenha,
        ]);

        $this->usuarioRepository->marcarCodigoComoUsado($registro);
        $this->usuarioRepository->invalidarCodigosAlteracaoSenha((int) $usuario->id_usuario);
        $this->usuarioRepository->limparTokensResetSenha($usuario->email);
    }

    public function excluirConta(Usuario $usuario): void
    {
        $idUsuario = (int) $usuario->id_usuario;
        $email = $usuario->email;
        $fotoPerfil = $usuario->foto_perfil;

        Log::info('Exclusão de conta iniciada', [
            'id_usuario' => $idUsuario,
        ]);

        $this->removerArquivoFoto($fotoPerfil);
        $this->usuarioRepository->invalidarCodigosAlteracaoSenha($idUsuario);
        $this->usuarioRepository->limparTokensResetSenha($email);
        $this->usuarioRepository->limparSessoesDoUsuario($idUsuario);
        $this->usuarioRepository->excluir($usuario);

        Log::info('Exclusão de conta concluída', [
            'id_usuario' => $idUsuario,
        ]);
    }

    public function urlFotoPerfil(?Usuario $usuario): ?string
    {
        if (! $usuario?->foto_perfil) {
            return null;
        }

        return Storage::disk('public')->url($usuario->foto_perfil);
    }

    private function armazenarFotoPerfil(Usuario $usuario, UploadedFile $foto): string
    {
        $this->removerArquivoFoto($usuario->foto_perfil);

        return $foto->store(self::DIRETORIO_FOTOS, 'public');
    }

    private function removerArquivoFoto(?string $caminho): void
    {
        if (! $caminho) {
            return;
        }

        if (Storage::disk('public')->exists($caminho)) {
            Storage::disk('public')->delete($caminho);
        }
    }
}
