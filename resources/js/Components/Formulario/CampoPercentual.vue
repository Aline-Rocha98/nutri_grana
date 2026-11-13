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
    placeholder: {
        type: String,
        default: '0',
    },
    maximo: {
        type: Number,
        default: 100,
    },
    desabilitado: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

function limitarPercentualDigitado(textoDigitado) {
    const [parteInteira = '', ...partesDecimais] = String(textoDigitado)
        .replace(',', '.')
        .replace(/[^\d.]/g, '')
        .split('.');

    const parteDecimal = partesDecimais.join('').slice(0, 2);
    const textoNormalizado = partesDecimais.length ? `${parteInteira}.${parteDecimal}` : parteInteira;

    return Number(textoNormalizado) > props.maximo ? String(props.maximo) : textoNormalizado;
}

function aoDigitarPercentual(evento) {
    const percentualLimitado = limitarPercentualDigitado(evento.target.value);
    evento.target.value = percentualLimitado;
    emit('update:modelValue', percentualLimitado);
}
</script>

<template>
    <CampoBase :id="id" :rotulo="rotulo" :obrigatorio="obrigatorio" :dica="dica" :erro="erro">
        <div class="relative">
            <input
                :id="id || undefined"
                :value="modelValue"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                :placeholder="placeholder"
                :required="obrigatorio"
                :disabled="desabilitado"
                class="ng-input block w-full rounded-lg pr-8 text-right font-mono text-sm tabular-nums"
                :class="erro ? '!border-red-500' : ''"
                @input="aoDigitarPercentual"
            >
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center font-mono text-sm font-semibold text-ng-ink-muted">%</span>
        </div>
    </CampoBase>
</template>
