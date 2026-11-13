<script setup>
defineProps({
    rotulo: {
        type: String,
        required: true,
    },
    icone: {
        type: String,
        default: '',
    },
    desabilitado: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['click']);
</script>

<template>
    <button
        type="button"
        class="ng-selecionavel inline-flex items-center gap-1 whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium text-ng-ink-secondary hover:text-ng-ink disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="desabilitado"
        @click="emit('click', $event)"
    >
        <span v-if="icone" class="material-symbols-outlined text-[16px]">{{ icone }}</span>
        {{ rotulo }}
    </button>
</template>
