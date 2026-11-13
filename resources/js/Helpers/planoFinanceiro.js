import { moedaParaNumero } from '@/Helpers/mascaraMoeda';

export const TIPO_CALCULO_PERCENTUAL = 'percentual';
export const TIPO_CALCULO_VALOR_FIXO = 'valor_fixo';
export const PERCENTUAL_TOTAL_DO_PLANO = 100;

let sequenciaRascunhoItemPlano = 0;

function arredondarCentavos(valor) {
    return Math.round(valor * 100) / 100;
}

function gerarChaveRascunho() {
    sequenciaRascunhoItemPlano += 1;

    return `rascunho-item-${sequenciaRascunhoItemPlano}`;
}

export function escolherProximaCorLivre(coresDisponiveis, coresEmUso) {
    const corLivre = coresDisponiveis.find((opcao) => !coresEmUso.includes(opcao.valor));

    if (corLivre) {
        return corLivre.valor;
    }

    return coresDisponiveis[coresEmUso.length % Math.max(coresDisponiveis.length, 1)]?.valor ?? null;
}

export function criarRascunhoItemPlano({ cor = null, idContaBancariaDestinoPadrao = null } = {}) {
    return {
        chave: gerarChaveRascunho(),
        id: null,
        nome: '',
        descricao: '',
        cor,
        tipo_calculo: TIPO_CALCULO_PERCENTUAL,
        valor_percentual: '',
        valor_fixo: '0,00',
        separar_do_saldo: false,
        id_conta_bancaria_destino: idContaBancariaDestinoPadrao,
        id_objetivo: null,
        id_alocacao: null,
    };
}

export function converterItemPlanoParaRascunho(item) {
    const ehPercentual = item.tipo_calculo === TIPO_CALCULO_PERCENTUAL;

    return {
        chave: `item-${item.id}`,
        id: item.id,
        nome: item.nome ?? '',
        descricao: item.descricao ?? '',
        cor: item.cor,
        tipo_calculo: item.tipo_calculo,
        valor_percentual: ehPercentual ? String(item.valor_numero ?? '') : '',
        valor_fixo: ehPercentual ? '0,00' : (item.valor ?? '0,00'),
        separar_do_saldo: Boolean(item.separar_do_saldo),
        id_conta_bancaria_destino: item.id_conta_bancaria_destino ?? null,
        id_objetivo: item.id_objetivo ?? null,
        id_alocacao: item.id_alocacao ?? null,
    };
}

export function obterValorConfiguradoDoRascunho(rascunho) {
    if (rascunho?.tipo_calculo === TIPO_CALCULO_PERCENTUAL) {
        return Number(String(rascunho.valor_percentual ?? '').replace(',', '.')) || 0;
    }

    return moedaParaNumero(rascunho?.valor_fixo ?? '0,00');
}

export function converterRascunhoParaItemCalculavel(rascunho, mapaCoresHexadecimais = {}) {
    return {
        id: rascunho.id ?? rascunho.chave,
        chave: rascunho.chave,
        nome: rascunho.nome,
        tipo_calculo: rascunho.tipo_calculo,
        valor_numero: obterValorConfiguradoDoRascunho(rascunho),
        separar_do_saldo: Boolean(rascunho.separar_do_saldo),
        cor_hexadecimal: mapaCoresHexadecimais[rascunho.cor] ?? null,
    };
}

export function montarDadosEnvioItemPlano(rascunho) {
    const ehPercentual = rascunho.tipo_calculo === TIPO_CALCULO_PERCENTUAL;
    const separarDoSaldo = Boolean(rascunho.separar_do_saldo);

    return {
        id: rascunho.id,
        nome: rascunho.nome,
        descricao: rascunho.descricao || null,
        cor: rascunho.cor,
        tipo_calculo: rascunho.tipo_calculo,
        valor: ehPercentual
            ? obterValorConfiguradoDoRascunho(rascunho) || null
            : rascunho.valor_fixo,
        separar_do_saldo: separarDoSaldo,
        id_conta_bancaria_destino: separarDoSaldo ? rascunho.id_conta_bancaria_destino : null,
        id_objetivo: separarDoSaldo ? rascunho.id_objetivo : null,
        id_alocacao: separarDoSaldo ? rascunho.id_alocacao : null,
    };
}

export function somarPercentualDoPlano(itens) {
    return arredondarCentavos((itens ?? [])
        .filter((item) => item.tipo_calculo === TIPO_CALCULO_PERCENTUAL)
        .reduce((total, item) => total + Number(item.valor_numero ?? 0), 0));
}

export function somarValorFixoDoPlano(itens) {
    return arredondarCentavos((itens ?? [])
        .filter((item) => item.tipo_calculo === TIPO_CALCULO_VALOR_FIXO)
        .reduce((total, item) => total + Number(item.valor_numero ?? 0), 0));
}

export function montarSegmentosDistribuicao(itens) {
    return (itens ?? [])
        .filter((item) => item.tipo_calculo === TIPO_CALCULO_PERCENTUAL && Number(item.valor_numero) > 0)
        .map((item) => ({
            chave: item.chave ?? item.id,
            percentual: Number(item.valor_numero),
            cor: item.cor_hexadecimal,
            rotulo: item.nome,
        }));
}

export function calcularValorDoItem(tipoCalculo, valorConfigurado, valorBase) {
    const base = Number(valorBase ?? 0);
    const configurado = Number(valorConfigurado ?? 0);

    if (!Number.isFinite(base) || !Number.isFinite(configurado) || base <= 0 || configurado <= 0) {
        return 0;
    }

    if (tipoCalculo === TIPO_CALCULO_PERCENTUAL) {
        return arredondarCentavos((base * Math.min(configurado, PERCENTUAL_TOTAL_DO_PLANO)) / PERCENTUAL_TOTAL_DO_PLANO);
    }

    return arredondarCentavos(Math.min(configurado, base));
}

export function distribuirValoresDoPlano(itens, valorBase) {
    let saldoRestante = arredondarCentavos(Math.max(0, Number(valorBase ?? 0)));

    return (itens ?? []).map((item) => {
        const valorCalculado = calcularValorDoItem(item.tipo_calculo, item.valor_numero, valorBase);

        if (!item.separar_do_saldo) {
            return { ...item, valor_calculado: valorCalculado, valor_separado: 0 };
        }

        const valorSeparado = arredondarCentavos(Math.min(valorCalculado, saldoRestante));
        saldoRestante = arredondarCentavos(saldoRestante - valorSeparado);

        return { ...item, valor_calculado: valorSeparado, valor_separado: valorSeparado };
    });
}

export function somarValoresSeparados(itens, valorBase) {
    return arredondarCentavos(
        distribuirValoresDoPlano(itens, valorBase)
            .reduce((total, item) => total + item.valor_separado, 0),
    );
}

export function calcularCapacidadeFinanceiraDoPlano(itens, valorRenda) {
    const renda = arredondarCentavos(Math.max(0, Number(valorRenda ?? 0)));
    const totalSeparado = somarValoresSeparados(itens, renda);

    return {
        renda,
        total_destinado: totalSeparado,
        capacidade: arredondarCentavos(renda - totalSeparado),
        percentual_destinado: renda > 0 ? Math.round((totalSeparado / renda) * 1000) / 10 : 0,
    };
}
