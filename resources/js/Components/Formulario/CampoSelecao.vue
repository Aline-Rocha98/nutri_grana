<script setup>
import { computed } from 'vue';
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
    opcoes: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: '',
    },
    placeholderSelecionavel: {
        type: Boolean,
        default: false,
    },
    desabilitado: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const possuiGrupos = computed(() => props.opcoes.some((opcao) => opcao.grupo));

const opcoesAgrupadas = computed(() => {
    const grupos = new Map();

    props.opcoes.forEach((opcao) => {
        const nomeGrupo = opcao.grupo ?? '';

        if (!grupos.has(nomeGrupo)) {
            grupos.set(nomeGrupo, []);
        }

        grupos.get(nomeGrupo).push(opcao);
    });

    return Array.from(grupos, ([nome, opcoesDoGrupo]) => ({ nome, opcoes: opcoesDoGrupo }));
});

function selecionarOpcao(evento) {
    const valorSelecionado = evento.target.value;

    if (valorSelecionado === '' && props.placeholderSelecionavel) {
        emit('update:modelValue', null);
        return;
    }

    const opcao = props.opcoes.find((item) => String(item.valor) === valorSelecionado);

    emit('update:modelValue', opcao ? opcao.valor : valorSelecionado);
}
</script>

<template>
    <CampoBase :id="id" :rotulo="rotulo" :obrigatorio="obrigatorio" :dica="dica" :erro="erro">
        <select
            :id="id || undefined"
            :value="modelValue ?? ''"
            :required="obrigatorio"
            :disabled="desabilitado"
            class="ng-input block w-full rounded-lg text-sm disabled:cursor-not-allowed"
            :class="erro ? '!border-red-500' : ''"
            @change="selecionarOpcao"
        >
            <option v-if="placeholder" value="" :disabled="!placeholderSelecionavel">{{ placeholder }}</option>

            <template v-if="possuiGrupos">
                <optgroup v-for="grupo in opcoesAgrupadas" :key="grupo.nome" :label="grupo.nome">
                    <option v-for="opcao in grupo.opcoes" :key="opcao.valor" :value="opcao.valor">
                        {{ opcao.rotulo }}
                    </option>
                </optgroup>
            </template>

            <template v-else>
                <option v-for="opcao in opcoes" :key="opcao.valor" :value="opcao.valor">
                    {{ opcao.rotulo }}
                </option>
            </template>
        </select>
    </CampoBase>
</template>
