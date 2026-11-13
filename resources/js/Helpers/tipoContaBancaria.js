const ICONES_POR_TIPO_CONTA = {
    corrente: 'account_balance',
    poupanca: 'savings',
    investimento: 'trending_up',
};

export function iconeTipoContaBancaria(tipo) {
    return ICONES_POR_TIPO_CONTA[tipo] ?? ICONES_POR_TIPO_CONTA.corrente;
}
