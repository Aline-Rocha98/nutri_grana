<script setup>
import { computed } from 'vue';
import CampoBase from '@/Components/Formulario/CampoBase.vue';
import { aoDigitarMoeda } from '@/Helpers/mascaraMoeda';
import { estiloDaVariante } from '@/Helpers/varianteCor';

const props = defineProps({
    modelValue: {
        type: String,
        default: '0,00',
    },
    id: {
        type: String,
        default: '',
    },
    rotulo: {
        type: String,
        default: '',
    },
    obrigatorio: {
        type: Boolean,
        default: false,
    },
    dica: {
        type: String,
        default: '',
    },
    erro: {
        type: String,
        default: '',
    },
    prefixo: {
        type: String,
        default: 'R$',
    },
    placeholder: {
        type: String,
        default: '0,00',
    },
    atalhos: {
        type: Array,
        default: () => [],
    },
    desabilitado: {
        type: Boolean,
        default: false,
    },
    variante: {
        type: String,
        default: 'brand',
    },
});

const emit = defineEmits(['update:modelValue']);

const classeCorPrefixo = computed(() => estiloDaVariante(props.variante).texto);
</script>

<template>
    <CampoBase :id="id" :rotulo="rotulo" :obrigatorio="obrigatorio" :dica="dica" :erro="erro">
        <template v-if="atalhos.length > 0 && !desabilitado" #rotulo-extra>
            <div class="flex items-center gap-2">
                <button
                    v-for="atalho in atalhos"
                    :key="atalho.rotulo"
                    type="button"
                    class="text-[11px] font-medium text-ng-ink-subtle underline-offset-2 transition hover:text-ng-brand hover:underline"
                    @click="emit('update:modelValue', atalho.valor)"
                >
                    {{ atalho.rotulo }}
                </button>
            </div>
        </template>

        <div class="relative">
            <span
                v-if="prefixo"
                class="pointer-events-none absolute inset-y-0 left-3 flex items-center font-mono text-sm font-semibold"
                :class="classeCorPrefixo"
            >
                {{ prefixo }}
            </span>
            <input
                :id="id || undefined"
                :value="modelValue"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                :placeholder="placeholder"
                :required="obrigatorio"
                :disabled="desabilitado"
                class="ng-input block w-full rounded-lg font-mono text-sm tabular-nums"
                :class="[prefixo ? 'pl-11' : '', erro ? '!border-red-500' : '']"
                @input="emit('update:modelValue', aoDigitarMoeda($event))"
            >
        </div>
    </CampoBase>
</template>
