<script setup>
import BarraProgresso from '@/Components/BarraProgresso.vue';

defineProps({
    titulo: {
        type: String,
        default: 'Meta em Destaque',
    },
    descricao: {
        type: String,
        required: true,
    },
    dataLimiteFormatada: {
        type: String,
        default: '',
    },
    valorGuardado: {
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
});
</script>

<template>
    <article class="ng-card p-5">
        <div class="flex items-center justify-between gap-3">
            <p class="flex items-center gap-2 text-sm font-semibold text-ng-ink">
                <span class="material-symbols-outlined text-[18px] text-ng-brand">savings</span>
                {{ titulo }}
            </p>
            <span class="font-mono text-xs font-semibold text-ng-brand">{{ Math.round(percentual) }}%</span>
        </div>

        <p class="mt-4 truncate text-sm font-semibold text-ng-ink" :title="descricao">{{ descricao }}</p>
        <p v-if="dataLimiteFormatada" class="text-[11px] text-ng-ink-muted">Meta até {{ dataLimiteFormatada }}</p>

        <BarraProgresso class="mt-4" :percentual="percentual" altura="h-2" />

        <div class="mt-2 flex items-center justify-between gap-3 font-mono text-xs tabular-nums">
            <span class="font-semibold text-ng-ink">{{ prefixoMoeda }} {{ valorGuardado }}</span>
            <span class="text-ng-ink-subtle">de {{ prefixoMoeda }} {{ valorMeta }}</span>
        </div>

        <div v-if="$slots.acao" class="mt-4">
            <slot name="acao" />
        </div>
    </article>
</template>
