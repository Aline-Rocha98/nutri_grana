<script setup>
import { computed } from 'vue';
import { estiloDaVariante } from '@/Helpers/varianteCor';

const props = defineProps({
    rotulo: {
        type: String,
        required: true,
    },
    valor: {
        type: String,
        default: '0,00',
    },
    sinal: {
        type: String,
        default: '',
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    variante: {
        type: String,
        default: 'brand',
    },
    selo: {
        type: String,
        default: '',
    },
});

const estilo = computed(() => estiloDaVariante(props.variante));
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3"
        :class="estilo.selecionavelAtivo"
    >
        <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider" :class="estilo.texto">{{ rotulo }}</p>
            <p class="mt-1 font-mono text-lg font-semibold tabular-nums text-ng-ink">
                {{ sinal }} {{ prefixoMoeda }} {{ valor }}
            </p>
        </div>

        <span
            v-if="selo"
            class="rounded-lg border px-2.5 py-1 font-mono text-[11px] font-semibold"
            :class="[estilo.selecionavelAtivo, estilo.texto]"
        >
            {{ selo }}
        </span>
    </div>
</template>
