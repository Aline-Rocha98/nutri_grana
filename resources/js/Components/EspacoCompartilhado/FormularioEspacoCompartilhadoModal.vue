<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    espaco: {
        type: Object,
        default: null,
    },
    urlCriar: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['fechar']);

const editando = computed(() => Boolean(props.espaco));
const formulario = useForm({ nome: '' });

watch(
    () => props.aberto,
    (aberto) => {
        if (!aberto) {
            return;
        }

        formulario.clearErrors();
        formulario.nome = props.espaco?.nome ?? '';
    },
);

function fechar() {
    emit('fechar');
}

function salvarEspacoCompartilhado() {
    const opcoes = { preserveScroll: true, onSuccess: fechar };

    if (editando.value) {
        formulario.put(props.espaco.url_atualizar, opcoes);
        return;
    }

    formulario.post(props.urlCriar, opcoes);
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="md"
        icone="group"
        :titulo="editando ? 'Renomear espaço' : 'Novo espaço compartilhado'"
        subtitulo="Um espaço reúne apenas as despesas que vocês decidirem dividir."
        :texto-confirmar="editando ? 'Salvar' : 'Criar espaço'"
        :processando="formulario.processing"
        @confirmar="salvarEspacoCompartilhado"
        @fechar="fechar"
    >
        <CampoTexto
            id="espaco-compartilhado-nome"
            v-model="formulario.nome"
            rotulo="Nome do espaço"
            placeholder="Ex.: Casa, Viagem, Família"
            obrigatorio
            :maxlength="80"
            :erro="formulario.errors.nome"
            foco-automatico
        />
    </Modal>
</template>
