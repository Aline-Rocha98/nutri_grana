export const PERCENTUAL_ALERTA_ORCAMENTO = 80;

export const GRUPO_ORCAMENTO_NO_LIMITE = 'no_limite';
export const GRUPO_ORCAMENTO_ATENCAO = 'atencao';
export const GRUPO_ORCAMENTO_ULTRAPASSADO = 'ultrapassado';

export const GRUPO_COTACAO_EM_ANALISE = 'em_analise';
export const GRUPO_COTACAO_APROVADA = 'aprovada';
export const GRUPO_COTACAO_ENCERRADA = 'encerrada';

const GRUPO_POR_STATUS_COTACAO = {
    em_analise: GRUPO_COTACAO_EM_ANALISE,
    aprovada: GRUPO_COTACAO_APROVADA,
    concluida: GRUPO_COTACAO_APROVADA,
    recusada: GRUPO_COTACAO_ENCERRADA,
    expirada: GRUPO_COTACAO_ENCERRADA,
};

export function grupoDoStatusCotacao(status) {
    return GRUPO_POR_STATUS_COTACAO[status] ?? null;
}

export function situacaoDoOrcamento(orcamento) {
    if (orcamento?.ultrapassado) {
        return { grupo: GRUPO_ORCAMENTO_ULTRAPASSADO, rotulo: 'Ultrapassado', variante: 'red', cor: '#f43f5e' };
    }

    if (Number(orcamento?.percentual ?? 0) >= PERCENTUAL_ALERTA_ORCAMENTO) {
        return { grupo: GRUPO_ORCAMENTO_ATENCAO, rotulo: 'Atenção', variante: 'amber', cor: '#f59e0b' };
    }

    return { grupo: GRUPO_ORCAMENTO_NO_LIMITE, rotulo: 'No limite', variante: 'brand', cor: '#1fa67e' };
}