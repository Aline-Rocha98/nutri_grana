<script setup>
import { computed } from 'vue';
import BarraProgresso from '@/Components/BarraProgresso.vue';
import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';

const ROTULOS_PADRAO = {
    renda: 'Renda',
    separado: 'Plano financeiro',
    capacidade: 'Capacidade financeira',
    descricaoRenda: 'Valor esperado',
    descricaoSeparado: 'Guardado ao confirmar a renda',
    descricaoCapacidade: 'Disponível para lançamentos e orçamentos',
    percentualDestinado: 'da renda separada',
};

const props = defineProps({
    valorRenda: {
        type: Number,
        default: 0,
    },
    valorDestinado: {
        type: Number,
        default: 0,
    },
    valorCapacidade: {
        type: Number,
        default: 0,
    },
    percentualDestinado: {
        type: Number,
        default: 0,
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    compacto: {
        type: Boolean,
        default: false,
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));

const etapas = computed(() => [
    {
        chave: 'renda',
        rotulo: textos.value.renda,
        descricao: textos.value.descricaoRenda,
        valor: props.valorRenda,
        icone: 'payments',
        classeValor: 'text-ng-ink',
        classeIcone: 'bg-ng-brand-soft text-ng-brand',
    },
    {
        chave: 'separado',
        rotulo: textos.value.separado,
        descricao: textos.value.descricaoSeparado,
        valor: props.valorDestinado,
        sinal: '−',
        icone: 'savings',
        classeValor: 'text-sky-500',
        classeIcone: 'bg-sky-500/10 text-sky-500',
    },
    {
        chave: 'capacidade',
        rotulo: textos.value.capacidade,
        descricao: textos.value.descricaoCapacidade,
        valor: props.valorCapacidade,
        sinal: '=',
        icone: 'account_balance_wallet',
        classeValor: props.valorCapacidade < 0 ? 'text-red-500' : 'text-ng-brand',
        classeIcone: 'bg-ng-brand-soft text-ng-brand',
    },
]);
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-col items-stretch gap-2 md:flex-row md:items-center">
            <template v-for="(etapa, indice) in etapas" :key="etapa.chave">
                <article
                    class="ng-card-muted flex flex-1 items-center gap-3 rounded-xl"
                    :class="compacto ? 'px-3 py-2' : 'px-4 py-3'"
                >
                    <span
                        v-if="!compacto"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                        :class="etapa.classeIcone"
                    >
                        <span class="material-symbols-outlined text-[18px]">{{ etapa.icone }}</span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-ng-ink-muted">{{ etapa.rotulo }}</p>
                        <p
                            class="font-mono font-semibold tabular-nums"
                            :class="[etapa.classeValor, compacto ? 'text-sm' : 'text-lg']"
                        >
                            {{ prefixoMoeda }} {{ formatarNumeroParaMoeda(etapa.valor) }}
                        </p>
                        <p v-if="!compacto" class="truncate text-[11px] text-ng-ink-subtle">{{ etapa.descricao }}</p>
                    </div>
                </article>

                <span
                    v-if="indice < etapas.length - 1"
                    class="material-symbols-outlined self-center text-[18px] text-ng-ink-subtle md:rotate-0 rotate-90"
                    aria-hidden="true"
                >
                    arrow_forward
                </span>
            </template>
        </div>

        <div v-if="!compacto" class="flex items-center gap-3">
            <BarraProgresso class="flex-1" :percentual="Math.min(100, percentualDestinado)" cor="#0ea5e9" />
            <span class="shrink-0 font-mono text-[11px] text-sky-500">
                {{ percentualDestinado.toLocaleString('pt-BR') }}% {{ textos.percentualDestinado }}
            </span>
        </div>
    </div>
</template>
