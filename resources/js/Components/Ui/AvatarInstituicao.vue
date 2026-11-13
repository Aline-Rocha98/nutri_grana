<script setup>
import { computed } from 'vue';

const props = defineProps({
    logo: {
        type: String,
        default: null,
    },
    icone: {
        type: String,
        default: 'account_balance',
    },
    rotulo: {
        type: String,
        default: '',
    },
    tamanho: {
        type: String,
        default: 'md',
    },
    comMoldura: {
        type: Boolean,
        default: false,
    },
});

const CLASSES_TAMANHO = {
    sm: { icone: 'text-[18px]', moldura: 'h-8 w-8' },
    md: { icone: 'text-[22px]', moldura: 'h-10 w-10' },
};

const tamanhoAtual = computed(() => CLASSES_TAMANHO[props.tamanho] ?? CLASSES_TAMANHO.md);
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center justify-center"
        :class="[
            comMoldura ? 'rounded-xl border border-ng-line-strong bg-ng-card-muted' : 'rounded-lg',
            comMoldura || logo ? ['overflow-hidden', tamanhoAtual.moldura] : '',
        ]"
        :title="rotulo || undefined"
    >
        <img v-if="logo" :src="logo" :alt="rotulo" class="h-full w-full object-cover">

        <slot v-else>
            <span class="material-symbols-outlined text-ng-ink-muted" :class="tamanhoAtual.icone">{{ icone }}</span>
        </slot>
    </span>
</template>
