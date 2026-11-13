const ESTILOS_POR_VARIANTE = {
    brand: {
        hex: '#1fa67e',
        texto: 'text-ng-brand',
        icone: 'ng-icone-destaque',
        selecionavelAtivo: 'ng-selecionavel--ativo',
    },
    red: {
        hex: '#f43f5e',
        texto: 'text-rose-500',
        icone: 'ng-icone-destaque ng-icone-destaque--red',
        selecionavelAtivo: 'ng-selecionavel--ativo-red',
    },
    sky: {
        hex: '#0ea5e9',
        texto: 'text-sky-500',
        icone: 'ng-icone-destaque ng-icone-destaque--sky',
        selecionavelAtivo: 'ng-selecionavel--ativo-sky',
    },
    amber: {
        hex: '#f59e0b',
        texto: 'text-amber-500',
        icone: 'ng-icone-destaque ng-icone-destaque--amber',
        selecionavelAtivo: 'ng-selecionavel--ativo-amber',
    },
    zinc: {
        hex: '#a1a1aa',
        texto: 'text-ng-ink',
        icone: 'ng-icone-destaque ng-icone-destaque--zinc',
        selecionavelAtivo: 'ng-selecionavel--ativo-zinc',
    },
};

export function estiloDaVariante(variante) {
    return ESTILOS_POR_VARIANTE[variante] ?? ESTILOS_POR_VARIANTE.brand;
}
