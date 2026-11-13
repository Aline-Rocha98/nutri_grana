<script setup>
import CampoBase from '@/Components/Formulario/CampoBase.vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
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
    tipo: {
        type: String,
        default: 'text',
    },
    placeholder: {
        type: String,
        default: '',
    },
    maxlength: {
        type: [Number, String],
        default: null,
    },
    inputmode: {
        type: String,
        default: 'text',
    },
    autocomplete: {
        type: String,
        default: 'off',
    },
    alinhamento: {
        type: String,
        default: 'esquerda',
    },
    mono: {
        type: Boolean,
        default: false,
    },
    somenteDigitos: {
        type: Boolean,
        default: false,
    },
    focoAutomatico: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

function aoDigitar(evento) {
    let valor = evento.target.value;

    if (props.somenteDigitos) {
        valor = valor.replace(/\D/g, '');
        evento.target.value = valor;
    }

    emit('update:modelValue', valor);
}
</script>

<template>
    <CampoBase :id="id" :rotulo="rotulo" :obrigatorio="obrigatorio" :dica="dica" :erro="erro">
        <input
            :id="id || undefined"
            :value="modelValue"
            :type="tipo"
            :placeholder="placeholder"
            :maxlength="maxlength"
            :inputmode="inputmode"
            :autocomplete="autocomplete"
            :autofocus="focoAutomatico"
            :required="obrigatorio"
            class="ng-input block w-full rounded-lg text-sm"
            :class="[
                alinhamento === 'centro' ? 'text-center' : '',
                mono ? 'font-mono tabular-nums tracking-wider' : '',
                erro ? '!border-red-500' : '',
            ]"
            @input="aoDigitar"
        >
    </CampoBase>
</template>
