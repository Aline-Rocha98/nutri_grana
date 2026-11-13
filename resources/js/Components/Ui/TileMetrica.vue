<script setup>
defineProps({
    rotulo: {
        type: String,
        required: true,
    },
    valor: {
        type: String,
        default: '0,00',
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    sufixo: {
        type: String,
        default: '',
    },
    destacado: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        class="rounded-xl border px-3 py-2.5"
        :class="destacado ? 'ng-selecionavel--ativo' : 'border-ng-line bg-ng-card-muted'"
    >
        <p
            class="text-[10px] font-semibold uppercase tracking-wider"
            :class="destacado ? 'text-ng-brand' : 'text-ng-ink-muted'"
        >
            {{ rotulo }}
        </p>
        <p
            class="mt-1 font-mono text-sm font-semibold tabular-nums"
            :class="destacado ? 'text-ng-brand' : 'text-ng-ink'"
        >
            {{ prefixoMoeda }} {{ valor }}<span v-if="sufixo" class="text-[11px] font-normal text-ng-ink-muted">{{ sufixo }}</span>
        </p>
    </div>
</template>
