<script setup>
import { computed } from 'vue';
import CardPainel from '@/Components/Ui/CardPainel.vue';
import EsqueletoCarregamento from '@/Components/Ui/EsqueletoCarregamento.vue';
import LinkAcaoPainel from '@/Components/Ui/LinkAcaoPainel.vue';
import CartaoCreditoMiniResumo from '@/Components/CartaoCredito/CartaoCreditoMiniResumo.vue';

const URL_CARTOES_CREDITO = '/cartoes-credito';

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

const cartoes = computed(() => props.dados?.itens ?? []);
</script>

<template>
    <CardPainel
        titulo="Cartões de Crédito"
        subtitulo="Limites e faturas do ciclo atual"
        icone="credit_card"
        :carregando="carregando"
        :vazio="!cartoes.length"
        mensagem-vazio="Nenhum cartão ativo cadastrado."
    >
        <template #acoes>
            <LinkAcaoPainel :href="URL_CARTOES_CREDITO" rotulo="Novo Cartão" icone-esquerda="add" destacado />
            <LinkAcaoPainel :href="URL_CARTOES_CREDITO" rotulo="Ver todos" icone-direita="chevron_right" />
        </template>

        <template #carregando>
            <EsqueletoCarregamento :quantidade="2" altura="h-36" colunas="grid-cols-1 md:grid-cols-2" />
        </template>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <CartaoCreditoMiniResumo
                v-for="cartao in cartoes"
                :key="cartao.id"
                :nome="cartao.nome"
                :bandeira="cartao.bandeira"
                :bandeira-rotulo="cartao.bandeira_rotulo"
                :logo="cartao.logo"
                :limite-total="cartao.limite_total"
                :limite-disponivel="cartao.limite_disponivel"
                :fatura-atual="cartao.fatura_atual"
                :dia-vencimento="cartao.dia_vencimento_formatado"
                :percentual-utilizado="cartao.percentual_utilizado"
            />
        </div>
    </CardPainel>
</template>
