<script setup>
import { computed } from 'vue';
import AcoesResumoFinanceiro from '@/Components/Ui/AcoesResumoFinanceiro.vue';
import AvatarInstituicao from '@/Components/Ui/AvatarInstituicao.vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import CardResumoFinanceiro from '@/Components/Ui/CardResumoFinanceiro.vue';
import { iconeTipoContaBancaria } from '@/Helpers/tipoContaBancaria';

const ROTULOS_PADRAO = {
    padrao: 'Padrão',
    definirPadrao: 'Definir como padrão para desconto',
    arquivar: 'Arquivar',
    desarquivar: 'Desarquivar',
    editar: 'Editar',
    excluir: 'Excluir',
    excluirBloqueado: 'Há lançamentos vinculados. Arquive a conta em vez de excluí-la',
    saldoAtual: 'Saldo atual',
    saldoInicial: 'Saldo inicial',
    saldoGuardado: 'Guardado',
    exibidaNoResumo: 'Exibida no resumo',
    ocultaNoResumo: 'Oculta no resumo',
    lancamentos: 'lançamento(s)',
};

const props = defineProps({
    nome: {
        type: String,
        required: true,
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
    saldoGuardado: {
        type: String,
        default: '0,00',
    },
    saldoGuardadoNumero: {
        type: Number,
        default: 0,
    },
    exibirResumo: {
        type: Boolean,
        default: false,
    },
    totalLancamentos: {
        type: Number,
        default: 0,
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
const saldoAtualNegativo = computed(() => props.saldoAtualNumero < 0);
</script>

<template>
    <CardResumoFinanceiro
        :nome="nome"
        :identificacao="tipoRotulo"
        :selecionado="selecionado"
        :arquivado="arquivado"
        @selecionar="emit('selecionar')"
    >
        <template #marca>
            <AvatarInstituicao :logo="logo" :icone="iconeTipoContaBancaria(tipo)" :rotulo="nome" com-moldura />
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

        <div class="space-y-1">
            <p class="text-xs font-medium text-ng-ink-secondary">{{ textos.saldoAtual }}</p>
            <p
                class="font-mono text-2xl font-semibold tabular-nums"
                :class="saldoAtualNegativo ? 'text-red-500' : 'text-ng-brand'"
            >
                {{ prefixoMoeda }} {{ saldoAtual }}
            </p>
            <p class="text-[11px] text-ng-ink-subtle">
                {{ textos.saldoInicial }}: <span class="font-mono">{{ prefixoMoeda }} {{ saldoInicial }}</span>
            </p>
            <p v-if="saldoGuardadoNumero > 0" class="flex items-center gap-1 text-[11px] text-sky-500">
                <span class="material-symbols-outlined text-[14px]">savings</span>
                {{ textos.saldoGuardado }}: <span class="font-mono">{{ prefixoMoeda }} {{ saldoGuardado }}</span>
            </p>
        </div>

        <template #rodape>
            <span class="flex items-center gap-1.5 text-ng-ink-muted">
                <span class="material-symbols-outlined text-[16px] text-ng-brand">
                    {{ exibirResumo ? 'visibility' : 'visibility_off' }}
                </span>
                {{ exibirResumo ? textos.exibidaNoResumo : textos.ocultaNoResumo }}
            </span>
            <span class="text-ng-ink-muted">
                <strong class="font-mono font-semibold text-ng-ink">{{ totalLancamentos }}</strong>
                {{ textos.lancamentos }}
            </span>
        </template>
    </CardResumoFinanceiro>
</template>
