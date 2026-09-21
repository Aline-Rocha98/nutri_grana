<script setup>
defineProps({
    percentual: {
        type: Number,
        default: 0,
    },
    cor: {
        type: String,
        default: '#1fa67e',
    },
    ultrapassado: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10">
        <div
            class="h-full rounded-full transition-all duration-500 ease-smooth"
            :class="ultrapassado ? 'bg-red-500' : ''"
            :style="ultrapassado ? { width: `${Math.min(100, percentual)}%` } : { width: `${Math.min(100, percentual)}%`, backgroundColor: cor }"
        />
    </div>
</template>
