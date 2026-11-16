<?php

namespace App\Http\Resources\EspacoCompartilhado;

use App\Models\EspacoCompartilhado\EspacoCompartilhado;
use App\Support\Data\IniciaisNome;
use App\Support\Data\ValorMonetario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LancamentoCompartilhadoResource extends JsonResource
{
    private int $idUsuarioAtual = 0;

    private bool $usuarioEhAdministrador = false;

    public static function colecaoComPermissoesDoUsuario(Collection $lancamentos, int $idUsuarioAtual, bool $usuarioEhAdministrador): array
    {
        return $lancamentos
            ->map(fn ($lancamento) => (new self($lancamento))
                ->definirPermissoesDoUsuario($idUsuarioAtual, $usuarioEhAdministrador)
                ->resolve())
            ->values()
            ->all();
    }

    public function definirPermissoesDoUsuario(int $idUsuarioAtual, bool $usuarioEhAdministrador): self
    {
        $this->idUsuarioAtual = $idUsuarioAtual;
        $this->usuarioEhAdministrador = $usuarioEhAdministrador;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $responsavel = $this->relationLoaded('responsavel') ? $this->responsavel : null;
        $espaco = $this->relationLoaded('espaco') ? $this->espaco : null;
        $categoria = $this->relationLoaded('categoria') ? $this->categoria : null;
        $categoriaPai = $categoria?->relationLoaded('pai') ? $categoria->pai : null;
        $ehResponsavel = $this->ehResponsavel($this->idUsuarioAtual);
        $podeEditar = (int) $this->criado_por === $this->idUsuarioAtual || $ehResponsavel || $this->usuarioEhAdministrador;
        $estaVencido = $this->estaPendente() && $this->data_vencimento?->lt(Carbon::today());

        return [
            'id' => (int) $this->id_lancamento_compartilhado,
            'chave' => $this->getRouteKey(),
            'descricao' => $this->descricao,
            'valor' => ValorMonetario::formatarCentavosEmReais(ValorMonetario::converterParaCentavos((string) $this->valor)),
            'data_vencimento' => $this->data_vencimento?->format('Y-m-d'),
            'data_vencimento_formatada' => $this->data_vencimento?->format('d/m/Y'),
            'data_pagamento_formatada' => $this->data_pagamento?->format('d/m/Y'),
            'status' => $this->status?->value,
            'status_rotulo' => $estaVencido ? 'Vencido' : $this->status?->rotulo(),
            'esta_pago' => $this->estaPago(),
            'esta_vencido' => (bool) $estaVencido,
            'versao' => (int) $this->versao,
            'observacao' => $this->observacao,
            'eh_recorrente' => $this->pertenceARecorrencia(),
            'id_usuario_responsavel' => $this->id_usuario_responsavel ? (int) $this->id_usuario_responsavel : null,
            'id_categoria' => $this->id_categoria ? (int) $this->id_categoria : null,
            'id_categoria_principal' => $categoriaPai
                ? (int) $categoriaPai->id_categoria
                : ($categoria ? (int) $categoria->id_categoria : null),
            'id_subcategoria' => $categoriaPai ? (int) $categoria->id_categoria : null,
            'categoria_nome' => $categoria?->nome,
            'categoria_icone' => $categoria?->icone ?? $categoriaPai?->icone,
            'categoria_cor' => $categoria?->cor ?? $categoriaPai?->cor,
            'responsavel' => $responsavel ? [
                'id_usuario' => (int) $responsavel->id_usuario,
                'nome' => $responsavel->nome,
                'iniciais' => IniciaisNome::extrairIniciais($responsavel->nome, '?'),
                'foto_url' => $responsavel->foto_url,
            ] : null,
            'eh_responsavel_usuario_atual' => $ehResponsavel,
            'pode_editar' => $podeEditar,
            'pode_excluir' => $podeEditar,
            'url_atualizar' => $this->montarUrl($espaco, 'compartilhados.lancamentos.atualizar'),
            'url_excluir' => $this->montarUrl($espaco, 'compartilhados.lancamentos.excluir'),
            'url_baixa' => $this->montarUrl($espaco, 'compartilhados.lancamentos.dar-baixa'),
            'url_estornar_baixa' => $this->montarUrl($espaco, 'compartilhados.lancamentos.estornar-baixa'),
            'url_assumir' => $this->montarUrl($espaco, 'compartilhados.lancamentos.assumir'),
        ];
    }

    private function montarUrl(?EspacoCompartilhado $espaco, string $nomeRota): ?string
    {
        if (! $espaco) {
            return null;
        }

        return route($nomeRota, [
            'espacoCompartilhado' => $espaco,
            'lancamentoCompartilhado' => $this->resource,
        ]);
    }
}
