<script setup>
import { computed } from 'vue';
import CardPainel from '@/Components/Ui/CardPainel.vue';
import EsqueletoCarregamento from '@/Components/Ui/EsqueletoCarregamento.vue';
import LinkAcaoPainel from '@/Components/Ui/LinkAcaoPainel.vue';
import ContaBancariaLinhaResumo from '@/Components/ContaBancaria/ContaBancariaLinhaResumo.vue';

const URL_CONTAS_BANCARIAS = '/contas-bancarias';

const props = defineProps({
    dados: {
        type: Object,
        default: null,
    },
    carregando: {
        type: Boolean,
        default: false,
    },
});

const contas = computed(() => props.dados?.itens ?? []);
</script>

<template>
    <CardPainel
        titulo="Minhas Contas"
        subtitulo="Saldos disponíveis em tempo real"
        icone="account_balance"
        :carregando="carregando"
        :vazio="!contas.length"
        mensagem-vazio="Nenhuma conta marcada para o resumo."
    >
        <template #acoes>
            <LinkAcaoPainel :href="URL_CONTAS_BANCARIAS" rotulo="Nova Conta" icone-esquerda="add" destacado />
            <LinkAcaoPainel :href="URL_CONTAS_BANCARIAS" rotulo="Ver todas" icone-direita="chevron_right" />
        </template>

        <template #carregando>
            <EsqueletoCarregamento :quantidade="3" altura="h-16" />
        </template>

        <div class="space-y-3">
            <ContaBancariaLinhaResumo
                v-for="conta in contas"
                :key="conta.id"
                :nome="conta.nome"
                :tipo="conta.tipo"
                :tipo-rotulo="conta.tipo_rotulo"
                :logo="conta.logo"
                :saldo-disponivel="conta.saldo_disponivel"
                :saldo-disponivel-numero="conta.saldo_disponivel_numero"
            />
        </div>
    </CardPainel>
</template>
