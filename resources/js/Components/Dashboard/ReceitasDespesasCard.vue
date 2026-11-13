<script setup>
import { computed } from 'vue';
import CardPainel from '@/Components/Ui/CardPainel.vue';
import CaixaDestaqueValor from '@/Components/Ui/CaixaDestaqueValor.vue';
import EsqueletoCarregamento from '@/Components/Ui/EsqueletoCarregamento.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';
import LinhaBarraRotulada from '@/Components/Ui/LinhaBarraRotulada.vue';

const COR_RECEITA = '#1fa67e';
const COR_DESPESA = '#f43f5e';

const OPCOES_PERIODO = [
    { valor: 'atual', rotulo: 'Mês atual' },
    { valor: 'anterior', rotulo: 'Mês anterior' },
    { valor: '3meses', rotulo: '3 meses' },
];

const props = defineProps({
    dados: {
        type: Object,
        default: null,
    },
    carregando: {
        type: Boolean,
        default: false,
    },
    periodo: {
        type: String,
        default: 'atual',
    },
});

const emit = defineEmits(['atualizar-periodo']);

const totais = computed(() => props.dados?.totais ?? null);
const possuiMovimentacao = computed(() => Boolean(
    totais.value && (totais.value.receitas_numero > 0 || totais.value.despesas_numero > 0),
));
const saldoPositivo = computed(() => (totais.value?.saldo_poupado_numero ?? 0) >= 0);
const percentualEntradas = computed(() => (totais.value?.receitas_numero > 0 ? 100 : 0));
const rotuloEntradas = computed(() => (props.periodo === '3meses' ? 'Entradas do período' : 'Entradas do mês'));
</script>

<template>
    <CardPainel
        titulo="Receitas x Despesas"
        subtitulo="Balanço do fluxo de caixa e margem poupada"
        icone="bar_chart"
        :carregando="carregando"
        :vazio="!possuiMovimentacao"
        mensagem-vazio="Sem movimentações para o período selecionado."
    >
        <template #acoes>
            <GrupoSegmentado
                :model-value="periodo"
                :opcoes="OPCOES_PERIODO"
                rotulo="Período de receitas e despesas"
                @update:model-value="(valor) => emit('atualizar-periodo', valor)"
            />
        </template>

        <template #carregando>
            <EsqueletoCarregamento :quantidade="1" altura="h-56" />
        </template>

        <div class="ng-card-muted space-y-5 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <span class="inline-flex items-center gap-2 text-ng-ink-secondary">
                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: COR_RECEITA }" />
                    Receitas
                    <strong class="font-mono font-semibold text-ng-brand">R$ {{ totais.receitas }}</strong>
                </span>
                <span class="inline-flex items-center gap-2 text-ng-ink-secondary">
                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: COR_DESPESA }" />
                    Despesas
                    <strong class="font-mono font-semibold text-rose-500">R$ {{ totais.despesas }}</strong>
                </span>
            </div>

            <LinhaBarraRotulada
                :rotulo="rotuloEntradas"
                :valor="`${percentualEntradas}%`"
                :percentual="percentualEntradas"
                :cor="COR_RECEITA"
            />

            <LinhaBarraRotulada
                rotulo="Saídas comprometidas"
                :valor="`${Math.round(totais.percentual_comprometido)}% da renda`"
                :percentual="totais.percentual_comprometido"
                :cor="COR_DESPESA"
                classe-valor="text-rose-500"
            />

            <CaixaDestaqueValor
                :rotulo="saldoPositivo ? 'Saldo líquido poupado' : 'Saldo líquido negativo'"
                :valor="totais.saldo_poupado"
                :sinal="saldoPositivo ? '' : '-'"
                :variante="saldoPositivo ? 'brand' : 'red'"
                :selo="`${Math.round(totais.percentual_guardado)}% guardado`"
            />
        </div>
    </CardPainel>
</template>
