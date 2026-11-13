<script setup>
import { computed } from 'vue';
import LinhaLancamento from '@/Components/Ui/LinhaLancamento.vue';
import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';

const ROTULOS_PADRAO = {
    guardado: 'Guardado',
    orientativo: 'Orientativo',
    porRecebimento: 'por recebimento',
    objetivo: 'Objetivo',
};

const props = defineProps({
    nome: {
        type: String,
        required: true,
    },
    regra: {
        type: String,
        default: '',
    },
    valorCalculado: {
        type: Number,
        default: 0,
    },
    separarDoSaldo: {
        type: Boolean,
        default: false,
    },
    contaDestino: {
        type: String,
        default: '',
    },
    objetivo: {
        type: String,
        default: '',
    },
    cor: {
        type: String,
        default: '#0ea5e9',
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));

const subtitulo = computed(() => [
    props.regra,
    props.separarDoSaldo ? props.contaDestino : null,
    props.separarDoSaldo && props.objetivo ? `${textos.value.objetivo}: ${props.objetivo}` : null,
].filter(Boolean).join(' · '));
</script>

<template>
    <LinhaLancamento
        :descricao="nome"
        :subtitulo="subtitulo"
        :valor="formatarNumeroParaMoeda(valorCalculado)"
        :legenda-valor="textos.porRecebimento"
        :icone="separarDoSaldo ? 'savings' : 'explore'"
        :cor="cor"
        :badge-texto="separarDoSaldo ? textos.guardado : textos.orientativo"
        :badge-variante="separarDoSaldo ? 'sky' : 'zinc'"
        badge-na-descricao
    />
</template>
