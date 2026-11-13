<script setup>
defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Buscar...',
    },
    rotulo: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <label class="relative block">
        <span class="material-symbols-outlined pointer-events-none absolute inset-y-0 left-3 flex items-center text-[18px] text-ng-ink-subtle">
            search
        </span>
        <input
            :value="modelValue"
            type="search"
            autocomplete="off"
            :placeholder="placeholder"
            :aria-label="rotulo || placeholder"
            class="ng-input block w-full rounded-lg pl-10 text-sm"
            @input="emit('update:modelValue', $event.target.value)"
        >
    </label>
</template>
