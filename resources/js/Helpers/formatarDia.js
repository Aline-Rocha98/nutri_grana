/**
 * Formata dia do mês no padrão pt-BR (ex.: 5 → "05").
 */
export function formatarDia(dia) {
    const numero = Number(dia);

    if (!Number.isFinite(numero) || numero < 1 || numero > 31) {
        return '';
    }

    return String(numero).padStart(2, '0');
}

/**
 * Opções 1–31 para selects de dia de fechamento/vencimento.
 */
export function opcoesDiasDoMes() {
    return Array.from({ length: 31 }, (_, indice) => {
        const dia = indice + 1;

        return {
            valor: dia,
            rotulo: formatarDia(dia),
        };
    });
}
