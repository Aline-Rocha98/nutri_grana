<script setup>
import { computed } from 'vue';

const props = defineProps({
    percentual: {
        type: Number,
        default: 0,
    },
    percentualProjetado: {
        type: Number,
        default: null,
    },
    cor: {
        type: String,
        default: '#1fa67e',
    },
    ultrapassado: {
        type: Boolean,
        default: false,
    },
    altura: {
        type: String,
        default: 'h-1.5',
    },
});

function limitarPercentual(valor) {
    return Math.min(100, Math.max(0, valor));
}

const estiloPreenchimento = computed(() => (props.ultrapassado
    ? { width: `${limitarPercentual(props.percentual)}%` }
    : { width: `${limitarPercentual(props.percentual)}%`, backgroundColor: props.cor }));

const exibirProjecao = computed(
    () => props.percentualProjetado !== null && props.percentualProjetado > props.percentual,
);

const estiloProjecao = computed(() => ({
    width: `${limitarPercentual(props.percentualProjetado ?? 0)}%`,
    backgroundColor: props.cor,
}));
</script>

<template>
    <div class="relative w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10" :class="altura">
        <div
            v-if="exibirProjecao"
            class="absolute inset-y-0 left-0 rounded-full opacity-30 transition-all duration-500 ease-smooth"
            :style="estiloProjecao"
        />
        <div
            class="relative h-full rounded-full transition-all duration-500 ease-smooth"
            :class="ultrapassado ? 'bg-red-500' : ''"
            :style="estiloPreenchimento"
        />
    </div>
</template>
