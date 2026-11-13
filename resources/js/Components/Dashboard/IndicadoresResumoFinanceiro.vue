<script setup>
import { computed } from 'vue';
import CardIndicador from '@/Components/Ui/CardIndicador.vue';
import EsqueletoCarregamento from '@/Components/Ui/EsqueletoCarregamento.vue';

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

const complementoVencimento = computed(() => (props.dados?.proximo_dia_vencimento
    ? `Dia ${props.dados.proximo_dia_vencimento}`
    : '—'));
</script>

<template>
    <EsqueletoCarregamento
        v-if="carregando"
        :quantidade="4"
        altura="h-32"
        colunas="grid-cols-1 sm:grid-cols-2 xl:grid-cols-4"
    />

    <div v-else-if="dados" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <CardIndicador
            rotulo="Dinheiro Disponível"
            :valor="dados.dinheiro_disponivel"
            :variante-valor="dados.dinheiro_disponivel_numero < 0 ? 'red' : 'brand'"
            descricao="Saldo livre nas contas"
            complemento="Livre p/ uso"
            variante-complemento="brand"
            icone="account_balance_wallet"
        />
        <CardIndicador
            rotulo="Dinheiro Guardado"
            :valor="dados.dinheiro_guardado"
            variante="sky"
            variante-valor="zinc"
            descricao="Separado pelo plano financeiro das rendas"
            complemento="Guardado"
            variante-complemento="sky"
            icone="savings"
        />
        <CardIndicador
            rotulo="Limite Livre em Cartões"
            :valor="dados.limite_livre_cartoes"
            variante="zinc"
            :descricao="`de R$ ${dados.limite_total_configurado} configurados`"
            :complemento="`${Math.round(dados.percentual_livre)}% livre`"
            variante-complemento="brand"
            icone="credit_card"
        />
        <CardIndicador
            rotulo="Faturas em Aberto"
            :valor="dados.faturas_em_aberto"
            variante="red"
            descricao="Próximo vencimento"
            :complemento="complementoVencimento"
            icone="trending_up"
        />
    </div>
</template>
