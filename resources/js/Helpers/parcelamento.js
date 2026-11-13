import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';
import { somarMesesSemEstourar } from '@/Helpers/periodo';

const CENTAVOS_POR_REAL = 100;

export function dividirValorEmParcelas(valorTotal, quantidadeParcelas) {
    const quantidade = Math.max(1, Math.floor(Number(quantidadeParcelas) || 1));
    const totalEmCentavos = Math.round(Number(valorTotal || 0) * CENTAVOS_POR_REAL);
    const parcelaEmCentavos = Math.round(totalEmCentavos / quantidade);
    const valoresEmCentavos = Array.from({ length: quantidade }, () => parcelaEmCentavos);

    valoresEmCentavos[quantidade - 1] = totalEmCentavos - parcelaEmCentavos * (quantidade - 1);

    return valoresEmCentavos.map((centavos) => centavos / CENTAVOS_POR_REAL);
}

export function montarParcelasPrevistas(valorTotal, quantidadeParcelas, dataInicialIso = null) {
    const valores = dividirValorEmParcelas(valorTotal, quantidadeParcelas);

    return valores.map((valor, indice) => ({
        numero: indice + 1,
        total: valores.length,
        dataIso: dataInicialIso ? somarMesesSemEstourar(dataInicialIso, indice) : null,
        valor,
    }));
}

export function agruparParcelasPorValor(parcelas) {
    return parcelas.reduce((grupos, parcela) => {
        const ultimoGrupo = grupos[grupos.length - 1];

        if (ultimoGrupo && ultimoGrupo.valor === parcela.valor) {
            ultimoGrupo.quantidade += 1;
        } else {
            grupos.push({ quantidade: 1, valor: parcela.valor });
        }

        return grupos;
    }, []);
}

export function descreverParcelaPrincipal(parcelas) {
    if (!parcelas.length) {
        return '';
    }

    return `${parcelas.length}x de R$ ${formatarNumeroParaMoeda(parcelas[0].valor)}`;
}

export function descreverParcelamento(parcelas) {
    return agruparParcelasPorValor(parcelas)
        .map((grupo) => `${grupo.quantidade}x de R$ ${formatarNumeroParaMoeda(grupo.valor)}`)
        .join(' + ');
}
