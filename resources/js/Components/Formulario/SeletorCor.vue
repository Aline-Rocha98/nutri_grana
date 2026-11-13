<script setup>
defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    rotulo: {
        type: String,
        default: '',
    },
    opcoes: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex items-center gap-2" role="radiogroup" :aria-label="rotulo">
        <span v-if="rotulo" class="text-[11px] text-ng-ink-subtle">{{ rotulo }}</span>
        <button
            v-for="opcao in opcoes"
            :key="opcao.valor"
            type="button"
            role="radio"
            :aria-checked="modelValue === opcao.valor"
            :title="opcao.rotulo"
            :aria-label="opcao.rotulo"
            class="h-4 w-4 rounded-full ring-offset-2 ring-offset-ng-card transition"
            :class="modelValue === opcao.valor ? 'ring-2 ring-ng-brand' : 'opacity-80 hover:opacity-100'"
            :style="{ backgroundColor: opcao.cor }"
            @click="emit('update:modelValue', opcao.valor)"
        />
    </div>
</template>
