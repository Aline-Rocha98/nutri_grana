<?php

namespace App\Repositories\EspacoCompartilhado;

use App\Enum\StatusConviteCompartilhamento;
use App\Enum\StatusEspacoCompartilhado;
use App\Models\EspacoCompartilhado\ConviteCompartilhamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ConviteCompartilhamentoRepository
{
    public function criarConvite(array $dados): ConviteCompartilhamento
    {
        return ConviteCompartilhamento::query()->create($dados);
    }

    public function atualizarConvite(ConviteCompartilhamento $convite, array $dados): ConviteCompartilhamento
    {
        $convite->update($dados);

        return $convite->refresh();
    }

    public function bloquearConviteParaAtualizacao(int $idConvite): ?ConviteCompartilhamento
    {
        return ConviteCompartilhamento::query()
            ->whereKey($idConvite)
            ->lockForUpdate()
            ->first();
    }

    public function listarConvitesPendentesRecebidosPeloUsuario(int $idUsuario): Collection
    {
        return ConviteCompartilhamento::query()
            ->with([
                'espaco:id_espaco_compartilhado,nome,status',
                'criador:id_usuario,nome',
            ])
            ->where('id_usuario_convidado', $idUsuario)
            ->where('status', StatusConviteCompartilhamento::Pendente)
            ->whereHas('espaco', fn (Builder $consulta) => $consulta->where('status', StatusEspacoCompartilhado::Ativo))
            ->orderByDesc('created_at')
            ->get();
    }

    public function listarConvitesPendentesDoEspaco(int $idEspaco): Collection
    {
        return ConviteCompartilhamento::query()
            ->with([
                'criador:id_usuario,nome',
                'convidado:id_usuario,nome,email',
            ])
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('status', StatusConviteCompartilhamento::Pendente)
            ->orderByDesc('created_at')
            ->get();
    }

    public function expirarConvitesPendentesDoUsuarioNoEspaco(int $idEspaco, int $idUsuarioConvidado): int
    {
        return ConviteCompartilhamento::query()
            ->where('id_espaco_compartilhado', $idEspaco)
            ->where('id_usuario_convidado', $idUsuarioConvidado)
            ->where('status', StatusConviteCompartilhamento::Pendente)
            ->update(['status' => StatusConviteCompartilhamento::Expirado->value]);
    }
}
