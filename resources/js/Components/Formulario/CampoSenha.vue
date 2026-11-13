<script setup>
import { ref } from 'vue';
import CampoBase from '@/Components/Formulario/CampoBase.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';

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
    autocomplete: {
        type: String,
        default: 'current-password',
    },
    focoAutomatico: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const senhaVisivel = ref(false);

function alternarVisibilidadeSenha() {
    senhaVisivel.value = !senhaVisivel.value;
}
</script>

<template>
    <CampoBase :id="id" :rotulo="rotulo" :obrigatorio="obrigatorio" :dica="dica" :erro="erro">
        <template v-if="$slots['rotulo-extra']" #rotulo-extra>
            <slot name="rotulo-extra" />
        </template>

        <div class="relative">
            <input
                :id="id || undefined"
                :value="modelValue"
                :type="senhaVisivel ? 'text' : 'password'"
                :placeholder="placeholder"
                :autocomplete="autocomplete"
                :autofocus="focoAutomatico"
                :required="obrigatorio"
                class="ng-input block w-full rounded-lg pr-11 text-sm"
                :class="erro ? '!border-red-500' : ''"
                @input="emit('update:modelValue', $event.target.value)"
            >
            <div class="absolute inset-y-0 right-1.5 flex items-center">
                <BotaoIcone
                    :icone="senhaVisivel ? 'visibility_off' : 'visibility'"
                    :rotulo="senhaVisivel ? 'Ocultar senha' : 'Mostrar senha'"
                    @click="alternarVisibilidadeSenha"
                />
            </div>
        </div>
    </CampoBase>
</template>
