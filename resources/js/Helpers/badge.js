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

export function badgeSituacaoObjetivo(situacao) {
    return {
        adiantado: BADGE.emerald,
        em_dia: BADGE.sky,
        atrasado: BADGE.amber,
        concluido: BADGE.brand,
        vencido: BADGE.red,
    }[situacao] ?? BADGE.zinc;
}

export function badgeStatusCotacao(status) {
    return {
        em_analise: BADGE.blue,
        aprovada: BADGE.emerald,
        recusada: BADGE.red,
        expirada: BADGE.zinc,
        concluida: BADGE.indigo,
    }[status] ?? BADGE.zinc;
}

export function badgeSituacaoLancamento(situacao) {
    if (situacao === 'pendente' || situacao === 'previsto') {
        return BADGE.orange;
    }

    if (situacao === 'pago' || situacao === 'recebido') {
        return BADGE.brand;
    }

    return BADGE.zinc;
}