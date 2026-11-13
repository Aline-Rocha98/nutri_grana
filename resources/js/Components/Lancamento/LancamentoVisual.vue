<script setup>
import { computed } from 'vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import PainelVisualFinanceiro from '@/Components/Ui/PainelVisualFinanceiro.vue';
import { estiloDaVariante } from '@/Helpers/varianteCor';

const ROTULOS_PADRAO = {
    receita: 'Entrada / Receita',
    despesa: 'Saída / Despesa',
    origem: 'Origem / destino',
    semDescricao: 'Sem descrição',
};

const ESTILO_POR_TIPO = {
    receita: { variante: 'brand', icone: 'south_west', sinal: '+' },
    despesa: { variante: 'red', icone: 'north_east', sinal: '-' },
};

const props = defineProps({
    tipo: {
        type: String,
        default: 'despesa',
    },
    descricao: {
        type: String,
        default: '',
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    valor: {
        type: String,
        default: '0,00',
    },
    data: {
        type: String,
        default: '',
    },
    categoria: {
        type: String,
        default: '',
    },
    etiqueta: {
        type: String,
        default: '',
    },
    etiquetaIcone: {
        type: String,
        default: 'repeat',
    },
    situacaoRotulo: {
        type: String,
        default: '',
    },
    situacaoVariante: {
        type: String,
        default: 'zinc',
    },
    situacaoIcone: {
        type: String,
        default: '',
    },
    origem: {
        type: String,
        default: '',
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));
const estiloTipo = computed(() => ESTILO_POR_TIPO[props.tipo] ?? ESTILO_POR_TIPO.despesa);
const estiloCor = computed(() => estiloDaVariante(estiloTipo.value.variante));
const rotuloTipo = computed(() => textos.value[props.tipo] ?? textos.value.despesa);
</script>

<template>
    <PainelVisualFinanceiro :cor-acabamento="estiloCor.hex">
        <template #topo-esquerda>
            <span class="h-8 w-8 rounded-lg" :class="estiloCor.icone">
                <span class="material-symbols-outlined text-[18px]">{{ estiloTipo.icone }}</span>
            </span>
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wide" :class="estiloCor.texto">{{ rotuloTipo }}</p>
                <p class="flex items-center gap-2 text-[11px] text-ng-ink-muted">
                    <span v-if="categoria" class="truncate">{{ categoria }}</span>
                    <span v-if="etiqueta" class="inline-flex items-center gap-0.5 text-ng-brand">
                        <span class="material-symbols-outlined text-[13px]">{{ etiquetaIcone }}</span>
                        {{ etiqueta }}
                    </span>
                </p>
            </div>
        </template>

        <template #topo-direita>
            <BadgeStatus v-if="situacaoRotulo" :texto="situacaoRotulo" :variante="situacaoVariante" :icone="situacaoIcone" />
        </template>

        <template #centro>
            <div class="min-w-0">
                <p class="truncate text-xs text-ng-ink-secondary">{{ descricao || textos.semDescricao }}</p>
                <p class="truncate font-mono text-3xl font-semibold tabular-nums" :class="estiloCor.texto">
                    {{ estiloTipo.sinal }} {{ prefixoMoeda }} {{ valor }}
                </p>
            </div>
            <p v-if="data" class="shrink-0 font-mono text-xs text-ng-ink-subtle">{{ data }}</p>
        </template>

        <template #rodape-esquerda>
            <p class="text-[10px] font-medium uppercase tracking-wider text-ng-ink-subtle">
                {{ textos.origem }}:
                <span class="ml-1 text-xs font-semibold normal-case tracking-normal text-ng-ink">{{ origem || '—' }}</span>
            </p>
        </template>
    </PainelVisualFinanceiro>
</template>
