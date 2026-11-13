import { somarDiasNaData, somarMesesSemEstourar } from '@/Helpers/periodo';

export const TERMINO_SEM_TERMINO = 'sem_termino';
export const TERMINO_POR_QUANTIDADE = 'quantidade';
export const TERMINO_ATE_MES = 'ate_mes';

export const FREQUENCIA_MENSAL = 'mensal';
export const FREQUENCIA_SEMANAL = 'semanal';
export const FREQUENCIA_ANUAL = 'anual';

export const MINIMO_OCORRENCIAS = 2;
export const MAXIMO_OCORRENCIAS = 120;

const DIAS_POR_SEMANA = 7;
const MESES_POR_ANO = 12;
const LIMITE_SEGURANCA_CONTAGEM = 1000;

export const OPCOES_TERMINO_RECORRENCIA = [
    { valor: TERMINO_SEM_TERMINO, rotulo: 'Sem término', icone: 'all_inclusive' },
    { valor: TERMINO_POR_QUANTIDADE, rotulo: 'Quantidade', icone: 'tag' },
    { valor: TERMINO_ATE_MES, rotulo: 'Até o mês', icone: 'event' },
];

export function avancarDataPelaFrequencia(dataIso, frequencia) {
    if (frequencia === FREQUENCIA_SEMANAL) {
        return somarDiasNaData(dataIso, DIAS_POR_SEMANA);
    }

    if (frequencia === FREQUENCIA_ANUAL) {
        return somarMesesSemEstourar(dataIso, MESES_POR_ANO);
    }

    return somarMesesSemEstourar(dataIso, 1);
}

export function calcularDataDaUltimaOcorrencia(dataInicialIso, frequencia, quantidadeOcorrencias) {
    let dataDaOcorrencia = String(dataInicialIso).slice(0, 10);

    for (let numeroOcorrencia = 2; numeroOcorrencia <= quantidadeOcorrencias; numeroOcorrencia += 1) {
        dataDaOcorrencia = avancarDataPelaFrequencia(dataDaOcorrencia, frequencia);
    }

    return dataDaOcorrencia;
}

export function contarOcorrenciasAteData(dataInicialIso, frequencia, dataFinalIso) {
    let dataDaOcorrencia = String(dataInicialIso).slice(0, 10);
    let quantidade = 0;

    while (dataDaOcorrencia <= dataFinalIso && quantidade < LIMITE_SEGURANCA_CONTAGEM) {
        quantidade += 1;
        dataDaOcorrencia = avancarDataPelaFrequencia(dataDaOcorrencia, frequencia);
    }

    return quantidade;
}

export function limitarQuantidadeOcorrencias(quantidade) {
    const numero = Math.floor(Number(quantidade) || MINIMO_OCORRENCIAS);

    return Math.min(MAXIMO_OCORRENCIAS, Math.max(MINIMO_OCORRENCIAS, numero));
}
