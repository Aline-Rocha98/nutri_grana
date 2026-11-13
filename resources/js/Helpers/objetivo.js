export const GRUPO_OBJETIVO_EM_DIA = 'em_dia';
export const GRUPO_OBJETIVO_ATRASADO = 'atrasado';
export const GRUPO_OBJETIVO_CONCLUIDO = 'concluido';

const GRUPO_POR_SITUACAO_OBJETIVO = {
    em_dia: GRUPO_OBJETIVO_EM_DIA,
    adiantado: GRUPO_OBJETIVO_EM_DIA,
    atrasado: GRUPO_OBJETIVO_ATRASADO,
    vencido: GRUPO_OBJETIVO_ATRASADO,
    concluido: GRUPO_OBJETIVO_CONCLUIDO,
};

export function grupoDaSituacaoObjetivo(situacao) {
    return GRUPO_POR_SITUACAO_OBJETIVO[situacao] ?? null;
}

function converterDataIsoParaLocal(dataIso) {
    const [ano, mes, dia] = String(dataIso).split('-').map(Number);

    if (!ano || !mes || !dia) {
        return null;
    }

    return new Date(ano, mes - 1, dia);
}

export function calcularMesesRestantes(dataLimiteIso, hoje = new Date()) {
    const dataLimite = converterDataIsoParaLocal(dataLimiteIso);
    const inicioHoje = new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate());

    if (!dataLimite || inicioHoje >= dataLimite) {
        return 1;
    }

    let meses = (dataLimite.getFullYear() - inicioHoje.getFullYear()) * 12
        + (dataLimite.getMonth() - inicioHoje.getMonth());

    if (dataLimite.getDate() < inicioHoje.getDate()) {
        meses -= 1;
    }

    return Math.max(1, meses);
}

export function calcularPercentualDaMeta(valorGuardado, valorMeta) {
    if (valorMeta <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((valorGuardado / valorMeta) * 1000) / 10);
}

export function calcularDepositoMensalSugerido(valorMeta, valorGuardado, dataLimiteIso) {
    const valorFaltante = Math.max(0, valorMeta - valorGuardado);

    if (valorFaltante <= 0) {
        return 0;
    }

    return Math.round((valorFaltante / calcularMesesRestantes(dataLimiteIso)) * 100) / 100;
}
