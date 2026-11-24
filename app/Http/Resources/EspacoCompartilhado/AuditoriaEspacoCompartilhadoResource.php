<?php

namespace App\Http\Resources\EspacoCompartilhado;

use App\Support\Data\IniciaisNome;
use App\Support\Data\ValorMonetario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditoriaEspacoCompartilhadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $usuario = $this->relationLoaded('usuario') ? $this->usuario : null;

        return [
            'id' => (int) $this->id_auditoria,
            'acao' => $this->acao?->value,
            'acao_rotulo' => $this->acao?->rotulo(),
            'icone' => $this->acao?->icone(),
            'usuario_nome' => $usuario?->nome ?? 'Ex-membro',
            'usuario_iniciais' => IniciaisNome::extrairIniciais($usuario?->nome, '?'),
            'usuario_foto_url' => $usuario?->foto_url,
            'detalhe' => $this->montarDetalhe(),
            'data_formatada' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function montarDetalhe(): string
    {
        $dadosNovos = $this->dados_novos ?? [];
        $dadosAnteriores = $this->dados_anteriores ?? [];
        $partes = [];

        $descricao = $dadosNovos['descricao'] ?? $dadosAnteriores['descricao'] ?? $dadosNovos['nome'] ?? $dadosAnteriores['nome'] ?? null;

        if ($descricao) {
            $partes[] = $descricao;
        }

        if (isset($dadosNovos['email_destinatario'])) {
            $partes[] = $dadosNovos['email_destinatario'];
        }

        $valorAnterior = $dadosAnteriores['valor'] ?? null;
        $valorNovo = $dadosNovos['valor'] ?? null;

        if ($valorAnterior !== null && $valorNovo !== null && $valorAnterior !== $valorNovo) {
            $partes[] = sprintf(
                'R$ %s → R$ %s',
                ValorMonetario::formatarCentavosEmReais(ValorMonetario::converterParaCentavos((string) $valorAnterior)),
                ValorMonetario::formatarCentavosEmReais(ValorMonetario::converterParaCentavos((string) $valorNovo))
            );
        } elseif ($valorNovo !== null || $valorAnterior !== null) {
            $partes[] = 'R$ '.ValorMonetario::formatarCentavosEmReais(
                ValorMonetario::converterParaCentavos((string) ($valorNovo ?? $valorAnterior))
            );
        }

        if (isset($dadosNovos['responsavel_nome'])) {
            $partes[] = 'responsável: '.$dadosNovos['responsavel_nome'];
        }

        return implode(' · ', $partes);
    }
}
