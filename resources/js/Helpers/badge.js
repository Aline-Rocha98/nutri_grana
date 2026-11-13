export const BADGE = {
    emerald: 'ng-badge ng-badge--emerald',
    brand: 'ng-badge ng-badge--brand',
    sky: 'ng-badge ng-badge--sky',
    blue: 'ng-badge ng-badge--blue',
    indigo: 'ng-badge ng-badge--indigo',
    amber: 'ng-badge ng-badge--amber',
    orange: 'ng-badge ng-badge--orange',
    red: 'ng-badge ng-badge--red',
    zinc: 'ng-badge ng-badge--zinc',
};

export function varianteSituacaoObjetivo(situacao) {
    return {
        adiantado: 'emerald',
        em_dia: 'sky',
        atrasado: 'amber',
        concluido: 'brand',
        vencido: 'red',
    }[situacao] ?? 'zinc';
}

export function badgeSituacaoObjetivo(situacao) {
    return BADGE[varianteSituacaoObjetivo(situacao)];
}

export function varianteStatusCotacao(status) {
    return {
        em_analise: 'blue',
        aprovada: 'emerald',
        recusada: 'red',
        expirada: 'zinc',
        concluida: 'indigo',
    }[status] ?? 'zinc';
}

export function varianteSituacaoFatura(situacao) {
    return {
        aberta: 'brand',
        fechada: 'amber',
        paga: 'zinc',
    }[situacao] ?? 'zinc';
}

export function badgeSituacaoFatura(situacao) {
    return BADGE[varianteSituacaoFatura(situacao)];
}

export function varianteSituacaoLancamento(situacao) {
    return {
        pendente: 'orange',
        previsto: 'orange',
        pago: 'brand',
        recebido: 'brand',
    }[situacao] ?? 'zinc';
}