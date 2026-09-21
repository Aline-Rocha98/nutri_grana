import { computed, onMounted, ref, watch } from 'vue';

export const CHAVE_TEMA = 'nutrigrana.tema';
export const TEMA_ESCURO = 'dark';
export const TEMA_CLARO = 'light';

const tema = ref(TEMA_ESCURO);
let iniciado = false;

function lerTemaSalvo() {
    try {
        const salvo = localStorage.getItem(CHAVE_TEMA);
        if (salvo === TEMA_CLARO || salvo === TEMA_ESCURO) {
            return salvo;
        }
    } catch {
        // localStorage indisponível
    }

    return TEMA_ESCURO;
}

function aplicarNoDocumento(valor) {
    const raiz = document.documentElement;
    const escuro = valor === TEMA_ESCURO;

    raiz.classList.toggle('dark', escuro);
    raiz.style.colorScheme = escuro ? 'dark' : 'light';
    raiz.dataset.tema = valor;
}

export function inicializarTema() {
    if (iniciado || typeof document === 'undefined') {
        return tema;
    }

    iniciado = true;
    tema.value = lerTemaSalvo();
    aplicarNoDocumento(tema.value);

    watch(tema, (valor) => {
        aplicarNoDocumento(valor);
        try {
            localStorage.setItem(CHAVE_TEMA, valor);
        } catch {
            // ignore
        }
    });

    return tema;
}

export function useTema() {
    onMounted(() => {
        inicializarTema();
    });

    const escuro = computed(() => tema.value === TEMA_ESCURO);

    function definirTema(valor) {
        if (valor !== TEMA_CLARO && valor !== TEMA_ESCURO) {
            return;
        }
        tema.value = valor;
    }

    function alternarTema() {
        definirTema(escuro.value ? TEMA_CLARO : TEMA_ESCURO);
    }

    return {
        tema,
        escuro,
        definirTema,
        alternarTema,
        TEMA_ESCURO,
        TEMA_CLARO,
    };
}
