<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';
import BarraDistribuicaoSegmentada from '@/Components/Ui/BarraDistribuicaoSegmentada.vue';
import FluxoCapacidadeFinanceira from '@/Components/Renda/FluxoCapacidadeFinanceira.vue';
import LinhaItemPlanoFinanceiro from '@/Components/PlanoFinanceiro/LinhaItemPlanoFinanceiro.vue';
import {
    calcularCapacidadeFinanceiraDoPlano,
    distribuirValoresDoPlano,
    montarSegmentosDistribuicao,
} from '@/Helpers/planoFinanceiro';

const ROTULOS_PADRAO = {
    plano: 'Plano financeiro',
    semPlano: 'Sem plano financeiro',
    padrao: 'padrão',
    gerenciar: 'Gerenciar planos',
    nenhumPlano: 'Você ainda não tem planos financeiros ativos.',
    criarPlano: 'Criar plano',
    capacidade: 'Capacidade financeira desta renda',
    dicaSemSeparacao: 'Este plano é só orientativo: nenhum item separa valores do saldo.',
};

const idPlanoSelecionado = defineModel({ type: [Number, String], default: null });

const props = defineProps({
    id: {
        type: String,
        default: 'plano-financeiro',
    },
    planos: {
        type: Array,
        default: () => [],
    },
    valorRenda: {
        type: Number,
        default: 0,
    },
    urlPlanosFinanceiros: {
        type: String,
        default: '',
    },
    erro: {
        type: String,
        default: '',
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));

const opcoesPlanos = computed(() => props.planos.map((plano) => ({
    valor: plano.id,
    rotulo: plano.padrao ? `${plano.nome} (${textos.value.padrao})` : plano.nome,
})));

const planoSelecionado = computed(() => props.planos
    .find((plano) => plano.id === idPlanoSelecionado.value) ?? null);

const itensComValores = computed(() => distribuirValoresDoPlano(
    planoSelecionado.value?.itens ?? [],
    props.valorRenda,
));

const segmentos = computed(() => montarSegmentosDistribuicao(planoSelecionado.value?.itens ?? []));

const capacidadeFinanceira = computed(() => calcularCapacidadeFinanceiraDoPlano(
    planoSelecionado.value?.itens ?? [],
    props.valorRenda,
));

const possuiItemQueSepara = computed(() => itensComValores.value.some((item) => item.separar_do_saldo));
</script>

<template>
    <div class="space-y-3">
        <CampoSelecao
            :id="id"
            v-model="idPlanoSelecionado"
            :rotulo="textos.plano"
            :placeholder="textos.semPlano"
            placeholder-selecionavel
            :opcoes="opcoesPlanos"
            :erro="erro"
        />

        <div v-if="!planos.length" class="ng-card-muted flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-xs text-ng-ink-muted">
            <span>{{ textos.nenhumPlano }}</span>
            <Link v-if="urlPlanosFinanceiros" :href="urlPlanosFinanceiros" class="font-semibold text-ng-brand hover:underline">
                {{ textos.criarPlano }}
            </Link>
        </div>

        <template v-else-if="planoSelecionado">
            <BarraDistribuicaoSegmentada :segmentos="segmentos" />

            <div class="overflow-hidden rounded-xl border border-ng-line bg-ng-card divide-y divide-ng-line">
                <LinhaItemPlanoFinanceiro
                    v-for="item in itensComValores"
                    :key="item.id"
                    :nome="item.nome"
                    :regra="item.regra_formatada"
                    :valor-calculado="item.valor_calculado"
                    :separar-do-saldo="item.separar_do_saldo"
                    :conta-destino="item.conta_bancaria_destino_nome ?? ''"
                    :objetivo="item.objetivo_descricao ?? ''"
                    :cor="item.cor_hexadecimal"
                />
            </div>

            <div v-if="possuiItemQueSepara" class="space-y-2">
                <p class="text-xs font-medium text-ng-ink-muted">{{ textos.capacidade }}</p>
                <FluxoCapacidadeFinanceira
                    :valor-renda="capacidadeFinanceira.renda"
                    :valor-destinado="capacidadeFinanceira.total_destinado"
                    :valor-capacidade="capacidadeFinanceira.capacidade"
                    :percentual-destinado="capacidadeFinanceira.percentual_destinado"
                    compacto
                />
            </div>
            <p v-else class="text-xs text-ng-ink-subtle">{{ textos.dicaSemSeparacao }}</p>
        </template>

        <div v-if="planos.length && urlPlanosFinanceiros" class="flex justify-end">
            <Link :href="urlPlanosFinanceiros" class="inline-flex items-center gap-1 text-xs font-semibold text-ng-brand hover:underline">
                <span class="material-symbols-outlined text-[16px]">tune</span>
                {{ textos.gerenciar }}
            </Link>
        </div>
    </div>
</template>
