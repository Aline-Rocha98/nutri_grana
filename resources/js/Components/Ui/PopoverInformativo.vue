<script setup>
import { onBeforeUnmount, ref, useId } from 'vue';

const props = defineProps({
    titulo: {
        type: String,
        default: '',
    },
    rotulo: {
        type: String,
        default: 'Mais informações',
    },
    posicao: {
        type: String,
        default: 'bottom',
    },
    alinhamento: {
        type: String,
        default: 'inicio',
    },
    icone: {
        type: String,
        default: 'info',
    },
});

const CLASSES_POSICAO = {
    top: 'bottom-full mb-2',
    bottom: 'top-full mt-2',
};

const CLASSES_ALINHAMENTO = {
    inicio: 'left-0',
    centro: 'left-1/2 -translate-x-1/2',
    fim: 'right-0',
};

const idPopup = `popover-${useId()}`;
const elementoRaiz = ref(null);
const aberto = ref(false);
const fixadoPorClique = ref(false);

function aoClicarFora(evento) {
    if (elementoRaiz.value && !elementoRaiz.value.contains(evento.target)) {
        fecharPopover();
    }
}

function aoPressionarTecla(evento) {
    if (evento.key === 'Escape') {
        fecharPopover();
    }
}

function registrarOuvintesGlobais() {
    document.addEventListener('pointerdown', aoClicarFora);
    document.addEventListener('keydown', aoPressionarTecla);
}

function removerOuvintesGlobais() {
    document.removeEventListener('pointerdown', aoClicarFora);
    document.removeEventListener('keydown', aoPressionarTecla);
}

function abrirPopover() {
    if (aberto.value) {
        return;
    }

    aberto.value = true;
    registrarOuvintesGlobais();
}

function fecharPopover() {
    if (!aberto.value) {
        return;
    }

    aberto.value = false;
    fixadoPorClique.value = false;
    removerOuvintesGlobais();
}

function alternarPopoverPorClique() {
    if (aberto.value && fixadoPorClique.value) {
        fecharPopover();
        return;
    }

    fixadoPorClique.value = true;
    abrirPopover();
}

function fecharAoSairDoHover() {
    if (!fixadoPorClique.value) {
        fecharPopover();
    }
}

onBeforeUnmount(removerOuvintesGlobais);
</script>

<template>
    <span
        ref="elementoRaiz"
        class="relative inline-flex"
        @mouseenter="abrirPopover"
        @mouseleave="fecharAoSairDoHover"
    >
        <button
            type="button"
            class="inline-flex items-center justify-center rounded-full text-ng-ink-subtle transition hover:text-ng-brand focus:outline-none focus-visible:text-ng-brand"
            :aria-label="props.rotulo"
            :aria-expanded="aberto"
            :aria-describedby="aberto ? idPopup : undefined"
            @click.stop="alternarPopoverPorClique"
            @focus="abrirPopover"
            @blur="fecharAoSairDoHover"
        >
            <span class="material-symbols-outlined text-[16px] leading-none">{{ props.icone }}</span>
        </button>

        <span
            v-if="aberto"
            :id="idPopup"
            role="tooltip"
            class="ng-card absolute z-40 block w-72 max-w-[calc(100vw-2rem)] rounded-xl p-3 text-left text-xs font-normal leading-relaxed text-ng-ink-secondary shadow-xl"
            :class="[CLASSES_POSICAO[props.posicao] ?? CLASSES_POSICAO.bottom, CLASSES_ALINHAMENTO[props.alinhamento] ?? CLASSES_ALINHAMENTO.inicio]"
            @click.stop
        >
            <span v-if="props.titulo" class="mb-1.5 block text-[13px] font-semibold text-ng-ink">{{ props.titulo }}</span>
            <slot />
        </span>
    </span>
</template>
