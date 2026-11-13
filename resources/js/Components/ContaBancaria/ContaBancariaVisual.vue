<script setup>
import { computed } from 'vue';
import AvatarInstituicao from '@/Components/Ui/AvatarInstituicao.vue';
import PainelVisualFinanceiro from '@/Components/Ui/PainelVisualFinanceiro.vue';
import { iconeTipoContaBancaria } from '@/Helpers/tipoContaBancaria';

const ROTULOS_PADRAO = {
    saldoAtual: 'Saldo atual',
    saldoInicial: 'Saldo inicial',
};

const props = defineProps({
    nome: {
        type: String,
        default: '',
    },
    tipo: {
        type: String,
        default: '',
    },
    tipoRotulo: {
        type: String,
        default: '',
    },
    logo: {
        type: String,
        default: null,
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    saldoAtual: {
        type: String,
        default: '0,00',
    },
    saldoAtualNumero: {
        type: Number,
        default: 0,
    },
    saldoInicial: {
        type: String,
        default: '0,00',
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
</script>

<template>
    <PainelVisualFinanceiro :cor-acabamento="corAcabamento" :titulo="nome">
        <template #topo-esquerda>
            <AvatarInstituicao :logo="logo" :icone="iconeTipoContaBancaria(tipo)" :rotulo="nome" tamanho="sm" com-moldura />
            <span class="text-[10px] font-medium uppercase tracking-[0.2em] text-ng-ink-subtle">{{ tipoRotulo }}</span>
        </template>

        <template #topo-direita>
            <span class="material-symbols-outlined text-[20px] text-ng-ink-subtle">{{ iconeTipoContaBancaria(tipo) }}</span>
        </template>

        <template #rodape-esquerda>
            <p class="text-[10px] font-medium uppercase tracking-wider text-ng-ink-subtle">{{ textos.saldoAtual }}</p>
            <p
                class="font-mono text-base font-semibold tabular-nums"
                :class="saldoAtualNumero < 0 ? 'text-red-500' : 'text-ng-brand'"
            >
                {{ prefixoMoeda }} {{ saldoAtual }}
            </p>
        </template>

        <template #rodape-direita>
            <p class="text-[10px] font-medium uppercase tracking-wider text-ng-ink-subtle">{{ textos.saldoInicial }}</p>
            <p class="font-mono text-xs font-semibold text-ng-ink">{{ prefixoMoeda }} {{ saldoInicial }}</p>
        </template>
    </PainelVisualFinanceiro>
</template>
