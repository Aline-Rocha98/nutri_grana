export const TIPO_CATEGORIA_ENTRADA = 'entrada';
export const TIPO_CATEGORIA_SAIDA = 'saida';

const APRESENTACAO_POR_TIPO_CATEGORIA = {
    [TIPO_CATEGORIA_ENTRADA]: { singular: 'Entrada', plural: 'Entradas', icone: 'north_east' },
    [TIPO_CATEGORIA_SAIDA]: { singular: 'Despesa', plural: 'Despesas', icone: 'south_west' },
};

function apresentacaoDoTipoCategoria(tipo) {
    return APRESENTACAO_POR_TIPO_CATEGORIA[tipo] ?? APRESENTACAO_POR_TIPO_CATEGORIA[TIPO_CATEGORIA_SAIDA];
}

export function rotuloTipoCategoria(tipo, { plural = false } = {}) {
    const apresentacao = apresentacaoDoTipoCategoria(tipo);

    return plural ? apresentacao.plural : apresentacao.singular;
}

export function iconeTipoCategoria(tipo) {
    return apresentacaoDoTipoCategoria(tipo).icone;
}
