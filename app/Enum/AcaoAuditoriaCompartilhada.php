<?php

namespace App\Enum;

enum AcaoAuditoriaCompartilhada: string
{
    case EspacoCriado = 'espaco_criado';
    case EspacoAtualizado = 'espaco_atualizado';
    case EspacoArquivado = 'espaco_arquivado';
    case MembroEntrou = 'membro_entrou';
    case MembroRemovido = 'membro_removido';
    case MembroSaiu = 'membro_saiu';
    case AdministradorPromovido = 'administrador_promovido';
    case ConviteCriado = 'convite_criado';
    case ConviteAceito = 'convite_aceito';
    case ConviteRecusado = 'convite_recusado';
    case ConviteRevogado = 'convite_revogado';
    case ConviteBloqueado = 'convite_bloqueado';
    case LancamentoCriado = 'lancamento_criado';
    case LancamentoAtualizado = 'lancamento_atualizado';
    case LancamentoExcluido = 'lancamento_excluido';
    case LancamentoCancelado = 'lancamento_cancelado';
    case BaixaRealizada = 'baixa_realizada';
    case BaixaEstornada = 'baixa_estornada';
    case ResponsavelAlterado = 'responsavel_alterado';
    case RecorrenciaCriada = 'recorrencia_criada';
    case RecorrenciaAtualizada = 'recorrencia_atualizada';
    case RecorrenciaEncerrada = 'recorrencia_encerrada';

    public function rotulo(): string
    {
        return match ($this) {
            self::EspacoCriado => 'Espaço criado',
            self::EspacoAtualizado => 'Espaço atualizado',
            self::EspacoArquivado => 'Espaço arquivado',
            self::MembroEntrou => 'Membro entrou',
            self::MembroRemovido => 'Membro removido',
            self::MembroSaiu => 'Membro saiu',
            self::AdministradorPromovido => 'Administrador promovido',
            self::ConviteCriado => 'Convite criado',
            self::ConviteAceito => 'Convite aceito',
            self::ConviteRecusado => 'Convite recusado',
            self::ConviteRevogado => 'Convite revogado',
            self::ConviteBloqueado => 'Convite bloqueado por tentativas inválidas',
            self::LancamentoCriado => 'Lançamento criado',
            self::LancamentoAtualizado => 'Lançamento atualizado',
            self::LancamentoExcluido => 'Lançamento excluído',
            self::LancamentoCancelado => 'Lançamento cancelado',
            self::BaixaRealizada => 'Baixa realizada',
            self::BaixaEstornada => 'Baixa estornada',
            self::ResponsavelAlterado => 'Responsável alterado',
            self::RecorrenciaCriada => 'Recorrência criada',
            self::RecorrenciaAtualizada => 'Recorrência atualizada',
            self::RecorrenciaEncerrada => 'Recorrência encerrada',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::EspacoCriado, self::EspacoAtualizado, self::EspacoArquivado => 'home',
            self::MembroEntrou, self::AdministradorPromovido => 'person_add',
            self::MembroRemovido, self::MembroSaiu => 'person_remove',
            self::ConviteCriado, self::ConviteAceito, self::ConviteRecusado, self::ConviteRevogado, self::ConviteBloqueado => 'mail',
            self::LancamentoCriado => 'add_circle',
            self::LancamentoAtualizado => 'edit',
            self::LancamentoExcluido, self::LancamentoCancelado => 'delete',
            self::BaixaRealizada => 'check_circle',
            self::BaixaEstornada => 'undo',
            self::ResponsavelAlterado => 'swap_horiz',
            self::RecorrenciaCriada, self::RecorrenciaAtualizada, self::RecorrenciaEncerrada => 'autorenew',
        };
    }
}
