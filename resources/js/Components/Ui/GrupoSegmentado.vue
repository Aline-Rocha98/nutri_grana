<script setup>
import { estiloDaVariante } from '@/Helpers/varianteCor';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: null,
    },
    opcoes: {
        type: Array,
        default: () => [],
    },
    separado: {
        type: Boolean,
        default: false,
    },
    expandido: {
        type: Boolean,
        default: false,
    },
    rotulo: {
        type: String,
        default: '',
    },
    contadorEmBadge: {
        type: Boolean,
        default: false,
    },
    desabilitado: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

function selecionarOpcao(opcao) {
    if (props.desabilitado) {
        return;
    }

    emit('update:modelValue', opcao.valor ?? null);
}

function estaAtiva(opcao) {
    return (opcao.valor ?? null) === (props.modelValue ?? null);
}

function classesDaOpcao(opcao) {
    if (estaAtiva(opcao)) {
        const estilo = estiloDaVariante(opcao.variante);

        return [estilo.selecionavelAtivo, estilo.texto];
    }

    return props.separado
        ? 'border-ng-line-strong text-ng-ink-muted hover:border-ng-ink-subtle hover:text-ng-ink'
        : 'border-transparent text-ng-ink-muted hover:text-ng-ink';
}

function possuiContador(opcao) {
    return opcao.contador !== undefined && opcao.contador !== null;
}
</script>

<template>
    <div
        role="radiogroup"
        :aria-label="rotulo || undefined"
        :class="[
            separado ? 'flex-wrap gap-2' : 'gap-1 rounded-xl border border-ng-line-strong bg-ng-card-muted p-1',
            expandido ? 'flex w-full' : 'inline-flex',
        ]"
    >
        <button
            v-for="(opcao, indice) in opcoes"
            :key="opcao.valor ?? `opcao-${indice}`"
            type="button"
            role="radio"
            :aria-checked="estaAtiva(opcao)"
            :disabled="desabilitado"
            class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border px-3 py-1.5 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-60"
            :class="[expandido ? 'flex-1 py-2.5' : '', classesDaOpcao(opcao)]"
            @click="selecionarOpcao(opcao)"
        >
            <span v-if="opcao.icone" class="material-symbols-outlined text-[16px]">{{ opcao.icone }}</span>
            {{ opcao.rotulo }}
            <template v-if="possuiContador(opcao)">
                <span
                    v-if="contadorEmBadge"
                    class="min-w-[1.25rem] rounded-md px-1.5 py-0.5 text-center font-mono text-[10px] font-semibold"
                    :class="estaAtiva(opcao) ? 'border border-current' : 'border border-ng-line bg-ng-card text-ng-ink-subtle'"
                >
                    {{ opcao.contador }}
                </span>
                <span v-else>({{ opcao.contador }})</span>
            </template>
        </button>
    </div>
</template>
