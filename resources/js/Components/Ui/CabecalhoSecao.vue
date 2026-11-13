<script setup>
import { computed } from 'vue';
import { estiloDaVariante } from '@/Helpers/varianteCor';

const props = defineProps({
    titulo: {
        type: String,
        required: true,
    },
    subtitulo: {
        type: String,
        default: '',
    },
    tamanho: {
        type: String,
        default: 'secao',
    },
    icone: {
        type: String,
        default: '',
    },
    varianteIcone: {
        type: String,
        default: 'brand',
    },
});

const classeIcone = computed(() => estiloDaVariante(props.varianteIcone).icone);
</script>

<template>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <span v-if="icone" class="h-9 w-9 rounded-xl" :class="classeIcone">
                <span class="material-symbols-outlined text-[18px]">{{ icone }}</span>
            </span>
            <div class="min-w-0">
                <h2 v-if="tamanho === 'pagina'" class="text-xl font-bold text-ng-ink">{{ titulo }}</h2>
                <h3 v-else class="text-base font-semibold text-ng-ink">{{ titulo }}</h3>
                <p v-if="subtitulo" class="mt-0.5 text-xs text-ng-ink-muted">{{ subtitulo }}</p>
            </div>
        </div>
        <div v-if="$slots.acoes" class="flex shrink-0 items-center gap-2">
            <slot name="acoes" />
        </div>
    </div>
</template>
