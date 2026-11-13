<script setup>
import BarraProgresso from '@/Components/BarraProgresso.vue';

defineProps({
    rotulo: {
        type: String,
        default: '',
    },
    valorAtual: {
        type: String,
        default: '0,00',
    },
    valorMeta: {
        type: String,
        default: '0,00',
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
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
    altura: {
        type: String,
        default: 'h-2',
    },
});
</script>

<template>
    <div>
        <div class="flex items-center justify-between gap-3 text-xs">
            <span class="text-ng-ink-muted">{{ rotulo }}</span>
            <span class="font-mono tabular-nums">
                <span class="font-semibold text-ng-ink">{{ prefixoMoeda }} {{ valorAtual }}</span>
                <span class="text-ng-ink-subtle"> / {{ prefixoMoeda }} {{ valorMeta }}</span>
            </span>
        </div>

        <BarraProgresso
            class="mt-2"
            :percentual="percentual"
            :percentual-projetado="percentualProjetado"
            :cor="cor"
            :altura="altura"
        />

        <div
            v-if="$slots['legenda-esquerda'] || $slots['legenda-direita']"
            class="mt-2 flex items-center justify-between gap-3 text-[11px]"
        >
            <span class="min-w-0 truncate">
                <slot name="legenda-esquerda" />
            </span>
            <span class="shrink-0 font-mono text-ng-ink-muted">
                <slot name="legenda-direita" />
            </span>
        </div>
    </div>
</template>
