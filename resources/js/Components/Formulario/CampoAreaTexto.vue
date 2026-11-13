<script setup>
import CampoBase from '@/Components/Formulario/CampoBase.vue';

defineProps({
    modelValue: {
        type: String,
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
        default: '',
    },
    linhas: {
        type: Number,
        default: 3,
    },
    maxlength: {
        type: [Number, String],
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <CampoBase :id="id" :rotulo="rotulo" :obrigatorio="obrigatorio" :dica="dica" :erro="erro">
        <textarea
            :id="id || undefined"
            :value="modelValue"
            :rows="linhas"
            :placeholder="placeholder"
            :maxlength="maxlength"
            :required="obrigatorio"
            class="ng-input ng-scroll block w-full resize-none rounded-lg text-sm"
            :class="erro ? '!border-red-500' : ''"
            @input="emit('update:modelValue', $event.target.value)"
        />
    </CampoBase>
</template>
