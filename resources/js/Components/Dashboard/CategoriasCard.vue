<script setup>
import { computed } from 'vue';
import CardPainel from '@/Components/Ui/CardPainel.vue';
import EsqueletoCarregamento from '@/Components/Ui/EsqueletoCarregamento.vue';
import LinkAcaoPainel from '@/Components/Ui/LinkAcaoPainel.vue';
import LinhaProgressoCategoria from '@/Components/Ui/LinhaProgressoCategoria.vue';

const URL_LANCAMENTOS = '/lancamentos';

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

const categorias = computed(() => props.dados?.itens ?? []);

function dicaLimiteCategoria(categoria) {
    if (!categoria.limite) {
        return '';
    }

    return `Limite: R$ ${categoria.limite} · Uso: ${categoria.percentual}%`;
}
</script>

<template>
    <CardPainel
        titulo="Despesas por Categoria"
        subtitulo="Onde seu dinheiro está sendo concentrado este mês"
        icone="donut_large"
        :carregando="carregando"
        :vazio="!categorias.length"
        mensagem-vazio="Nenhuma despesa neste mês."
    >
        <template #acoes>
            <LinkAcaoPainel :href="URL_LANCAMENTOS" rotulo="Ver extrato" icone-direita="chevron_right" />
        </template>

        <template #carregando>
            <EsqueletoCarregamento :quantidade="4" altura="h-9" />
        </template>

        <div class="space-y-4">
            <LinhaProgressoCategoria
                v-for="categoria in categorias"
                :key="categoria.id_categoria"
                :nome="categoria.nome"
                :icone="categoria.icone"
                :cor="categoria.cor"
                :valor="categoria.valor"
                :percentual="categoria.percentual_participacao"
                :ultrapassado="categoria.ultrapassado"
                :dica="dicaLimiteCategoria(categoria)"
            />
        </div>

        <template #rodape>
            <div class="flex items-center justify-between gap-3 font-mono text-xs">
                <span class="text-ng-ink-muted">Total de despesas do ciclo:</span>
                <span class="font-semibold tabular-nums text-rose-500">R$ {{ dados?.total }}</span>
            </div>
        </template>
    </CardPainel>
</template>
