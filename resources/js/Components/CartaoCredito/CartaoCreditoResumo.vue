<script setup>
import { computed } from 'vue';
import BarraProgresso from '@/Components/BarraProgresso.vue';
import AcoesResumoFinanceiro from '@/Components/Ui/AcoesResumoFinanceiro.vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import CardResumoFinanceiro from '@/Components/Ui/CardResumoFinanceiro.vue';
import MarcaBandeiraCartao from '@/Components/CartaoCredito/MarcaBandeiraCartao.vue';

const ROTULOS_PADRAO = {
    padrao: 'Padrão',
    definirPadrao: 'Definir como padrão',
    arquivar: 'Arquivar',
    desarquivar: 'Desarquivar',
    editar: 'Editar',
    excluir: 'Excluir',
    excluirBloqueado: 'Dê baixa na fatura antes de excluir',
    limiteUtilizado: 'Limite Utilizado',
    disponivel: 'Disponível',
    consumido: 'consumido',
    fechamento: 'Fechamento',
    vencimento: 'Vencimento',
    dia: 'Dia',
};

const props = defineProps({
    nome: {
        type: String,
        required: true,
    },
    ultimosDigitos: {
        type: String,
        default: '',
    },
    nomeTitular: {
        type: String,
        default: '',
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
    limiteUsado: {
        type: String,
        default: '0,00',
    },
    limiteTotal: {
        type: String,
        default: '0,00',
    },
    limiteDisponivel: {
        type: String,
        default: '0,00',
    },
    percentualUtilizado: {
        type: Number,
        default: 0,
    },
    diaFechamento: {
        type: String,
        default: '',
    },
    diaVencimento: {
        type: String,
        default: '',
    },
    padrao: {
        type: Boolean,
        default: false,
    },
    arquivado: {
        type: Boolean,
        default: false,
    },
    selecionado: {
        type: Boolean,
        default: false,
    },
    podeExcluir: {
        type: Boolean,
        default: true,
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['selecionar', 'editar', 'arquivar', 'excluir', 'definir-padrao']);

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));
const percentualArredondado = computed(() => Math.round(props.percentualUtilizado));
const identificacao = computed(() =>
    [props.ultimosDigitos ? `•••• ${props.ultimosDigitos}` : '', props.nomeTitular]
        .filter(Boolean)
        .join(' · '),
);
</script>

<template>
    <CardResumoFinanceiro
        :nome="nome"
        :identificacao="identificacao"
        :selecionado="selecionado"
        :arquivado="arquivado"
        @selecionar="emit('selecionar')"
    >
        <template #marca>
            <MarcaBandeiraCartao :bandeira="bandeira" :logo="logo" :rotulo="bandeiraRotulo || nome" com-moldura />
        </template>

        <template #badges>
            <BadgeStatus v-if="padrao" :texto="textos.padrao" variante="brand" icone="check" compacto />
        </template>

        <template #acoes>
            <AcoesResumoFinanceiro
                :padrao="padrao"
                :arquivado="arquivado"
                :pode-excluir="podeExcluir"
                :rotulos="textos"
                @definir-padrao="emit('definir-padrao')"
                @arquivar="emit('arquivar')"
                @editar="emit('editar')"
                @excluir="emit('excluir')"
            />
        </template>

        <div class="space-y-2">
            <div class="flex items-center justify-between gap-2 text-xs">
                <span class="font-medium text-ng-ink-secondary">{{ textos.limiteUtilizado }}</span>
                <span class="font-mono tabular-nums">
                    <strong class="font-semibold text-ng-ink">{{ prefixoMoeda }} {{ limiteUsado }}</strong>
                    <span class="text-ng-ink-subtle"> / {{ prefixoMoeda }} {{ limiteTotal }}</span>
                </span>
            </div>

            <BarraProgresso :percentual="percentualUtilizado" :ultrapassado="percentualUtilizado > 100" altura="h-2" />

            <div class="flex items-center justify-between gap-2 text-[11px]">
                <span class="font-medium text-ng-brand">
                    {{ textos.disponivel }}: <span class="font-mono">{{ prefixoMoeda }} {{ limiteDisponivel }}</span>
                </span>
                <span class="font-mono text-ng-ink-subtle">{{ percentualArredondado }}% {{ textos.consumido }}</span>
            </div>
        </div>

        <template #rodape>
            <span class="flex items-center gap-1.5 text-ng-ink-muted">
                <span class="material-symbols-outlined text-[16px] text-ng-brand">calendar_month</span>
                {{ textos.fechamento }}:
                <strong class="font-mono font-semibold text-ng-ink">{{ textos.dia }} {{ diaFechamento }}</strong>
            </span>
            <span class="text-ng-ink-muted">
                {{ textos.vencimento }}:
                <strong class="font-mono font-semibold text-ng-ink">{{ textos.dia }} {{ diaVencimento }}</strong>
            </span>
        </template>
    </CardResumoFinanceiro>
</template>
