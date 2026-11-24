<?php

namespace App\Services\EspacoCompartilhado;

use App\Enum\AcaoAuditoriaCompartilhada;
use App\Enum\StatusConviteCompartilhamento;
use App\Exceptions\EspacoCompartilhado\RespostaConviteInvalidaException;
use App\Models\EspacoCompartilhado\ConviteCompartilhamento;
use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Models\Usuario\Usuario;
use App\Repositories\EspacoCompartilhado\ConviteCompartilhamentoRepository;
use App\Repositories\EspacoCompartilhado\MembroEspacoCompartilhadoRepository;
use App\Repositories\Usuario\UsuarioRepository;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ConviteCompartilhamentoService
{
    public const MINUTOS_VALIDADE_CODIGO_CONVITE = 15;

    public const TAMANHO_CODIGO_CONVITE = 8;

    private const ALFABETO_CODIGO_CONVITE = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const MENSAGEM_CONVITE_INDISPONIVEL = 'Este convite não está mais disponível. Peça um novo convite.';

    public function __construct(
        private readonly ConviteCompartilhamentoRepository $conviteRepository,
        private readonly MembroEspacoCompartilhadoRepository $membroEspacoRepository,
        private readonly MembroEspacoCompartilhadoService $membroEspacoService,
        private readonly UsuarioRepository $usuarioRepository,
        private readonly AuditoriaEspacoCompartilhadoService $auditoriaService,
    ) {}

    public function listarConvitesRecebidosPeloUsuario(Usuario $usuario): Collection
    {
        return $this->conviteRepository->listarConvitesPendentesRecebidosPeloUsuario((int) $usuario->id_usuario);
    }

    public function listarConvitesPendentesDoEspaco(EspacoCompartilhado $espaco): Collection
    {
        return $this->conviteRepository
            ->listarConvitesPendentesDoEspaco((int) $espaco->id_espaco_compartilhado)
            ->each(fn (ConviteCompartilhamento $convite) => $convite->setRelation('espaco', $espaco));
    }

    public function convidarUsuarioCadastradoPorEmail(EspacoCompartilhado $espaco, Usuario $convidante, string $email): array
    {
        $idEspaco = (int) $espaco->id_espaco_compartilhado;
        $convidado = $this->buscarUsuarioConvidavel($idEspaco, $convidante, $email);

        $this->conviteRepository->expirarConvitesPendentesDoUsuarioNoEspaco($idEspaco, (int) $convidado->id_usuario);

        $codigo = $this->sortearCodigo();

        $convite = $this->conviteRepository->criarConvite([
            'id_espaco_compartilhado' => $idEspaco,
            'id_usuario_convidado' => (int) $convidado->id_usuario,
            'email_destinatario' => mb_strtolower((string) $convidado->email),
            'codigo_hash' => $this->calcularHashDoCodigo($codigo),
            'tentativas_invalidas' => 0,
            'criado_por' => (int) $convidante->id_usuario,
            'expira_em' => now()->addMinutes(self::MINUTOS_VALIDADE_CODIGO_CONVITE),
            'status' => StatusConviteCompartilhamento::Pendente,
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            $idEspaco,
            (int) $convidante->id_usuario,
            AcaoAuditoriaCompartilhada::ConviteCriado,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_CONVITE,
            (int) $convite->id_convite_compartilhamento,
            null,
            [
                'email_destinatario' => $convite->email_destinatario,
                'nome' => $convidado->nome,
                'expira_em' => $convite->expira_em?->toDateTimeString(),
            ],
        );

        $convite->setRelation('convidado', $convidado);

        return [
            'convite' => $convite,
            'codigo' => $this->formatarCodigoParaExibicao($codigo),
            'convidado' => $convidado,
        ];
    }

    public function aceitarConviteComCodigo(
        ConviteCompartilhamento $convite,
        Usuario $usuario,
        string $emailConvidanteInformado,
        string $codigoInformado,
    ): EspacoCompartilhado {
        $conviteBloqueado = $this->bloquearConvitePendente($convite);

        if ($conviteBloqueado->estaExpirado()) {
            $this->conviteRepository->atualizarConvite($conviteBloqueado, [
                'status' => StatusConviteCompartilhamento::Expirado,
            ]);

            throw new RespostaConviteInvalidaException('codigo', 'Código expirado. Peça um novo convite.');
        }

        $conviteBloqueado->load('criador:id_usuario,email');

        if (! $this->respostaDoConviteConfere($conviteBloqueado, $emailConvidanteInformado, $codigoInformado)) {
            $this->registrarTentativaInvalidaDoConvite($conviteBloqueado, $usuario);
        }

        $espaco = $this->carregarEspacoAtivoDoConvite($conviteBloqueado);
        $this->garantirQueUsuarioAindaNaoParticipa($espaco, $usuario);

        $this->membroEspacoService->adicionarMembroAoEspaco($espaco, (int) $usuario->id_usuario);

        $this->conviteRepository->atualizarConvite($conviteBloqueado, [
            'status' => StatusConviteCompartilhamento::Aceito,
            'usado_em' => now(),
        ]);

        $this->registrarAuditoriaDeRespostaAoConvite($conviteBloqueado, $usuario, AcaoAuditoriaCompartilhada::ConviteAceito);

        return $espaco;
    }

    public function recusarConvite(ConviteCompartilhamento $convite, Usuario $usuario): void
    {
        $conviteBloqueado = $this->bloquearConvitePendente($convite);

        $this->conviteRepository->atualizarConvite($conviteBloqueado, [
            'status' => StatusConviteCompartilhamento::Recusado,
            'usado_em' => now(),
        ]);

        $this->registrarAuditoriaDeRespostaAoConvite($conviteBloqueado, $usuario, AcaoAuditoriaCompartilhada::ConviteRecusado);
    }

    public function revogarConvite(EspacoCompartilhado $espaco, ConviteCompartilhamento $convite, int $idUsuario): void
    {
        $conviteBloqueado = $this->bloquearConvitePendente($convite);

        $this->conviteRepository->atualizarConvite($conviteBloqueado, [
            'status' => StatusConviteCompartilhamento::Revogado,
        ]);

        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            $idUsuario,
            AcaoAuditoriaCompartilhada::ConviteRevogado,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_CONVITE,
            (int) $conviteBloqueado->id_convite_compartilhamento,
            ['status' => StatusConviteCompartilhamento::Pendente->value],
            [
                'status' => StatusConviteCompartilhamento::Revogado->value,
                'email_destinatario' => $conviteBloqueado->email_destinatario,
            ],
        );
    }

    private function buscarUsuarioConvidavel(int $idEspaco, Usuario $convidante, string $email): Usuario
    {
        $convidado = $this->usuarioRepository->buscarUsuarioPorEmail($email);

        if (! $convidado || ! $convidado->possuiCompartilhamentoHabilitado()) {
            throw ValidationException::withMessages([
                'email' => 'Não encontramos uma pessoa cadastrada com acesso aos espaços compartilhados para este e-mail.',
            ]);
        }

        if ((int) $convidado->id_usuario === (int) $convidante->id_usuario) {
            throw ValidationException::withMessages([
                'email' => 'Não é possível enviar um convite para o seu próprio e-mail.',
            ]);
        }

        if ($this->membroEspacoRepository->usuarioEhMembroAtivoDoEspaco($idEspaco, (int) $convidado->id_usuario)) {
            throw ValidationException::withMessages([
                'email' => 'Esta pessoa já participa do espaço.',
            ]);
        }

        return $convidado;
    }

    private function bloquearConvitePendente(ConviteCompartilhamento $convite): ConviteCompartilhamento
    {
        $conviteBloqueado = $this->conviteRepository->bloquearConviteParaAtualizacao((int) $convite->id_convite_compartilhamento);

        if (! $conviteBloqueado || ! $conviteBloqueado->estaPendente()) {
            throw ValidationException::withMessages([
                'convite' => self::MENSAGEM_CONVITE_INDISPONIVEL,
            ]);
        }

        return $conviteBloqueado;
    }

    private function respostaDoConviteConfere(
        ConviteCompartilhamento $convite,
        string $emailConvidanteInformado,
        string $codigoInformado,
    ): bool {
        $emailDoConvidante = $convite->criador?->email;
        $codigoNormalizado = $this->normalizarCodigo($codigoInformado);

        $emailConfere = $emailDoConvidante !== null && hash_equals(
            mb_strtolower(trim((string) $emailDoConvidante)),
            mb_strtolower(trim($emailConvidanteInformado))
        );

        $codigoConfere = strlen($codigoNormalizado) === self::TAMANHO_CODIGO_CONVITE
            && $convite->codigo_hash !== null
            && hash_equals((string) $convite->codigo_hash, $this->calcularHashDoCodigo($codigoNormalizado));

        return $emailConfere && $codigoConfere;
    }

    private function registrarTentativaInvalidaDoConvite(ConviteCompartilhamento $convite, Usuario $usuario): never
    {
        $tentativasInvalidas = $convite->tentativas_invalidas + 1;
        $atingiuLimite = $tentativasInvalidas >= ConviteCompartilhamento::LIMITE_TENTATIVAS_CODIGO;

        $conviteAtualizado = $this->conviteRepository->atualizarConvite($convite, array_filter([
            'tentativas_invalidas' => $tentativasInvalidas,
            'status' => $atingiuLimite ? StatusConviteCompartilhamento::Expirado : null,
        ]));

        if ($atingiuLimite) {
            $this->registrarAuditoriaDeRespostaAoConvite($conviteAtualizado, $usuario, AcaoAuditoriaCompartilhada::ConviteBloqueado);

            throw new RespostaConviteInvalidaException(
                'codigo',
                'Convite bloqueado por excesso de tentativas. Peça um novo convite.'
            );
        }

        $tentativasRestantes = $conviteAtualizado->quantidadeDeTentativasRestantes();
        $rotuloTentativas = $tentativasRestantes === 1 ? 'tentativa' : 'tentativas';

        throw new RespostaConviteInvalidaException(
            'codigo',
            "E-mail ou código inválido. Você ainda tem {$tentativasRestantes} {$rotuloTentativas}."
        );
    }

    private function carregarEspacoAtivoDoConvite(ConviteCompartilhamento $convite): EspacoCompartilhado
    {
        $convite->load('espaco');
        $espaco = $convite->espaco;

        if (! $espaco || ! $espaco->estaAtivo()) {
            throw ValidationException::withMessages([
                'convite' => self::MENSAGEM_CONVITE_INDISPONIVEL,
            ]);
        }

        return $espaco;
    }

    private function garantirQueUsuarioAindaNaoParticipa(EspacoCompartilhado $espaco, Usuario $usuario): void
    {
        $jaParticipa = $this->membroEspacoRepository->usuarioEhMembroAtivoDoEspaco(
            (int) $espaco->id_espaco_compartilhado,
            (int) $usuario->id_usuario
        );

        if ($jaParticipa) {
            throw ValidationException::withMessages([
                'convite' => 'Você já participa deste espaço compartilhado.',
            ]);
        }
    }

    private function registrarAuditoriaDeRespostaAoConvite(
        ConviteCompartilhamento $convite,
        Usuario $usuario,
        AcaoAuditoriaCompartilhada $acao,
    ): void {
        $this->auditoriaService->registrarAcaoNoEspaco(
            (int) $convite->id_espaco_compartilhado,
            (int) $usuario->id_usuario,
            $acao,
            AuditoriaEspacoCompartilhadoService::ENTIDADE_CONVITE,
            (int) $convite->id_convite_compartilhamento,
            null,
            ['email_destinatario' => $convite->email_destinatario],
        );
    }

    private function sortearCodigo(): string
    {
        $ultimoIndice = strlen(self::ALFABETO_CODIGO_CONVITE) - 1;
        $codigo = '';

        for ($posicao = 0; $posicao < self::TAMANHO_CODIGO_CONVITE; $posicao++) {
            $codigo .= self::ALFABETO_CODIGO_CONVITE[random_int(0, $ultimoIndice)];
        }

        return $codigo;
    }

    private function normalizarCodigo(string $codigo): string
    {
        return (string) preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($codigo));
    }

    private function calcularHashDoCodigo(string $codigo): string
    {
        return hash_hmac('sha256', $codigo, (string) config('app.key'));
    }

    private function formatarCodigoParaExibicao(string $codigo): string
    {
        return implode('-', str_split($codigo, self::TAMANHO_CODIGO_CONVITE / 2));
    }
}
