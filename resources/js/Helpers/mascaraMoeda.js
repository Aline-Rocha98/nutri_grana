export function somenteDigitos(valor) {
    return String(valor ?? '').replace(/\D/g, '');
}

export function aplicarMascaraMoeda(valor) {
    const digitos = somenteDigitos(valor);

    if (!digitos) {
        return '0,00';
    }

    const centavos = Number(digitos);

    if (!Number.isFinite(centavos)) {
        return '0,00';
    }

    return (centavos / 100).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export function moedaParaNumero(valor) {
    if (valor === null || valor === undefined || valor === '') {
        return 0;
    }

    const normalizado = String(valor)
        .replace(/R\$\s?/g, '')
        .replace(/\./g, '')
        .replace(',', '.')
        .trim();

    const numero = Number(normalizado);

    return Number.isFinite(numero) ? numero : 0;
}

export function formatarNumeroParaMoeda(valor) {
    const numero = Number(valor ?? 0);

    if (!Number.isFinite(numero)) {
        return '0,00';
    }

    return numero.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export function aoDigitarMoeda(evento) {
    const formatado = aplicarMascaraMoeda(evento.target.value);
    evento.target.value = formatado;

    return formatado;
}
