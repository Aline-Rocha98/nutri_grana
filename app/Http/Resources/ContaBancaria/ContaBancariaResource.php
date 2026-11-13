<?php

namespace App\Http\Resources\ContaBancaria;

use App\Data\BancosSugeridos;
use App\Enum\AcabamentoVisual;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContaBancariaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $acabamento = $this->acabamento ?? AcabamentoVisual::padrao();
        $saldoAtual = (float) ($this->saldo_atual ?? $this->saldo_inicial);
        $saldoGuardado = (float) ($this->saldo_guardado ?? 0);
        $saldoDisponivel = (float) ($this->saldo_disponivel ?? $saldoAtual);

        return [
            'id' => $this->id_conta_bancaria,
            'chave' => $this->getRouteKey(),
            'nome' => $this->nome,
            'saldo_inicial' => number_format((float) $this->saldo_inicial, 2, ',', '.'),
            'saldo_inicial_numero' => (float) $this->saldo_inicial,
            'saldo_atual' => number_format($saldoAtual, 2, ',', '.'),
            'saldo_atual_numero' => $saldoAtual,
            'saldo_guardado' => number_format($saldoGuardado, 2, ',', '.'),
            'saldo_guardado_numero' => $saldoGuardado,
            'saldo_disponivel' => number_format($saldoDisponivel, 2, ',', '.'),
            'saldo_disponivel_numero' => $saldoDisponivel,
            'tipo' => $this->tipo?->value,
            'tipo_rotulo' => $this->tipo?->rotulo(),
            'acabamento' => $acabamento->value,
            'acabamento_cor' => $acabamento->corDestaque(),
            'arquivada' => $this->arquivada?->value,
            'padrao_desconto' => $this->padrao_desconto?->value,
            'exibir_resumo' => $this->exibir_resumo?->value,
            'total_lancamentos' => (int) ($this->total_lancamentos ?? 0),
            'logo' => BancosSugeridos::logoPorNome($this->nome),
            'url_atualizar' => route('contas-bancarias.atualizar', $this->resource),
            'url_arquivar' => route('contas-bancarias.arquivar', $this->resource),
            'url_definir_padrao_desconto' => route('contas-bancarias.definir-padrao-desconto', $this->resource),
            'url_excluir' => route('contas-bancarias.excluir', $this->resource),
            'url_alocacoes' => route('alocacoes.index'),
        ];
    }
}
