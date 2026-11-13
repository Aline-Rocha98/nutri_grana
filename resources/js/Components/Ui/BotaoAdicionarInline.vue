<script setup>
defineProps({
    rotulo: {
        type: String,
        required: true,
    },
    icone: {
        type: String,
        default: 'add',
    },
});

const emit = defineEmits(['click']);
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-1 whitespace-nowrap text-sm font-semibold text-ng-brand transition hover:text-ng-brand-hover"
        @click="emit('click', $event)"
    >
        <span class="material-symbols-outlined text-[18px]">{{ icone }}</span>
        {{ rotulo }}
    </button>
</template>
