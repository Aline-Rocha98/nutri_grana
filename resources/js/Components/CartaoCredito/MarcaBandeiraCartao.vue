<script setup>
import { computed } from 'vue';
import AvatarInstituicao from '@/Components/Ui/AvatarInstituicao.vue';

const props = defineProps({
    bandeira: {
        type: String,
        default: '',
    },
    logo: {
        type: String,
        default: null,
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

const BANDEIRAS_COM_MARCA = ['mastercard', 'visa', 'elo', 'amex', 'hipercard'];

const classesTamanho = {
    sm: { circulo: 'h-4 w-4', texto: 'text-[11px]' },
    md: { circulo: 'h-5 w-5', texto: 'text-sm' },
};

const tamanhoAtual = computed(() => classesTamanho[props.tamanho] ?? classesTamanho.md);
const possuiMarcaBandeira = computed(() => BANDEIRAS_COM_MARCA.includes(props.bandeira));
</script>

<template>
    <AvatarInstituicao :logo="logo" icone="credit_card" :rotulo="rotulo" :tamanho="tamanho" :com-moldura="comMoldura">
        <template v-if="possuiMarcaBandeira" #default>
            <span v-if="bandeira === 'mastercard'" class="flex items-center">
                <span class="rounded-full bg-[#eb001b]" :class="tamanhoAtual.circulo" />
                <span class="-ml-2 rounded-full bg-[#f79e1b] opacity-90" :class="tamanhoAtual.circulo" />
            </span>

            <span v-else-if="bandeira === 'visa'" class="font-black italic tracking-tight text-[#4f7cff]" :class="tamanhoAtual.texto">
                VISA
            </span>

            <span v-else-if="bandeira === 'elo'" class="flex items-center gap-1 font-bold text-ng-ink" :class="tamanhoAtual.texto">
                <span class="flex">
                    <span class="h-2 w-2 rounded-full bg-[#ffcb05]" />
                    <span class="-ml-0.5 h-2 w-2 rounded-full bg-[#00a4e0]" />
                    <span class="-ml-0.5 h-2 w-2 rounded-full bg-[#ef4123]" />
                </span>
                elo
            </span>

            <span
                v-else-if="bandeira === 'amex'"
                class="rounded border border-[#2e77bc] px-1 font-bold text-[#2e77bc] text-[9px] leading-tight"
            >
                AMEX
            </span>

            <span v-else class="font-bold italic text-[#b3131b]" :class="tamanhoAtual.texto">
                Hiper
            </span>
        </template>
    </AvatarInstituicao>
</template>
