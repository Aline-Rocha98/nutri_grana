<script setup>
defineProps({
    id: {
        type: String,
        default: '',
    },
    rotulo: {
        type: String,
        default: '',
    },
    obrigatorio: {
        type: Boolean,
        default: false,
    },
    dica: {
        type: String,
        default: '',
    },
    erro: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div>
        <div v-if="rotulo || dica || $slots['rotulo-extra']" class="mb-1.5 flex items-center justify-between gap-3">
            <label v-if="rotulo" :for="id || undefined" class="ng-label">
                {{ rotulo }}
                <span v-if="obrigatorio" class="text-ng-brand">*</span>
            </label>
            <slot name="rotulo-extra">
                <span v-if="dica" class="text-[11px] text-ng-ink-subtle">{{ dica }}</span>
            </slot>
        </div>

        <slot />

        <p v-if="erro" class="mt-1.5 text-xs text-red-500">{{ erro }}</p>
    </div>
</template>
