<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import CardMetaCompacta from '@/Components/Ui/CardMetaCompacta.vue';
import CardPainel from '@/Components/Ui/CardPainel.vue';
import EsqueletoCarregamento from '@/Components/Ui/EsqueletoCarregamento.vue';
import LinkAcaoPainel from '@/Components/Ui/LinkAcaoPainel.vue';

const URL_OBJETIVOS = '/objetivos';

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

const metas = computed(() => props.dados?.itens ?? []);
const rotuloVerTodas = computed(() => `Ver todas (${props.dados?.total_objetivos ?? metas.value.length})`);

function metaEstaConcluida(meta) {
    return Number(meta.percentual_atual) >= 100;
}
</script>

<template>
    <CardPainel
        titulo="Metas & Objetivos em Foco"
        subtitulo="Acompanhe seu progresso e faça aportes rápidos"
        icone="track_changes"
        :carregando="carregando"
        :vazio="!metas.length"
        mensagem-vazio="Nenhum objetivo marcado para o dashboard."
    >
        <template #acoes>
            <LinkAcaoPainel :href="URL_OBJETIVOS" rotulo="Nova Meta" icone-esquerda="add" destacado />
            <LinkAcaoPainel :href="URL_OBJETIVOS" :rotulo="rotuloVerTodas" icone-direita="chevron_right" />
        </template>

        <template #carregando>
            <EsqueletoCarregamento :quantidade="3" altura="h-36" colunas="grid-cols-1 md:grid-cols-3" />
        </template>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <CardMetaCompacta
                v-for="meta in metas"
                :key="meta.id"
                :titulo="meta.descricao"
                :valor-atual="meta.valor_guardado"
                :valor-meta="meta.valor_meta"
                :percentual="meta.percentual_atual"
                :valor-sugerido="metaEstaConcluida(meta) ? '' : meta.deposito_mensal_sugerido"
            >
                <template v-if="!metaEstaConcluida(meta)" #acao>
                    <Link
                        :href="URL_OBJETIVOS"
                        class="ng-selecionavel ng-selecionavel--ativo inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-ng-brand"
                    >
                        <span class="material-symbols-outlined text-[16px]">savings</span>
                        Aportar
                    </Link>
                </template>
            </CardMetaCompacta>
        </div>
    </CardPainel>
</template>
