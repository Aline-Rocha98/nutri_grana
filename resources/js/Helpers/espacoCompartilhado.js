export const STATUS_LANCAMENTO_COMPARTILHADO_PENDENTE = 'pendente';
export const STATUS_LANCAMENTO_COMPARTILHADO_PAGO = 'pago';

const TAMANHO_BLOCO_CODIGO_CONVITE = 4;
const TAMANHO_CODIGO_CONVITE = 8;
const TAMANHO_MAXIMO_DESCRICAO = 255;

export function varianteStatusLancamentoCompartilhado(lancamento) {
    if (lancamento?.esta_pago) {
        return 'brand';
    }

    if (lancamento?.esta_vencido) {
        return 'red';
    }

    return lancamento?.status === STATUS_LANCAMENTO_COMPARTILHADO_PENDENTE ? 'orange' : 'zinc';
}

export function iconeStatusLancamentoCompartilhado(lancamento) {
    if (lancamento?.esta_pago) {
        return 'check_circle';
    }

    return lancamento?.esta_vencido ? 'error' : 'schedule';
}

export function montarDescricaoDoLancamentoPessoal(nomeEspaco, descricaoLancamento) {
    return `[${nomeEspaco}] ${descricaoLancamento}`.slice(0, TAMANHO_MAXIMO_DESCRICAO);
}

export function formatarCodigoConvite(codigoDigitado) {
    const codigo = String(codigoDigitado ?? '')
        .toUpperCase()
        .replace(/[^A-Z0-9]/g, '')
        .slice(0, TAMANHO_CODIGO_CONVITE);

    if (codigo.length <= TAMANHO_BLOCO_CODIGO_CONVITE) {
        return codigo;
    }

    return `${codigo.slice(0, TAMANHO_BLOCO_CODIGO_CONVITE)}-${codigo.slice(TAMANHO_BLOCO_CODIGO_CONVITE)}`;
}
