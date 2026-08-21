<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import WidgetToggles from '@/Components/Dashboard/WidgetToggles.vue';
import ResumoFinanceiroCard from '@/Components/Dashboard/ResumoFinanceiroCard.vue';
import ContasCard from '@/Components/Dashboard/ContasCard.vue';
import CartoesCard from '@/Components/Dashboard/CartoesCard.vue';
import CategoriasCard from '@/Components/Dashboard/CategoriasCard.vue';
import ReceitasDespesasChart from '@/Components/Dashboard/ReceitasDespesasChart.vue';
import MetasCard from '@/Components/Dashboard/MetasCard.vue';

const STORAGE_WIDGETS = 'nutrigrana.dashboard.widgets';
const STORAGE_PERIODO = 'nutrigrana.dashboard.periodo';

const props = defineProps({
    usuario: {
        type: Object,
        required: true,
    },
    dataHoje: {
        type: String,
        required: true,
    },
    widgetsPadrao: {
        type: Array,
        default: () => [],
    },
    periodoPadrao: {
        type: String,
        default: 'atual',
    },
    urlDados: {
        type: String,
        required: true,
    },
});

const opcoesWidgets = [
    { id: 'resumo', rotulo: 'Resumo' },
    { id: 'contas', rotulo: 'Contas' },
    { id: 'cartoes', rotulo: 'Cartões' },
    { id: 'categorias', rotulo: 'Categorias' },
    { id: 'receitas_despesas', rotulo: 'Receitas x despesas' },
    { id: 'metas', rotulo: 'Metas' },
];

const widgets = reactive({});
const periodo = ref(props.periodoPadrao);
const payload = ref({});
const carregando = ref(false);
const erro = ref('');

function carregarPreferencias() {
    let salvos = null;

    try {
        salvos = JSON.parse(localStorage.getItem(STORAGE_WIDGETS) || 'null');
    } catch {
        salvos = null;
    }

    const ids = props.widgetsPadrao.length
        ? props.widgetsPadrao
        : opcoesWidgets.map((item) => item.id);

    ids.forEach((id) => {
        widgets[id] = salvos && typeof salvos[id] === 'boolean' ? salvos[id] : true;
    });

    const periodoSalvo = localStorage.getItem(STORAGE_PERIODO);
    if (['atual', 'anterior', '3meses'].includes(periodoSalvo)) {
        periodo.value = periodoSalvo;
    }
}

function persistirPreferencias() {
    localStorage.setItem(STORAGE_WIDGETS, JSON.stringify({ ...widgets }));
    localStorage.setItem(STORAGE_PERIODO, periodo.value);
}

const widgetsAtivos = computed(() =>
    Object.entries(widgets)
        .filter(([, ativo]) => ativo)
        .map(([id]) => id),
);

async function buscarDados() {
    if (!widgetsAtivos.value.length) {
        payload.value = {};
        return;
    }

    carregando.value = true;
    erro.value = '';

    try {
        const { data } = await axios.get(props.urlDados, {
            params: {
                widgets: widgetsAtivos.value,
                periodo: periodo.value,
            },
            headers: {
                Accept: 'application/json',
            },
        });

        payload.value = data?.data ?? data ?? {};
    } catch (e) {
        erro.value = 'Não foi possível carregar o dashboard. Tente novamente.';
        payload.value = {};
    } finally {
        carregando.value = false;
    }
}

function alternarWidget(id) {
    widgets[id] = !widgets[id];
    persistirPreferencias();
    buscarDados();
}

function atualizarPeriodo(novoPeriodo) {
    periodo.value = novoPeriodo;
    persistirPreferencias();
    buscarDados();
}

onMounted(() => {
    carregarPreferencias();
    buscarDados();
});
</script>

<template>
    <Head title="Dashboard" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
                <span class="text-sm text-gray-500">{{ dataHoje }}</span>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-[#1fa67e]">
                    Olá, {{ usuario.nome }}!
                </h3>
                <p class="mt-2 text-gray-600">
                    Veja o panorama das suas finanças. Escolha abaixo o que deseja exibir.
                </p>
                <div class="mt-4">
                    <WidgetToggles
                        :widgets="widgets"
                        :opcoes="opcoesWidgets"
                        @toggle="alternarWidget"
                    />
                </div>
            </div>

            <div
                v-if="erro"
                class="rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                {{ erro }}
            </div>

            <div v-if="!widgetsAtivos.length" class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-500">
                Ative ao menos um card em “Exibir” para carregar os dados.
            </div>

            <template v-else>
                <ResumoFinanceiroCard
                    v-if="widgets.resumo"
                    :dados="payload.resumo"
                    :carregando="carregando && !payload.resumo"
                />

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <ContasCard
                        v-if="widgets.contas"
                        :dados="payload.contas"
                        :carregando="carregando && !payload.contas"
                    />
                    <CartoesCard
                        v-if="widgets.cartoes"
                        :dados="payload.cartoes"
                        :carregando="carregando && !payload.cartoes"
                    />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <CategoriasCard
                        v-if="widgets.categorias"
                        :dados="payload.categorias"
                        :carregando="carregando && !payload.categorias"
                    />
                    <MetasCard
                        v-if="widgets.metas"
                        :dados="payload.metas"
                        :carregando="carregando && !payload.metas"
                    />
                </div>

                <ReceitasDespesasChart
                    v-if="widgets.receitas_despesas"
                    :dados="payload.receitas_despesas"
                    :carregando="carregando && !payload.receitas_despesas"
                    :periodo="periodo"
                    @atualizar-periodo="atualizarPeriodo"
                />
            </template>

            <div class="flex justify-end">
                <Link href="/home" class="text-sm font-medium text-[#1fa67e] hover:underline">
                    Voltar para Home
                </Link>
            </div>
        </div>
    </AutenticadoLayout>
</template>
