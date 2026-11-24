import { computed, onBeforeUnmount, ref, toValue, watch } from 'vue';

const INTERVALO_ATUALIZACAO_MS = 1000;
const SEGUNDOS_POR_MINUTO = 60;

function calcularSegundosRestantes(dataLimiteIso) {
    if (!dataLimiteIso) {
        return 0;
    }

    const dataLimite = new Date(dataLimiteIso).getTime();

    if (Number.isNaN(dataLimite)) {
        return 0;
    }

    return Math.max(0, Math.floor((dataLimite - Date.now()) / 1000));
}

function formatarSegundosEmMinutos(totalSegundos) {
    const minutos = Math.floor(totalSegundos / SEGUNDOS_POR_MINUTO);
    const segundos = totalSegundos % SEGUNDOS_POR_MINUTO;

    return `${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
}

export function useContagemRegressiva(dataLimiteIso) {
    const segundosRestantes = ref(calcularSegundosRestantes(toValue(dataLimiteIso)));
    let temporizador = null;

    function pararContagem() {
        if (temporizador) {
            clearInterval(temporizador);
            temporizador = null;
        }
    }

    function atualizarSegundosRestantes() {
        segundosRestantes.value = calcularSegundosRestantes(toValue(dataLimiteIso));

        if (segundosRestantes.value === 0) {
            pararContagem();
        }
    }

    function iniciarContagem() {
        pararContagem();
        atualizarSegundosRestantes();

        if (segundosRestantes.value > 0) {
            temporizador = setInterval(atualizarSegundosRestantes, INTERVALO_ATUALIZACAO_MS);
        }
    }

    watch(() => toValue(dataLimiteIso), iniciarContagem, { immediate: true });

    onBeforeUnmount(pararContagem);

    return {
        segundosRestantes,
        tempoRestanteFormatado: computed(() => formatarSegundosEmMinutos(segundosRestantes.value)),
        expirou: computed(() => segundosRestantes.value === 0),
    };
}
