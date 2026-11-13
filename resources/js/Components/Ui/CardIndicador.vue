<script setup>
import { computed } from 'vue';
import BarraProgresso from '@/Components/BarraProgresso.vue';
import { estiloDaVariante } from '@/Helpers/varianteCor';

const props = defineProps({
    rotulo: {
        type: String,
        required: true,
    },
    valor: {
        type: String,
        default: '0,00',
    },
    sinal: {
        type: String,
        default: '',
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    descricao: {
        type: String,
        default: '',
    },
    icone: {
        type: String,
        default: '',
    },
    variante: {
        type: String,
        default: 'brand',
    },
    destacado: {
        type: Boolean,
        default: false,
    },
    percentual: {
        type: Number,
        default: null,
    },
    rotuloPercentual: {
        type: String,
        default: '',
    },
    complemento: {
        type: String,
        default: '',
    },
    varianteComplemento: {
        type: String,
        default: '',
    },
    varianteValor: {
        type: String,
        default: '',
    },
});

const estilo = computed(() => estiloDaVariante(props.variante));
const classeValor = computed(() => estiloDaVariante(props.varianteValor || props.variante).texto);
const classeComplemento = computed(() => (props.varianteComplemento
    ? estiloDaVariante(props.varianteComplemento).texto
    : 'text-ng-ink-secondary'));

const estiloDestaque = computed(() => (props.destacado
    ? {
        borderColor: `${estilo.value.hex}59`,
        backgroundImage: `linear-gradient(135deg, ${estilo.value.hex}1f 0%, var(--ng-card) 70%)`,
    }
    : undefined));
</script>

<template>
    <article class="ng-card flex flex-col rounded-2xl p-5" :style="estiloDestaque">
        <div class="flex items-start justify-between gap-3">
            <p class="text-xs font-medium" :class="destacado ? estilo.texto : 'text-ng-ink-muted'">{{ rotulo }}</p>
            <span v-if="icone" class="h-7 w-7 rounded-md" :class="estilo.icone">
                <span class="material-symbols-outlined text-[16px]">{{ icone }}</span>
            </span>
        </div>

        <p class="mt-3 font-mono text-2xl font-semibold tabular-nums" :class="classeValor">
            {{ sinal }} {{ prefixoMoeda }} {{ valor }}
        </p>
        <div v-if="descricao || complemento" class="mt-1 flex items-center justify-between gap-3 text-[11px]">
            <p class="min-w-0 text-ng-ink-muted">{{ descricao }}</p>
            <span v-if="complemento" class="shrink-0 font-mono font-semibold" :class="classeComplemento">
                {{ complemento }}
            </span>
        </div>

        <div v-if="percentual !== null" class="mt-3 flex items-center gap-3">
            <BarraProgresso class="flex-1" :percentual="percentual" :cor="estilo.hex" />
            <span class="shrink-0 font-mono text-[10px]" :class="estilo.texto">
                {{ Math.round(percentual) }}% {{ rotuloPercentual }}
            </span>
        </div>
    </article>
</template>
