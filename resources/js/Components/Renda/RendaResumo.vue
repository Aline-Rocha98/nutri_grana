<script setup>
import { computed } from 'vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';
import CardResumoFinanceiro from '@/Components/Ui/CardResumoFinanceiro.vue';
import IconeCategoria from '@/Components/Ui/IconeCategoria.vue';
import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';

const ROTULOS_PADRAO = {
    valorEsperado: 'Valor esperado',
    capacidade: 'Capacidade',
    guardado: 'Guardado por recebimento',
    dia: 'dia',
    semConta: 'Sem conta vinculada',
    semPlano: 'Sem plano financeiro',
    planoAplicado: 'Plano financeiro aplicado',
    editar: 'Editar',
    excluir: 'Excluir',
};

const props = defineProps({
    descricao: {
        type: String,
        required: true,
    },
    frequenciaRotulo: {
        type: String,
        default: '',
    },
    diaEsperado: {
        type: Number,
        default: null,
    },
    contaBancariaNome: {
        type: String,
        default: '',
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    valorEsperado: {
        type: String,
        default: '0,00',
    },
    valorCapacidade: {
        type: Number,
        default: 0,
    },
    valorDestinado: {
        type: Number,
        default: 0,
    },
    planoFinanceiroNome: {
        type: String,
        default: '',
    },
    icone: {
        type: String,
        default: 'payments',
    },
    cor: {
        type: String,
        default: '#1fa67e',
    },
    selecionado: {
        type: Boolean,
        default: false,
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['selecionar', 'editar', 'excluir']);

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));

const identificacao = computed(() => [
    props.frequenciaRotulo,
    props.diaEsperado ? `${textos.value.dia} ${props.diaEsperado}` : null,
].filter(Boolean).join(' · '));
</script>

<template>
    <CardResumoFinanceiro
        :nome="descricao"
        :identificacao="identificacao"
        :selecionado="selecionado"
        @selecionar="emit('selecionar')"
    >
        <template #marca>
            <IconeCategoria :icone="icone" :cor="cor" tamanho="lg" />
        </template>

        <template #acoes>
            <BotaoIcone icone="edit" :rotulo="textos.editar" parar-propagacao @click="emit('editar')" />
            <BotaoIcone
                icone="delete"
                variante="perigo"
                :rotulo="textos.excluir"
                parar-propagacao
                @click="emit('excluir')"
            />
        </template>

        <div class="space-y-1">
            <p class="text-xs font-medium text-ng-ink-secondary">{{ textos.valorEsperado }}</p>
            <p class="font-mono text-2xl font-semibold tabular-nums text-ng-brand">
                {{ prefixoMoeda }} {{ valorEsperado }}
            </p>
            <p class="text-[11px] text-ng-ink-subtle">
                {{ textos.capacidade }}:
                <span class="font-mono" :class="valorCapacidade < 0 ? 'text-red-500' : ''">
                    {{ prefixoMoeda }} {{ formatarNumeroParaMoeda(valorCapacidade) }}
                </span>
            </p>
            <p v-if="valorDestinado > 0" class="flex items-center gap-1 text-[11px] text-sky-500">
                <span class="material-symbols-outlined text-[14px]">savings</span>
                {{ textos.guardado }}:
                <span class="font-mono">{{ prefixoMoeda }} {{ formatarNumeroParaMoeda(valorDestinado) }}</span>
            </p>
        </div>

        <template #rodape>
            <span class="flex min-w-0 items-center gap-1.5 text-ng-ink-muted">
                <span class="material-symbols-outlined text-[16px] text-ng-brand">account_balance</span>
                <span class="truncate">{{ contaBancariaNome || textos.semConta }}</span>
            </span>
            <span class="flex min-w-0 shrink items-center gap-1 text-ng-ink-muted" :title="textos.planoAplicado">
                <span class="material-symbols-outlined text-[16px] text-sky-500">donut_large</span>
                <span class="truncate">{{ planoFinanceiroNome || textos.semPlano }}</span>
            </span>
        </template>
    </CardResumoFinanceiro>
</template>
