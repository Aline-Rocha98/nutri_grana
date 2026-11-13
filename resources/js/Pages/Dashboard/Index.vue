<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import BotaoAdicionarInline from '@/Components/Ui/BotaoAdicionarInline.vue';
import CabecalhoBoasVindas from '@/Components/Dashboard/CabecalhoBoasVindas.vue';
import IndicadoresResumoFinanceiro from '@/Components/Dashboard/IndicadoresResumoFinanceiro.vue';
import ContasCard from '@/Components/Dashboard/ContasCard.vue';
import CartoesCard from '@/Components/Dashboard/CartoesCard.vue';
import CategoriasCard from '@/Components/Dashboard/CategoriasCard.vue';
import ReceitasDespesasCard from '@/Components/Dashboard/ReceitasDespesasCard.vue';
import MetasCard from '@/Components/Dashboard/MetasCard.vue';

const STORAGE_WIDGETS = 'nutrigrana.dashboard.widgets';
const STORAGE_PERIODO = 'nutrigrana.dashboard.periodo';
const STORAGE_MODULOS_OCULTOS = 'nutrigrana.dashboard.modulos-ocultos';
const PERIODOS_VALIDOS = ['atual', 'anterior', '3meses'];
const WIDGET_RECEITAS_DESPESAS = 'receitas_despesas';
const URL_LANCAMENTOS = '/lancamentos';

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
    { id: 'resumo', rotulo: 'Resumo Financeiro' },
    { id: 'contas', rotulo: 'Contas Bancárias' },
    { id: 'cartoes', rotulo: 'Cartões de Crédito' },
    { id: 'categorias', rotulo: 'Despesas por Categoria' },
    { id: WIDGET_RECEITAS_DESPESAS, rotulo: 'Receitas x Despesas' },
    { id: 'metas', rotulo: 'Objetivos' },
];

const widgets = reactive({});
const payload = reactive({});
const widgetsCarregando = reactive(new Set());
const versaoPorWidget = {};
const periodo = ref(props.periodoPadrao);
const modulosOcultos = ref(false);
const erro = ref('');
let controladorPeriodo = null;

const widgetsAtivos = computed(() =>
    Object.entries(widgets)
        .filter(([, ativo]) => ativo)
        .map(([id]) => id),
);

function lerJsonDoStorage(chave) {
    try {
        return JSON.parse(localStorage.getItem(chave) || 'null');
    } catch {
        return null;
    }
}

function carregarPreferencias() {
    const salvos = lerJsonDoStorage(STORAGE_WIDGETS);
    const ids = props.widgetsPadrao.length
        ? props.widgetsPadrao
        : opcoesWidgets.map((item) => item.id);

    ids.forEach((id) => {
        widgets[id] = salvos && typeof salvos[id] === 'boolean' ? salvos[id] : true;
    });

    const periodoSalvo = localStorage.getItem(STORAGE_PERIODO);
    if (PERIODOS_VALIDOS.includes(periodoSalvo)) {
        periodo.value = periodoSalvo;
    }

    modulosOcultos.value = lerJsonDoStorage(STORAGE_MODULOS_OCULTOS) === true;
}

function persistirPreferencias() {
    localStorage.setItem(STORAGE_WIDGETS, JSON.stringify({ ...widgets }));
    localStorage.setItem(STORAGE_PERIODO, periodo.value);
    localStorage.setItem(STORAGE_MODULOS_OCULTOS, JSON.stringify(modulosOcultos.value));
}

function registrarNovaVersao(listaWidgets) {
    return Object.fromEntries(listaWidgets.map((widget) => {
        versaoPorWidget[widget] = (versaoPorWidget[widget] ?? 0) + 1;
        widgetsCarregando.add(widget);

        return [widget, versaoPorWidget[widget]];
    }));
}

function ehVersaoAtual(widget, versoes) {
    return versoes[widget] === versaoPorWidget[widget];
}

