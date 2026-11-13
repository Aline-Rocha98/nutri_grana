<script setup>
defineProps({
    titulo: {
        type: String,
        default: 'NutriGrana',
    },
    subtitulo: {
        type: String,
        default: '',
    },
    logo: {
        type: String,
        default: '/images/logo-N.png',
    },
    largura: {
        type: String,
        default: 'md',
    },
});

const CLASSES_POR_LARGURA = {
    md: 'max-w-md',
    lg: 'max-w-lg',
};
</script>

<template>
    <div class="ng-card w-full p-6 shadow-2xl sm:p-8" :class="CLASSES_POR_LARGURA[largura] ?? CLASSES_POR_LARGURA.md">
        <div class="mb-6 flex flex-col items-center text-center">
            <img :src="logo" :alt="titulo" class="h-14 w-auto select-none" draggable="false">
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-ng-ink">{{ titulo }}</h1>
            <p v-if="subtitulo" class="mt-1.5 text-sm text-ng-ink-muted">{{ subtitulo }}</p>
        </div>

        <div v-if="$slots.topo" class="mb-6">
            <slot name="topo" />
        </div>

        <slot />

        <div v-if="$slots.rodape" class="mt-6 border-t border-ng-line pt-5 text-center text-sm text-ng-ink-muted">
            <slot name="rodape" />
        </div>
    </div>
</template>
