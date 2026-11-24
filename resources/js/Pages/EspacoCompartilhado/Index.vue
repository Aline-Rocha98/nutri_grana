<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import BotaoAdicionarInline from '@/Components/Ui/BotaoAdicionarInline.vue';
import CabecalhoSecao from '@/Components/Ui/CabecalhoSecao.vue';
import CardAdicionarPlaceholder from '@/Components/Ui/CardAdicionarPlaceholder.vue';
import CardEspacoCompartilhado from '@/Components/EspacoCompartilhado/CardEspacoCompartilhado.vue';
import FormularioEspacoCompartilhadoModal from '@/Components/EspacoCompartilhado/FormularioEspacoCompartilhadoModal.vue';
import ListaConvitesRecebidos from '@/Components/EspacoCompartilhado/ListaConvitesRecebidos.vue';

defineProps({
    espacos: { type: Array, default: () => [] },
    convitesRecebidos: { type: Array, default: () => [] },
    urlCriarEspaco: { type: String, required: true },
});

const modalCriarEspacoAberto = ref(false);
</script>

<template>
    <Head title="Espaços compartilhados" />

    <AutenticadoLayout titulo="Compartilhados">
        <div class="space-y-6 p-6 lg:p-8">
            <CabecalhoSecao
                tamanho="pagina"
                titulo="Espaços compartilhados"
                subtitulo="Divida despesas com quem você confia, sem expor suas finanças pessoais."
            >
                <template #acoes>
                    <BotaoAdicionarInline rotulo="Novo espaço" @click="modalCriarEspacoAberto = true" />
                </template>
            </CabecalhoSecao>

            <ListaConvitesRecebidos :convites="convitesRecebidos" />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <CardEspacoCompartilhado v-for="espaco in espacos" :key="espaco.chave" :espaco="espaco" />
                <CardAdicionarPlaceholder
                    titulo="Criar espaço compartilhado"
                    descricao="Ex.: Casa, Viagem, Família"
                    @click="modalCriarEspacoAberto = true"
                />
            </div>
        </div>

        <FormularioEspacoCompartilhadoModal
            :aberto="modalCriarEspacoAberto"
            :url-criar="urlCriarEspaco"
            @fechar="modalCriarEspacoAberto = false"
        />
    </AutenticadoLayout>
</template>
