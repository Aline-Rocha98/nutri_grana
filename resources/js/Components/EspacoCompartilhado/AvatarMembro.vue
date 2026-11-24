<script setup>
import { computed } from 'vue';

const props = defineProps({
    nome: {
        type: String,
        default: '',
    },
    iniciais: {
        type: String,
        default: '?',
    },
    fotoUrl: {
        type: String,
        default: null,
    },
    tamanho: {
        type: String,
        default: 'md',
    },
    destacado: {
        type: Boolean,
        default: false,
    },
    comBorda: {
        type: Boolean,
        default: false,
    },
});

const CLASSES_POR_TAMANHO = {
    xs: 'h-6 w-6 text-[9px]',
    sm: 'h-8 w-8 text-[11px]',
    md: 'h-10 w-10 text-xs',
    lg: 'h-12 w-12 text-sm',
};

const classeTamanho = computed(() => CLASSES_POR_TAMANHO[props.tamanho] ?? CLASSES_POR_TAMANHO.md);
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full font-semibold uppercase"
        :class="[
            classeTamanho,
            destacado ? 'ng-icone-destaque' : 'border border-ng-line-strong bg-ng-card-muted text-ng-ink-secondary',
            comBorda ? 'ring-2 ring-[var(--ng-card)]' : '',
        ]"
        :title="nome || undefined"
    >
        <img v-if="fotoUrl" :src="fotoUrl" :alt="nome" class="h-full w-full object-cover">
        <template v-else>{{ iniciais }}</template>
    </span>
</template>
