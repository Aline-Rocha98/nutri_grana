<script setup>
defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    id: {
        type: String,
        default: '',
    },
    rotulo: {
        type: String,
        default: '',
    },
    desabilitado: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <label
        :for="id || undefined"
        class="inline-flex cursor-pointer select-none items-center gap-2 text-sm text-ng-ink-secondary"
        :class="desabilitado ? 'cursor-not-allowed opacity-60' : ''"
    >
        <input
            :id="id || undefined"
            :checked="modelValue"
            type="checkbox"
            :disabled="desabilitado"
            class="h-4 w-4 rounded border-ng-input-border bg-ng-card-muted text-ng-brand focus:ring-ng-brand focus:ring-offset-0"
            @change="emit('update:modelValue', $event.target.checked)"
        >
        <span>{{ rotulo }}</span>
    </label>
</template>
