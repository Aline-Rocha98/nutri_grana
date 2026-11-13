<script setup>
import { computed } from 'vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import IconeCategoria from '@/Components/Ui/IconeCategoria.vue';
import PainelVisualFinanceiro from '@/Components/Ui/PainelVisualFinanceiro.vue';
import ProgressoMeta from '@/Components/Ui/ProgressoMeta.vue';
import { estiloDaVariante } from '@/Helpers/varianteCor';

const props = defineProps({
    titulo: {
        type: String,
        default: '',
    },
    tituloPadrao: {
        type: String,
        default: '—',
    },
    subtitulo: {
        type: String,
        default: '',
    },
    subtituloIcone: {
        type: String,
        default: '',
    },
    icone: {
        type: String,
        default: '',
    },
    corIcone: {
        type: String,
        default: '#1fa67e',
    },
    badgeTexto: {
        type: String,
        default: '',
    },
    badgeVariante: {
        type: String,
        default: 'zinc',
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    destaqueRotulo: {
        type: String,
        default: '',
    },
    destaqueValor: {
        type: String,
        default: '',
    },
    destaqueSufixo: {
        type: String,
        default: '',
    },
    rotuloProgresso: {
        type: String,
        default: '',
    },
    valorAtual: {
        type: String,
        default: '0,00',
    },
    valorMeta: {
        type: String,
        default: '0,00',
    },
    percentual: {
        type: Number,
        default: 0,
    },
    variante: {
        type: String,
        default: 'brand',
    },
    rodape: {
        type: Array,
        default: () => [],
    },
});

const estiloCor = computed(() => estiloDaVariante(props.variante));
const itensRodapeEsquerda = computed(() => props.rodape.slice(0, -1));
const itemRodapeDireita = computed(() => props.rodape.at(-1) ?? null);
</script>

<template>
    <PainelVisualFinanceiro :cor-acabamento="estiloCor.hex">
        <template #topo-esquerda>
            <IconeCategoria v-if="icone" :icone="icone" :cor="corIcone" tamanho="sm" />
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="truncate text-base font-bold text-ng-ink">{{ titulo || tituloPadrao }}</p>
                    <BadgeStatus v-if="badgeTexto" :texto="badgeTexto" :variante="badgeVariante" compacto />
                </div>
                <p v-if="subtitulo" class="mt-0.5 flex items-center gap-1 font-mono text-[11px] text-ng-ink-muted">
                    <span v-if="subtituloIcone" class="material-symbols-outlined text-[13px]">{{ subtituloIcone }}</span>
                    {{ subtitulo }}
                </p>
            </div>
        </template>

        <template #topo-direita>
            <div v-if="destaqueRotulo" class="shrink-0 text-right">
                <p class="text-[10px] font-medium uppercase tracking-wider text-ng-ink-subtle">{{ destaqueRotulo }}</p>
                <p class="font-mono text-sm font-semibold tabular-nums text-ng-ink">
                    {{ prefixoMoeda }} {{ destaqueValor }}<span v-if="destaqueSufixo" class="text-[11px] font-normal text-ng-ink-muted">{{ destaqueSufixo }}</span>
                </p>
            </div>
        </template>

        <template #centro>
            <ProgressoMeta
                class="w-full"
                :rotulo="rotuloProgresso"
                :prefixo-moeda="prefixoMoeda"
                :valor-atual="valorAtual"
                :valor-meta="valorMeta"
                :percentual="percentual"
                :cor="estiloCor.hex"
            />
        </template>

        <template #rodape-esquerda>
            <div class="flex gap-8">
                <div v-for="item in itensRodapeEsquerda" :key="item.rotulo">
                    <p class="text-[10px] font-medium uppercase tracking-wider text-ng-ink-subtle">{{ item.rotulo }}</p>
                    <p class="font-mono text-xs font-semibold text-ng-ink">{{ item.valor }}</p>
                </div>
            </div>
        </template>

        <template #rodape-direita>
            <template v-if="itemRodapeDireita">
                <p class="text-[10px] font-medium uppercase tracking-wider text-ng-ink-subtle">{{ itemRodapeDireita.rotulo }}</p>
                <p class="font-mono text-xs font-semibold text-ng-ink">{{ itemRodapeDireita.valor }}</p>
            </template>
        </template>
    </PainelVisualFinanceiro>
</template>
