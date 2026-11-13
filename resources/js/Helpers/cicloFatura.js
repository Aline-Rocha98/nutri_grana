const DIAS_REFERENCIA_MES = 30;

export function calcularMelhorDiaCompra(diaFechamento) {
    return Number(diaFechamento) || 1;
}

export function calcularDiasAteVencimento(diaFechamento, diaVencimento) {
    const diferenca = Number(diaVencimento) - Number(diaFechamento);

    return diferenca > 0 ? diferenca : diferenca + DIAS_REFERENCIA_MES;
}
