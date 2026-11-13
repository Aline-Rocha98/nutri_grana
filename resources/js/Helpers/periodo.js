const NOMES_DOS_MESES = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
];

export function nomeDoMes(mes) {
    return NOMES_DOS_MESES[mes - 1] ?? '';
}

export function formatarDataIsoParaBrasileira(dataIso) {
    const [ano, mes, dia] = String(dataIso ?? '').slice(0, 10).split('-');

    return ano && mes && dia ? `${dia}/${mes}/${ano}` : '';
}

const NOMES_ABREVIADOS_DOS_MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
const MILISSEGUNDOS_POR_DIA = 24 * 60 * 60 * 1000;

function separarPartesDaDataIso(dataIso) {
    const [ano, mes, dia] = String(dataIso ?? '').slice(0, 10).split('-').map(Number);

    return { ano, mes, dia: dia || 1 };
}

function montarDataIso(ano, mes, dia) {
    return `${ano}-${String(mes).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
}

export function dataIsoEhValida(dataIso) {
    return /^\d{4}-\d{2}-\d{2}$/.test(String(dataIso ?? '').slice(0, 10));
}

export function quantidadeDeDiasNoMes(ano, mes) {
    return new Date(Date.UTC(ano, mes, 0)).getUTCDate();
}

export function somarMesesSemEstourar(dataIso, quantidadeMeses) {
    const { ano, mes, dia } = separarPartesDaDataIso(dataIso);
    const destino = navegarMes(ano, mes, quantidadeMeses);

    return montarDataIso(destino.ano, destino.mes, Math.min(dia, quantidadeDeDiasNoMes(destino.ano, destino.mes)));
}

export function somarDiasNaData(dataIso, quantidadeDias) {
    const { ano, mes, dia } = separarPartesDaDataIso(dataIso);
    const data = new Date(Date.UTC(ano, mes - 1, dia) + quantidadeDias * MILISSEGUNDOS_POR_DIA);

    return montarDataIso(data.getUTCFullYear(), data.getUTCMonth() + 1, data.getUTCDate());
}

export function ultimoDiaDoMes(mesIso) {
    const [ano, mes] = String(mesIso ?? '').split('-').map(Number);

    if (!ano || !mes) {
        return null;
    }

    return montarDataIso(ano, mes, quantidadeDeDiasNoMes(ano, mes));
}

export function formatarMesAbreviado(dataOuMesIso) {
    const [ano, mes] = String(dataOuMesIso ?? '').split('-').map(Number);

    return ano && mes ? `${NOMES_ABREVIADOS_DOS_MESES[mes - 1]}/${ano}` : '';
}

export function navegarMes(ano, mes, deslocamento) {
    const indiceAbsoluto = ano * 12 + (mes - 1) + deslocamento;

    return {
        ano: Math.floor(indiceAbsoluto / 12),
        mes: (indiceAbsoluto % 12) + 1,
    };
}
