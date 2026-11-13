<script setup>
defineProps({
    rotulo: {
        type: String,
        default: '',
    },
    valor: {
        type: String,
        default: '',
    },
    complemento: {
        type: String,
        default: '',
    },
    variante: {
        type: String,
        default: 'brand',
    },
});

const classesVariante = {
    brand: { ponto: 'bg-ng-brand', valor: 'text-ng-brand' },
    amber: { ponto: 'bg-amber-500', valor: 'text-amber-500' },
    red: { ponto: 'bg-red-500', valor: 'text-red-500' },
    sky: { ponto: 'bg-sky-500', valor: 'text-sky-500' },
};
</script>

<template>
    <div class="ng-card-muted flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-xs">
        <p class="flex items-center gap-2 text-ng-ink-muted">
            <span class="h-1.5 w-1.5 rounded-full" :class="(classesVariante[variante] ?? classesVariante.brand).ponto" />
            {{ rotulo }}
            <strong class="font-semibold" :class="(classesVariante[variante] ?? classesVariante.brand).valor">{{ valor }}</strong>
        </p>
        <span v-if="complemento" class="font-mono text-[11px] text-ng-ink-subtle">{{ complemento }}</span>
    </div>
</template>
