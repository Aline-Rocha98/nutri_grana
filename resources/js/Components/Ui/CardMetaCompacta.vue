<script setup>
import BarraProgresso from '@/Components/BarraProgresso.vue';

defineProps({
    titulo: {
        type: String,
        required: true,
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
    cor: {
        type: String,
        default: '#1fa67e',
    },
    rotuloMeta: {
        type: String,
        default: 'Meta',
    },
    rotuloSugerido: {
        type: String,
        default: 'Sugerido',
    },
    valorSugerido: {
        type: String,
        default: '',
    },
    sufixoSugerido: {
        type: String,
        default: '/mês',
    },
});
</script>

<template>
    <article class="ng-card flex flex-col p-4 transition hover:border-ng-line-strong">
        <div class="flex items-start justify-between gap-3">
            <h4 class="truncate text-sm font-semibold text-ng-ink" :title="titulo">{{ titulo }}</h4>
            <span class="shrink-0 font-mono text-xs font-semibold text-ng-brand">{{ Math.round(percentual) }}%</span>
        </div>

        <BarraProgresso class="mt-3" :percentual="percentual" :cor="cor" altura="h-2" />

        <div class="mt-3 flex items-center justify-between gap-3 font-mono text-xs tabular-nums">
            <span class="font-semibold text-ng-ink">{{ prefixoMoeda }} {{ valorAtual }}</span>
            <span class="text-ng-ink-subtle">{{ rotuloMeta }}: {{ prefixoMoeda }} {{ valorMeta }}</span>
        </div>

        <div
            v-if="valorSugerido || $slots.acao"
            class="mt-4 flex items-center justify-between gap-3 border-t border-ng-line pt-3"
        >
            <span v-if="valorSugerido" class="text-[11px] text-ng-ink-muted">
                {{ rotuloSugerido }}:
                <strong class="font-mono font-semibold text-ng-brand">{{ prefixoMoeda }} {{ valorSugerido }}{{ sufixoSugerido }}</strong>
            </span>
            <slot name="acao" />
        </div>
    </article>
</template>