async function buscarWidgets(listaWidgets, controlador = null) {
    if (!listaWidgets.length) {
        return;
    }

    const versoes = registrarNovaVersao(listaWidgets);
    erro.value = '';

    try {
        const { data } = await axios.get(props.urlDados, {
            params: {
                widgets: listaWidgets,
                periodo: periodo.value,
            },
            headers: {
                Accept: 'application/json',
            },
            signal: controlador?.signal,
        });

        const dados = data?.data ?? data ?? {};

        listaWidgets
            .filter((widget) => ehVersaoAtual(widget, versoes))
            .forEach((widget) => {
                payload[widget] = dados[widget] ?? null;
            });
    } catch (e) {
        if (axios.isCancel(e)) {
            return;
        }

        erro.value = 'Não foi possível carregar o dashboard. Tente novamente.';
        listaWidgets
            .filter((widget) => ehVersaoAtual(widget, versoes) && payload[widget] === undefined)
            .forEach((widget) => {
                payload[widget] = null;
            });
    } finally {
        listaWidgets
            .filter((widget) => ehVersaoAtual(widget, versoes))
            .forEach((widget) => widgetsCarregando.delete(widget));
    }
}

function estaCarregando(widget) {
    return widgetsCarregando.has(widget) || payload[widget] === undefined;
}

function alternarWidget(id) {
    widgets[id] = !widgets[id];
    persistirPreferencias();

    if (widgets[id] && payload[id] === undefined) {
        buscarWidgets([id]);
    }
}

function alternarVisibilidadeModulos() {
    modulosOcultos.value = !modulosOcultos.value;
    persistirPreferencias();
}

function atualizarPeriodo(novoPeriodo) {
    if (novoPeriodo === periodo.value) {
        return;
    }

    periodo.value = novoPeriodo;
    persistirPreferencias();

    controladorPeriodo?.abort();
    controladorPeriodo = null;

    if (!widgets[WIDGET_RECEITAS_DESPESAS]) {
        delete payload[WIDGET_RECEITAS_DESPESAS];
        return;
    }

    controladorPeriodo = new AbortController();
    buscarWidgets([WIDGET_RECEITAS_DESPESAS], controladorPeriodo);
}

function abrirTelaDeLancamentos() {
    router.visit(URL_LANCAMENTOS);
}

onMounted(() => {
    carregarPreferencias();
    buscarWidgets(widgetsAtivos.value);
});

onBeforeUnmount(() => {
    controladorPeriodo?.abort();
});
</script>

<template>
    <Head title="Dashboard" />

    <AutenticadoLayout titulo="Dashboard Executivo">
        <template #acoes>
            <BotaoAdicionarInline rotulo="Novo lançamento" @click="abrirTelaDeLancamentos" />
        </template>

        <div class="space-y-6 p-6 lg:p-8">
            <CabecalhoBoasVindas
                :nome-usuario="usuario.nome"
                :modulos="widgets"
                :opcoes-modulos="opcoesWidgets"
                :modulos-ocultos="modulosOcultos"
                @alternar-modulo="alternarWidget"
                @alternar-visibilidade-modulos="alternarVisibilidadeModulos"
            />

            <div v-if="erro" class="ng-alert ng-alert--danger" role="alert">
                <span class="ng-alert__icon" aria-hidden="true">
                    <span class="material-symbols-outlined">priority_high</span>
                </span>
                <p class="min-w-0">{{ erro }}</p>
            </div>

            <div v-if="!widgetsAtivos.length" class="ng-card p-8 text-center text-sm text-ng-ink-muted">
                Ative ao menos um módulo em “Exibir módulos” para carregar os dados.
            </div>

            <template v-else>
                <IndicadoresResumoFinanceiro
                    v-if="widgets.resumo"
                    :dados="payload.resumo"
                    :carregando="estaCarregando('resumo')"
                />

                <div v-if="widgets.contas || widgets.cartoes" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <ContasCard
                        v-if="widgets.contas"
                        :dados="payload.contas"
                        :carregando="estaCarregando('contas')"
                    />
                    <CartoesCard
                        v-if="widgets.cartoes"
                        :dados="payload.cartoes"
                        :carregando="estaCarregando('cartoes')"
                    />
                </div>

                <div
                    v-if="widgets.categorias || widgets.receitas_despesas"
                    class="grid grid-cols-1 gap-6 lg:grid-cols-2"
                >
                    <CategoriasCard
                        v-if="widgets.categorias"
                        :dados="payload.categorias"
                        :carregando="estaCarregando('categorias')"
                    />
                    <ReceitasDespesasCard
                        v-if="widgets.receitas_despesas"
                        :dados="payload.receitas_despesas"
                        :carregando="estaCarregando('receitas_despesas')"
                        :periodo="periodo"
                        @atualizar-periodo="atualizarPeriodo"
                    />
                </div>

                <MetasCard
                    v-if="widgets.metas"
                    :dados="payload.metas"
                    :carregando="estaCarregando('metas')"
                />
            </template>
        </div>
    </AutenticadoLayout>
</template>
