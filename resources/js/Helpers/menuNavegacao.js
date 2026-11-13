export function montarTrilhaDoMenuAtivo(itensMenu = []) {
    const itemAtivo = itensMenu.find((item) => item.ativo);

    if (!itemAtivo) {
        return [];
    }

    if (itemAtivo.tipo !== 'grupo') {
        return [{ rotulo: itemAtivo.rotulo }];
    }

    const filhoAtivo = (itemAtivo.filhos ?? []).find((filho) => filho.ativo);

    return filhoAtivo
        ? [{ rotulo: itemAtivo.rotulo }, { rotulo: filhoAtivo.rotulo, url: filhoAtivo.url }]
        : [{ rotulo: itemAtivo.rotulo }];
}
