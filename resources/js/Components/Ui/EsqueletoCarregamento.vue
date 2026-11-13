<script setup>
defineProps({
    quantidade: {
        type: Number,
        default: 3,
    },
    altura: {
        type: String,
        default: 'h-10',
    },
    colunas: {
        type: String,
        default: 'grid-cols-1',
    },
});
</script>

<template>
    <div class="grid gap-3" :class="colunas" aria-busy="true" aria-live="polite">
        <div
            v-for="indice in quantidade"
            :key="indice"
            class="animate-pulse rounded-xl bg-ng-card-muted"
            :class="altura"
        />
    </div>
</template>
