<script setup>
import { computed } from 'vue';
import MarcaBandeiraCartao from '@/Components/CartaoCredito/MarcaBandeiraCartao.vue';
import PainelVisualFinanceiro from '@/Components/Ui/PainelVisualFinanceiro.vue';

const ROTULOS_PADRAO = {
    tipo: 'Crédito',
    limiteConfigurado: 'Limite configurado',
    cicloFatura: 'Ciclo da fatura',
    fecha: 'Fecha',
    vence: 'Vence',
};

const props = defineProps({
    nome: {
        type: String,
        default: '',
    },
    nomeTitular: {
        type: String,
        default: '',
    },
    ultimosDigitos: {
        type: String,
        default: '',
    },
    bandeira: {
        type: String,
        default: '',
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    limite: {
        type: String,
        default: '0,00',
    },
    diaFechamento: {
        type: String,
        default: '',
    },
    diaVencimento: {
        type: String,
        default: '',
    },
    corAcabamento: {
        type: String,
        default: '#1fa67e',
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));
const digitosMascarados = computed(() => (props.ultimosDigitos ? `•••• ${props.ultimosDigitos}` : ''));
</script>

<template>
    <PainelVisualFinanceiro
        :cor-acabamento="corAcabamento"
        :titulo="nome"
        :subtitulo="nomeTitular"
        :detalhe="digitosMascarados"
    >
        <template #topo-esquerda>
            <span class="h-7 w-9 rounded-md bg-gradient-to-br from-[#f7d774] to-[#c99a2e] shadow-inner" />
            <span class="material-symbols-outlined text-[18px] text-ng-ink-subtle">contactless</span>
            <span class="text-[10px] font-medium uppercase tracking-[0.2em] text-ng-ink-subtle">{{ textos.tipo }}</span>
        </template>

        <template #topo-direita>
            <MarcaBandeiraCartao :bandeira="bandeira" />
        </template>

        <template #rodape-esquerda>
            <p class="text-[10px] font-medium uppercase tracking-wider text-ng-ink-subtle">{{ textos.limiteConfigurado }}</p>
            <p class="font-mono text-base font-semibold tabular-nums text-ng-brand">{{ prefixoMoeda }} {{ limite }}</p>
        </template>

        <template #rodape-direita>
            <p class="text-[10px] font-medium uppercase tracking-wider text-ng-ink-subtle">{{ textos.cicloFatura }}</p>
            <p class="font-mono text-xs font-semibold text-ng-ink">
                {{ textos.fecha }} {{ diaFechamento }} · {{ textos.vence }} {{ diaVencimento }}
            </p>
        </template>
    </PainelVisualFinanceiro>
</template>
