<script setup>
import { BADGE } from '@/Helpers/badge';

defineProps({
    texto: {
        type: String,
        required: true,
    },
    variante: {
        type: String,
        default: 'zinc',
    },
    icone: {
        type: String,
        default: '',
    },
    compacto: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <span
        class="gap-1 whitespace-nowrap"
        :class="[BADGE[variante] ?? BADGE.zinc, compacto ? '!px-2 !py-0 text-[10px]' : '']"
    >
        <span v-if="icone" class="material-symbols-outlined text-[12px] leading-none">{{ icone }}</span>
        {{ texto }}
    </span>
</template>
