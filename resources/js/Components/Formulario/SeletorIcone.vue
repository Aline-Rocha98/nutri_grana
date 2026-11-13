<script setup>
import CampoBase from '@/Components/Formulario/CampoBase.vue';

defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    opcoes: {
        type: Array,
        default: () => [],
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
    cor: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <CampoBase :rotulo="rotulo" :obrigatorio="obrigatorio" :dica="dica" :erro="erro">
        <div class="grid grid-cols-6 gap-2 sm:grid-cols-8" role="radiogroup" :aria-label="rotulo || undefined">
            <button
                v-for="opcao in opcoes"
                :key="opcao.valor"
                type="button"
                role="radio"
                :aria-checked="modelValue === opcao.valor"
                :aria-label="opcao.rotulo"
                :title="opcao.rotulo"
                class="ng-selecionavel flex aspect-square items-center justify-center rounded-xl"
                :class="modelValue === opcao.valor ? 'ng-selecionavel--ativo' : 'text-ng-ink-muted hover:text-ng-ink'"
                :style="modelValue === opcao.valor && cor ? { color: cor } : undefined"
                @click="emit('update:modelValue', opcao.valor)"
            >
                <span class="material-symbols-outlined text-[20px]">{{ opcao.valor }}</span>
            </button>
        </div>
    </CampoBase>
</template>
