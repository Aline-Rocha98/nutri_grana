<script setup>
import { computed } from 'vue';

const props = defineProps({
    variante: {
        type: String,
        default: 'info',
    },
    titulo: {
        type: String,
        default: '',
    },
    mensagem: {
        type: String,
        default: '',
    },
    icone: {
        type: String,
        default: '',
    },
});

const ICONES_POR_VARIANTE = {
    success: 'check',
    info: 'info',
    warning: 'priority_high',
    danger: 'error',
};

const varianteAplicada = computed(() => (ICONES_POR_VARIANTE[props.variante] ? props.variante : 'info'));

const iconeAplicado = computed(() => props.icone || ICONES_POR_VARIANTE[varianteAplicada.value]);
</script>

<template>
    <div class="ng-alert" :class="`ng-alert--${varianteAplicada}`" role="status">
        <span class="ng-alert__icon" aria-hidden="true">
            <span class="material-symbols-outlined">{{ iconeAplicado }}</span>
        </span>
        <p class="min-w-0 self-center">
            <span v-if="titulo" class="font-semibold">{{ titulo }} </span>
            <slot>{{ mensagem }}</slot>
        </p>
    </div>
</template>
