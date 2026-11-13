<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { estiloDaVariante } from '@/Helpers/varianteCor';

const props = defineProps({
    href: {
        type: String,
        default: '',
    },
    rotulo: {
        type: String,
        required: true,
    },
    iconeEsquerda: {
        type: String,
        default: '',
    },
    iconeDireita: {
        type: String,
        default: '',
    },
    destacado: {
        type: Boolean,
        default: false,
    },
    variante: {
        type: String,
        default: 'brand',
    },
});

const classeCor = computed(() => (props.destacado
    ? [estiloDaVariante(props.variante).texto, 'hover:opacity-80']
    : 'text-ng-ink-muted hover:text-ng-ink'));
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href || undefined"
        :type="href ? undefined : 'button'"
        class="inline-flex items-center gap-1 whitespace-nowrap text-xs font-medium transition"
        :class="classeCor"
    >
        <span v-if="iconeEsquerda" class="material-symbols-outlined text-[16px]">{{ iconeEsquerda }}</span>
        {{ rotulo }}
        <span v-if="iconeDireita" class="material-symbols-outlined text-[16px]">{{ iconeDireita }}</span>
    </component>
</template>
