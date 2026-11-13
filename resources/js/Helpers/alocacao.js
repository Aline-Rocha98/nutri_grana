import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';

export const COR_ALOCACAO = '#0ea5e9';

export function obterDataDeHojeIso() {
    const hoje = new Date();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');

    return `${hoje.getFullYear()}-${mes}-${dia}`;
}

export function listarContasComSaldoDaAlocacao(alocacao) {
    return (alocacao?.saldos_por_conta ?? []).filter((saldoConta) => Number(saldoConta.saldo) > 0);
}

export function montarOpcoesContasComSaldo(alocacao) {
    return listarContasComSaldoDaAlocacao(alocacao).map((saldoConta) => ({
        valor: saldoConta.id_conta_bancaria,
        rotulo: `${saldoConta.nome} · R$ ${saldoConta.saldo_formatado}`,
    }));
}

export function buscarSaldoDaAlocacaoNaConta(alocacao, idContaBancaria) {
    const saldoConta = (alocacao?.saldos_por_conta ?? [])
        .find((item) => String(item.id_conta_bancaria) === String(idContaBancaria));

    return Number(saldoConta?.saldo ?? 0);
}

export function montarAtalhoValorTotal(rotulo, saldo) {
    return saldo > 0 ? [{ rotulo, valor: formatarNumeroParaMoeda(saldo) }] : [];
}

export function converterMovimentacaoAlocacaoParaLinha(movimentacao) {
    return {
        chave: movimentacao.id,
        descricao: movimentacao.descricao,
        subtitulo: [
            movimentacao.conta_bancaria_nome,
            movimentacao.data_movimentacao_formatada,
        ].filter(Boolean).join(' · '),
        valor: movimentacao.valor,
        icone: movimentacao.eh_entrada ? 'savings' : 'south_west',
        cor: COR_ALOCACAO,
        positivo: movimentacao.eh_entrada,
        negativo: !movimentacao.eh_entrada,
    };
}
