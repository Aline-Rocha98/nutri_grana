<script setup>
import { computed } from 'vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import IconeCategoria from '@/Components/Ui/IconeCategoria.vue';
import PainelVisualFinanceiro from '@/Components/Ui/PainelVisualFinanceiro.vue';

const ROTULOS_PADRAO = {
    semDescricao: 'Nova cotação',
    semFornecedor: 'Fornecedor não informado',
    badge: 'Cotação',
    validade: 'Válida até',
    origem: 'Pagamento via',
};

const props = defineProps({
    descricao: {
        type: String,
        default: '',
    },
    fornecedor: {
        type: String,
        default: '',
    },
    categoria: {
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
    modalidade: {
        type: String,
        default: '',
    },
    validade: {
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
</script>

<template>
    <PainelVisualFinanceiro>
        <template #topo-esquerda>
            <IconeCategoria icone="request_quote" cor="#1fa67e" tamanho="sm" />
            <div class="min-w-0">
                <p class="truncate text-xs font-bold uppercase tracking-wide text-ng-brand">
                    {{ fornecedor || textos.semFornecedor }}
                </p>
                <p v-if="categoria" class="truncate text-[11px] text-ng-ink-muted">{{ categoria }}</p>
            </div>
        </template>

        <template #topo-direita>
            <BadgeStatus :texto="textos.badge" variante="blue" compacto />
        </template>

        <template #centro>
            <div class="min-w-0">
                <p class="truncate text-xs text-ng-ink-secondary">{{ descricao || textos.semDescricao }}</p>
                <p class="truncate font-mono text-3xl font-semibold tabular-nums text-ng-ink">
                    {{ prefixoMoeda }} {{ valor }}
                </p>
            </div>
            <p v-if="modalidade" class="shrink-0 font-mono text-xs text-ng-ink-subtle">{{ modalidade }}</p>
        </template>

        <template #rodape-esquerda>
            <p class="text-[10px] font-medium uppercase tracking-wider text-ng-ink-subtle">
                {{ textos.validade }}:
                <span class="ml-1 font-mono text-xs font-semibold normal-case tracking-normal text-ng-ink">{{ validade || '—' }}</span>
            </p>
        </template>

        <template #rodape-direita>
            <p class="text-[10px] font-medium uppercase tracking-wider text-ng-ink-subtle">
                {{ textos.origem }}:
                <span class="ml-1 text-xs font-semibold normal-case tracking-normal text-ng-ink">{{ origem || '—' }}</span>
            </p>
        </template>
    </PainelVisualFinanceiro>
</template>
