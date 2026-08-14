<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import FormularioModal from '@/Pages/Orcamento/FormularioModal.vue';
import FormularioServicoModal from '@/Pages/Orcamento/FormularioServicoModal.vue';

const props = defineProps({
    ano: {
        type: Number,
        required: true,
    },
    mes: {
        type: Number,
        required: true,
    },
    orcamentos: {
        type: Array,
        default: () => [],
    },
    orcamentosServico: {
        type: Array,
        default: () => [],
    },
    categorias: {
        type: Array,
        default: () => [],
    },
    tipoAtivo: {
        type: String,
        default: 'por_categoria',
    },
    urlBase: {
        type: String,
        required: true,
    },
    simulacaoOrcamentoServico: {
        type: Object,
        default: null,
    },
});

const pagina = usePage();
const modalFormularioAberto = ref(false);
const orcamentoEmEdicao = ref(null);
const modalServicoAberto = ref(false);
const orcamentoServicoEmEdicao = ref(null);
const simulacaoAtiva = ref(props.simulacaoOrcamentoServico);
const modalExclusaoAberto = ref(false);
const orcamentoParaExcluir = ref(null);
const excluindo = ref(false);
const exclusaoEhServico = ref(false);

const ehPorServico = computed(() => props.tipoAtivo === 'por_servico');

const meses = [
    'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
    'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez',
];

const nomesMeses = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
];

const siglaMes = computed(() => meses[props.mes - 1] ?? '');
const nomeMes = computed(() => nomesMeses[props.mes - 1] ?? '');

watch(
    () => props.simulacaoOrcamentoServico,
    (valor) => {
        if (valor) {
            simulacaoAtiva.value = valor;
            modalServicoAberto.value = true;
            orcamentoServicoEmEdicao.value = null;
        }
    },
    { immediate: true },
);

watch(
    () => pagina.props.errors,
    (erros) => {
        if (!erros || Object.keys(erros).length === 0) {
            return;
        }

        if (ehPorServico.value) {
            modalServicoAberto.value = true;
            return;
        }

        modalFormularioAberto.value = true;
    },
    { deep: true, immediate: true },
);

function urlDoMes(ano, mes) {
    const base = `${props.urlBase}/${ano}/${mes}`;
    return ehPorServico.value ? `${base}?tipo=por_servico` : base;
}

