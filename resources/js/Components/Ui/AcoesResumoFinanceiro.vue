<script setup>
import { computed } from 'vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';

const ROTULOS_PADRAO = {
    definirPadrao: 'Definir como padrão',
    arquivar: 'Arquivar',
    desarquivar: 'Desarquivar',
    editar: 'Editar',
    excluir: 'Excluir',
    excluirBloqueado: 'Não é possível excluir',
};

const props = defineProps({
    padrao: {
        type: Boolean,
        default: false,
    },
    arquivado: {
        type: Boolean,
        default: false,
    },
    podeExcluir: {
        type: Boolean,
        default: true,
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['definir-padrao', 'arquivar', 'editar', 'excluir']);

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));
</script>

<template>
    <BotaoIcone
        v-if="!padrao && !arquivado"
        icone="star"
        :rotulo="textos.definirPadrao"
        parar-propagacao
        @click="emit('definir-padrao')"
    />
    <BotaoIcone
        :icone="arquivado ? 'lock_open' : 'lock'"
        :rotulo="arquivado ? textos.desarquivar : textos.arquivar"
        parar-propagacao
        @click="emit('arquivar')"
    />
    <BotaoIcone icone="edit" :rotulo="textos.editar" parar-propagacao @click="emit('editar')" />
    <BotaoIcone
        icone="delete"
        variante="perigo"
        :rotulo="podeExcluir ? textos.excluir : textos.excluirBloqueado"
        :desabilitado="!podeExcluir"
        parar-propagacao
        @click="emit('excluir')"
    />
</template>
