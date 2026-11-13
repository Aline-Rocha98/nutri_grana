<script setup>
import { computed } from 'vue';

const props = defineProps({
    icone: {
        type: String,
        default: 'category',
    },
    cor: {
        type: String,
        default: '#6b7280',
    },
    tamanho: {
        type: String,
        default: 'md',
    },
    formato: {
        type: String,
        default: 'quadrado',
    },
});

const classesTamanho = {
    sm: { caixa: 'h-8 w-8', icone: 'text-[16px]' },
    md: { caixa: 'h-10 w-10', icone: 'text-[20px]' },
    lg: { caixa: 'h-11 w-11', icone: 'text-[22px]' },
};

const tamanhoAtual = computed(() => classesTamanho[props.tamanho] ?? classesTamanho.md);

const estiloCor = computed(() => {
    const cor = props.cor || '#6b7280';

    return {
        backgroundColor: `${cor}1a`,
        borderColor: `${cor}33`,
        color: cor,
    };
});
</script>

<template>
    <span
        class="flex shrink-0 items-center justify-center border"
        :class="[tamanhoAtual.caixa, formato === 'circulo' ? 'rounded-full' : 'rounded-xl']"
        :style="estiloCor"
    >
        <span class="material-symbols-outlined" :class="tamanhoAtual.icone">{{ icone || 'category' }}</span>
    </span>
</template>