function abrirMes(ano, mes) {
    let a = ano;
    let m = mes;

    if (m < 1) {
        m = 12;
        a -= 1;
    } else if (m > 12) {
        m = 1;
        a += 1;
    }

    router.get(urlDoMes(a, m), {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

function mesAnterior() {
    abrirMes(props.ano, props.mes - 1);
}

function proximoMes() {
    abrirMes(props.ano, props.mes + 1);
}

function abrirCriar() {
    if (ehPorServico.value) {
        orcamentoServicoEmEdicao.value = null;
        simulacaoAtiva.value = null;
        modalServicoAberto.value = true;
        return;
    }

    orcamentoEmEdicao.value = null;
    modalFormularioAberto.value = true;
}

function abrirEditar(orcamento) {
    orcamentoEmEdicao.value = orcamento;
    modalFormularioAberto.value = true;
}

function abrirEditarServico(orcamento) {
    orcamentoServicoEmEdicao.value = orcamento;
    simulacaoAtiva.value = null;
    modalServicoAberto.value = true;
}

function fecharFormulario() {
    modalFormularioAberto.value = false;
    orcamentoEmEdicao.value = null;
}

function fecharFormularioServico() {
    modalServicoAberto.value = false;
    orcamentoServicoEmEdicao.value = null;
    simulacaoAtiva.value = null;
}

function pedirExclusao(orcamento, ehServico = false) {
    orcamentoParaExcluir.value = orcamento;
    exclusaoEhServico.value = ehServico;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    orcamentoParaExcluir.value = null;
    exclusaoEhServico.value = false;
}

function confirmarExclusao() {
    if (!orcamentoParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    const dados = exclusaoEhServico.value
        ? {}
        : { ano: props.ano, mes: props.mes };

    router.delete(orcamentoParaExcluir.value.url_excluir, {
        data: dados,
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            orcamentoParaExcluir.value = null;
            exclusaoEhServico.value = false;
        },
    });
}

const mensagemExclusao = computed(() => {
    if (exclusaoEhServico.value) {
        const nome = orcamentoParaExcluir.value?.descricao;
        if (!nome) {
            return 'Deseja excluir este orçamento por serviço? Esta ação não pode ser desfeita.';
        }

        return `Deseja excluir o orçamento "${nome}"? Nenhum lançamento será criado ou removido.`;
    }

    const nome = orcamentoParaExcluir.value?.categoria_nome;
    if (!nome) {
        return 'Deseja excluir este orçamento? Esta ação não pode ser desfeita.';
    }

    return `Deseja excluir o orçamento de "${nome}"? O histórico de lançamentos permanece intacto.`;
});

const tituloPagina = computed(() => {
    if (ehPorServico.value) {
        return 'Orçamentos por serviço';
    }

    return `Orçamentos · ${nomeMes.value} ${props.ano}`;
});
</script>

<template>
    <Head :title="tituloPagina" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ ehPorServico ? 'Orçamentos por serviço' : 'Orçamentos por categoria' }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        <template v-if="ehPorServico">
                            Avalie propostas comerciais com base na sua saúde financeira
                        </template>
                        <template v-else>
                            Acompanhe quanto já usou em {{ nomeMes.toLowerCase() }}/{{ ano }}
                        </template>
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#198a68] transition"
                    @click="abrirCriar"
                >
                    <span class="material-symbols-outlined text-base leading-none">add</span>
                    Novo orçamento
                </button>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div
                v-if="!ehPorServico"
                class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100"
            >
                <div class="flex items-center justify-center gap-6 px-4 py-1">
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100"
                        @click="mesAnterior"
                    >
                        <span class="material-symbols-outlined">chevron_left</span>
                    </button>
                    <p class="min-w-[3rem] text-center text-base font-semibold text-gray-800">
                        {{ siglaMes }}
                    </p>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100"
                        @click="proximoMes"
                    >
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                </div>
            </div>

            <template v-if="ehPorServico">
                <div
                    v-if="orcamentosServico.length === 0"
                    class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-10 text-center"
                >
                    <span class="material-symbols-outlined text-4xl text-gray-300">request_quote</span>
                    <p class="mt-3 text-gray-600">Você ainda não registrou um orçamento por serviço.</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Ex.: a loja passou um orçamento de R$ 8.000 para uma reforma. Veja se cabe no seu fluxo.
                    </p>
                    <button
                        type="button"
                        class="mt-4 inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#198a68] transition"
                        @click="abrirCriar"
                    >
                        Criar primeiro orçamento
                    </button>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6"
                >
                    <div
                        v-for="item in orcamentosServico"
                        :key="item.id"
                        class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-[#1fa67e]">
                                        <span class="material-symbols-outlined text-[20px]">request_quote</span>
                                    </span>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ item.descricao }}
                                    </h3>
                                    <span
                                        v-if="item.expirado"
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
                                    >
                                        Expirado
                                    </span>
                                    <span
                                        v-else-if="item.compromete_fluxo"
                                        class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800"
                                    >
                                        Compromete o fluxo
                                    </span>
                                    <span
                                        v-else-if="item.pago_integralmente_agora"
                                        class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"
                                    >
                                        Viável agora
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    R$ {{ item.valor }} · válido até {{ item.data_validade_formatada }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 transition"
                                    @click="abrirEditarServico(item)"
                                >
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 transition"
                                    @click="pedirExclusao(item, true)"
                                >
                                    Excluir
                                </button>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <p class="text-sm text-gray-700">
                                {{ item.mensagem_principal }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ item.mensagem_disponivel }}
                            </p>

                            <div
                                v-if="item.comparativo?.mes_rotulo"
                                class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                            >
                                <div class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-3">
                                    <p class="text-xs text-gray-500">
                                        Sem o orçamento · {{ item.comparativo.mes_rotulo }}
                                    </p>
                                    <p
                                        class="mt-1 text-base font-semibold"
                                        :class="item.comparativo.saldo_sem_orcamento_numero >= 0 ? 'text-[#1fa67e]' : 'text-red-600'"
                                    >
                                        R$ {{ item.comparativo.saldo_sem_orcamento }}
                                    </p>
                                </div>
                                <div class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-3">
                                    <p class="text-xs text-gray-500">
                                        Com o orçamento · {{ item.comparativo.mes_rotulo }}
                                    </p>
                                    <p
                                        class="mt-1 text-base font-semibold"
                                        :class="item.comparativo.saldo_com_orcamento_numero >= 0 ? 'text-[#1fa67e]' : 'text-red-600'"
                                    >
                                        R$ {{ item.comparativo.saldo_com_orcamento }}
                                    </p>
                                </div>
                            </div>

                            <p v-if="item.mensagem_alerta" class="text-sm font-medium text-amber-800">
                                {{ item.mensagem_alerta }}
                            </p>

                            <p v-if="item.observacao" class="text-sm text-gray-500 border-t border-gray-100 pt-3">
                                {{ item.observacao }}
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    v-if="orcamentos.length === 0"
                    class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-10 text-center"
                >
                    <span class="material-symbols-outlined text-4xl text-gray-300">account_balance_wallet</span>
                    <p class="mt-3 text-gray-600">Você ainda não definiu um orçamento por categoria.</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Escolha uma categoria, como Alimentação, e um limite mensal para acompanhar seus gastos.
                    </p>
                    <button
                        type="button"
                        class="mt-4 inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#198a68] transition"
                        @click="abrirCriar"
                    >
                        Criar primeiro orçamento
                    </button>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6"
                >
                    <div
                        v-for="orcamento in orcamentos"
                        :key="orcamento.id"
                        class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                        :style="{ backgroundColor: `${orcamento.categoria_cor || '#1fa67e'}20`, color: orcamento.categoria_cor || '#1fa67e' }"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">
                                            {{ orcamento.categoria_icone || 'category' }}
                                        </span>
                                    </span>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ orcamento.categoria_nome }}
                                    </h3>
                                    <span
                                        v-if="orcamento.ultrapassado"
                                        class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700"
                                    >
                                        Ultrapassado
                                    </span>
                                    <span
                                        v-if="orcamento.exibir_dashboard === 'S'"
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
                                    >
                                        No dashboard
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    Limite mensal de R$ {{ orcamento.valor_mensal }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 transition"
                                    @click="abrirEditar(orcamento)"
                                >
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 transition"
                                    @click="pedirExclusao(orcamento, false)"
                                >
                                    Excluir
                                </button>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="flex items-center justify-between text-sm">
                                <span
                                    class="font-medium"
                                    :class="orcamento.ultrapassado ? 'text-red-700' : 'text-gray-700'"
                                >
                                    {{ orcamento.percentual }}% usado
                                </span>
                                <span :class="orcamento.ultrapassado ? 'text-red-600 font-medium' : 'text-gray-500'">
                                    R$ {{ orcamento.texto_progresso }}
                                </span>
                            </div>
                            <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="orcamento.ultrapassado ? 'bg-red-500' : 'bg-[#1fa67e]'"
                                    :style="{ width: `${orcamento.percentual_barra}%` }"
                                />
                            </div>
                            <p v-if="orcamento.ultrapassado" class="mt-2 text-sm text-red-600">
                                Você ultrapassou R$ {{ orcamento.valor_excedente }} neste mês.
                            </p>
                            <p v-else class="mt-2 text-sm text-gray-500">
                                Ainda restam R$ {{ orcamento.valor_restante }} neste mês.
                            </p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <FormularioModal
            :aberto="modalFormularioAberto"
            :orcamento="orcamentoEmEdicao"
            :categorias="categorias"
            :ano="ano"
            :mes="mes"
            @fechar="fecharFormulario"
        />

        <FormularioServicoModal
            :aberto="modalServicoAberto"
            :orcamento="orcamentoServicoEmEdicao"
            :simulacao="simulacaoAtiva"
            @fechar="fecharFormularioServico"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir orçamento"
            :mensagem="mensagemExclusao"
            texto-confirmar="Excluir"
            texto-cancelar="Cancelar"
            perigo
            :processando="excluindo"
            @confirmar="confirmarExclusao"
            @cancelar="fecharExclusao"
        />
    </AutenticadoLayout>
</template>
