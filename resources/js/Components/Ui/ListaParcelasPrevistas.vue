<script setup>
import { computed } from 'vue';
import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';
import { descreverParcelamento } from '@/Helpers/parcelamento';
import { formatarDataIsoParaBrasileira } from '@/Helpers/periodo';
import { estiloDaVariante } from '@/Helpers/varianteCor';

const props = defineProps({
    parcelas: {
        type: Array,
        default: () => [],
    },
    titulo: {
        type: String,
        default: 'Parcelas previstas',
    },
    variante: {
        type: String,
        default: 'brand',
    },
});

const descricaoDoParcelamento = computed(() => descreverParcelamento(props.parcelas));
const classeCorDoValor = computed(() => estiloDaVariante(props.variante).texto);
</script>

<template>
    <div v-if="parcelas.length" class="ng-card-muted overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-ng-line px-4 py-2.5">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-ng-ink-subtle">{{ titulo }}</span>
            <span class="font-mono text-xs font-semibold tabular-nums" :class="classeCorDoValor">
                {{ descricaoDoParcelamento }}
            </span>
        </div>

        <ul class="max-h-56 divide-y divide-ng-line overflow-y-auto">
            <li
                v-for="parcela in parcelas"
                :key="parcela.numero"
                class="flex items-center justify-between gap-3 px-4 py-2 text-xs"
            >
                <span class="flex items-center gap-3 text-ng-ink-muted">
                    <span class="w-10 font-mono tabular-nums text-ng-ink-subtle">{{ parcela.numero }}/{{ parcela.total }}</span>
                    <span v-if="parcela.dataIso" class="font-mono tabular-nums">
                        {{ formatarDataIsoParaBrasileira(parcela.dataIso) }}
                    </span>
                </span>
                <span class="font-mono font-semibold tabular-nums text-ng-ink">
                    R$ {{ formatarNumeroParaMoeda(parcela.valor) }}
                </span>
            </li>
        </ul>
    </div>
</template>
