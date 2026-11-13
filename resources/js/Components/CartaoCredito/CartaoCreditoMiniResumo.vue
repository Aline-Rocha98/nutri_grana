<script setup>
import { computed } from 'vue';
import BarraProgresso from '@/Components/BarraProgresso.vue';
import MarcaBandeiraCartao from '@/Components/CartaoCredito/MarcaBandeiraCartao.vue';

const ROTULOS_PADRAO = {
    usado: 'usado',
    limiteTotal: 'Limite Total',
    disponivel: 'Disponível',
    faturaAtual: 'Fatura Atual',
    vencimento: 'Vencimento',
    dia: 'Dia',
};

const props = defineProps({
    nome: {
        type: String,
        required: true,
    },
    bandeira: {
        type: String,
        default: '',
    },
    bandeiraRotulo: {
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
    limiteTotal: {
        type: String,
        default: '0,00',
    },
    limiteDisponivel: {
        type: String,
        default: '0,00',
    },
    faturaAtual: {
        type: String,
        default: '0,00',
    },
    diaVencimento: {
        type: String,
        default: '',
    },
    percentualUtilizado: {
        type: Number,
        default: 0,
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));

const informacoes = computed(() => [
    { rotulo: textos.value.limiteTotal, valor: `${props.prefixoMoeda} ${props.limiteTotal}`, classe: 'text-ng-ink' },
    { rotulo: textos.value.disponivel, valor: `${props.prefixoMoeda} ${props.limiteDisponivel}`, classe: 'text-ng-brand' },
    { rotulo: textos.value.faturaAtual, valor: `${props.prefixoMoeda} ${props.faturaAtual}`, classe: 'text-ng-ink' },
    { rotulo: textos.value.vencimento, valor: props.diaVencimento ? `${textos.value.dia} ${props.diaVencimento}` : '—', classe: 'text-ng-ink' },
]);
</script>

<template>
    <article class="ng-card-muted p-4">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2.5">
                <MarcaBandeiraCartao
                    :bandeira="bandeira"
                    :logo="logo"
                    :rotulo="bandeiraRotulo || nome"
                    tamanho="sm"
                    com-moldura
                />
                <p class="truncate text-sm font-semibold text-ng-ink">{{ nome }}</p>
            </div>
            <span class="shrink-0 font-mono text-[10px] text-ng-ink-muted">
                {{ Math.round(percentualUtilizado) }}% {{ textos.usado }}
            </span>
        </div>

        <BarraProgresso
            class="mt-3"
            :percentual="percentualUtilizado"
            :ultrapassado="percentualUtilizado > 100"
            altura="h-1.5"
        />

        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3">
            <div v-for="informacao in informacoes" :key="informacao.rotulo" class="min-w-0">
                <dt class="text-[10px] text-ng-ink-muted">{{ informacao.rotulo }}</dt>
                <dd class="truncate font-mono text-xs font-semibold tabular-nums" :class="informacao.classe">
                    {{ informacao.valor }}
                </dd>
            </div>
        </dl>
    </article>
</template>
