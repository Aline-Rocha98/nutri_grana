<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import Modal from '@/Components/Modal.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import FormularioModal from '@/Pages/Orcamento/FormularioModal.vue';
import FormularioServicoModal from '@/Pages/Orcamento/FormularioServicoModal.vue';
import { BADGE, badgeStatusCotacao } from '@/Helpers/badge';

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
    contasBancarias: {
        type: Array,
        default: () => [],
    },
    cartoesCredito: {
        type: Array,
        default: () => [],
    },
    formasPagamento: {
        type: Array,
        default: () => [],
    },
    modalidadesPagamento: {
        type: Array,
        default: () => [],
    },
});

const pagina = usePage();
const modalFormularioAberto = ref(false);
const orcamentoEmEdicao = ref(null);
const modalServicoAberto = ref(false);
const orcamentoServicoEmEdicao = ref(null);
const modalExclusaoAberto = ref(false);
const orcamentoParaExcluir = ref(null);
const excluindo = ref(false);
const exclusaoEhServico = ref(false);
const modalAprovarAberto = ref(false);
const cotacaoParaAprovar = ref(null);
const cenarioSelecionado = ref(null);
const modalDetalheAberto = ref(false);
const cotacaoDetalhe = ref(null);
const mostrarParcelasExtras = ref(false);

const formularioAprovacao = useForm({
    modalidade_pagamento: 'a_vista',
    total_parcelas: 2,
    forma_pagamento: 'conta_bancaria',
    id_conta_bancaria: null,
    id_cartao_credito: null,
});

const ehPorServico = computed(() => props.tipoAtivo === 'por_servico');
const aprovacaoParcelada = computed(() => formularioAprovacao.modalidade_pagamento === 'parcelado');
const aprovacaoConta = computed(() => formularioAprovacao.forma_pagamento === 'conta_bancaria');
const aprovacaoCartao = computed(() => formularioAprovacao.forma_pagamento === 'cartao_credito');

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
    () => pagina.props.errors,
    (erros) => {
        if (!erros || Object.keys(erros).length === 0) {
            return;
        }

        if (ehPorServico.value) {
            modalServicoAberto.value = true;
            modalAprovarAberto.value = true;
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
    modalServicoAberto.value = true;
}

function fecharFormulario() {
    modalFormularioAberto.value = false;
    orcamentoEmEdicao.value = null;
}

function fecharFormularioServico() {
    modalServicoAberto.value = false;
    orcamentoServicoEmEdicao.value = null;
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
        return 'Cotações por serviço';
    }

    return `Orçamentos · ${nomeMes.value} ${props.ano}`;
});

function classeStatus(status) {
    return badgeStatusCotacao(status);
}

function abrirAprovar(item, cenario = null) {
    cotacaoParaAprovar.value = item;
    const cenarioEscolhido = cenario
        ?? item.cenarios?.find((opcao) => opcao.recomendado)
        ?? item.cenarios?.[0]
        ?? null;
    cenarioSelecionado.value = cenarioEscolhido;

    const parcelado = cenarioEscolhido?.modalidade_pagamento === 'parcelado'
        || (cenarioEscolhido === null && item.modalidade_pagamento === 'parcelado');
    formularioAprovacao.clearErrors();
    formularioAprovacao.modalidade_pagamento = parcelado ? 'parcelado' : 'a_vista';
    formularioAprovacao.total_parcelas = parcelado
        ? (cenarioEscolhido?.total_parcelas ?? 2)
        : 1;
    formularioAprovacao.forma_pagamento = item.forma_pagamento ?? 'conta_bancaria';
    formularioAprovacao.id_conta_bancaria = item.id_conta_bancaria
        ?? props.contasBancarias.find((c) => c.padrao_desconto === 'S')?.id
        ?? props.contasBancarias[0]?.id
        ?? null;
    formularioAprovacao.id_cartao_credito = item.id_cartao_credito
        ?? props.cartoesCredito.find((c) => c.padrao === 'S')?.id
        ?? props.cartoesCredito[0]?.id
        ?? null;

    modalAprovarAberto.value = true;
}

function fecharAprovar() {
    modalAprovarAberto.value = false;
    cotacaoParaAprovar.value = null;
    cenarioSelecionado.value = null;
    formularioAprovacao.reset();
}

function confirmarAprovacao() {
    if (!cotacaoParaAprovar.value) {
        return;
    }

    formularioAprovacao.post(cotacaoParaAprovar.value.url_aprovar, {
        preserveScroll: true,
        onSuccess: () => {
            fecharAprovar();
            fecharDetalhe();
        },
    });
}

function recusarCotacao(item) {
    router.post(item.url_recusar, {}, { preserveScroll: true });
}

function abrirDetalhe(item) {
    cotacaoDetalhe.value = item;
    mostrarParcelasExtras.value = false;
    modalDetalheAberto.value = true;
}

function fecharDetalhe() {
    modalDetalheAberto.value = false;
    cotacaoDetalhe.value = null;
    mostrarParcelasExtras.value = false;
}

const cenariosVisiveis = computed(() => {
    const item = cotacaoDetalhe.value;
    if (!item?.cenarios?.length) {
        return [];
    }

    if (item.modalidade_pagamento === 'a_vista') {
        return item.cenarios.slice(0, 1);
    }

    const limite = mostrarParcelasExtras.value ? 12 : 6;
    return item.cenarios.slice(0, limite);
});

const temParcelasExtras = computed(() => {
    const item = cotacaoDetalhe.value;
    return item?.modalidade_pagamento === 'parcelado' && (item?.cenarios?.length ?? 0) > 6;
});

const detalheEhCartao = computed(() => cotacaoDetalhe.value?.forma_pagamento === 'cartao_credito');
const detalheEhConta = computed(() => cotacaoDetalhe.value?.forma_pagamento === 'conta_bancaria');

const saldoContaDetalhe = computed(() => {
    const item = cotacaoDetalhe.value;
    if (!item || !detalheEhConta.value) {
        return null;
    }

    if (item.saldo_conta_selecionada) {
        return item.saldo_conta_selecionada;
    }

    const conta = props.contasBancarias.find(
        (c) => Number(c.id) === Number(item.id_conta_bancaria),
    );

    return conta?.saldo_atual ?? null;
});
</script>

<template>
    <Head :title="tituloPagina" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-ng-ink leading-tight">
                        {{ ehPorServico ? 'Orçamentos por serviço' : 'Orçamentos por categoria' }}
                    </h2>
                    <p class="mt-1 text-sm text-ng-ink-muted">
                        <template v-if="ehPorServico">
                            Avalie orçamentos para saber se convém assumir compromissos com eles
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
                    {{ ehPorServico ? 'Orçamento por serviço' : 'Orçamento por categoria' }}
                </button>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div
                v-if="!ehPorServico"
                class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line"
            >
                <div class="flex items-center justify-center gap-6 px-4 py-1">
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-ng-ink-muted hover:bg-ng-brand-soft"
                        @click="mesAnterior"
                    >
                        <span class="material-symbols-outlined">chevron_left</span>
                    </button>
                    <p class="min-w-[3rem] text-center text-base font-semibold text-ng-ink">
                        {{ siglaMes }}
                    </p>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-ng-ink-muted hover:bg-ng-brand-soft"
                        @click="proximoMes"
                    >
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                </div>
            </div>

            <template v-if="ehPorServico">
                <div
                    v-if="orcamentosServico.length === 0"
                    class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-10 text-center"
                >
                    <span class="material-symbols-outlined text-4xl text-ng-ink-subtle">request_quote</span>
                    <p class="mt-3 text-ng-ink-muted">Você ainda não registrou uma cotação.</p>
                    <p class="mt-1 text-sm text-ng-ink-muted">
                        Simule o impacto antes de decidir assumir compromissos com eles.
                    </p>
                    <button
                        type="button"
                        class="mt-4 inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#198a68] transition"
                        @click="abrirCriar"
                    >
                        Criar primeira cotação
                    </button>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 xl:grid-cols-2 gap-4 lg:gap-6"
                >
                    <div
                        v-for="item in orcamentosServico"
                        :key="item.id"
                        class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-6"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="ng-tint ng-tint--brand flex h-9 w-9 shrink-0 items-center justify-center rounded-full">
                                        <span class="material-symbols-outlined text-[20px]">request_quote</span>
                                    </span>
                                    <h3 class="text-lg font-semibold text-ng-ink">{{ item.descricao }}</h3>
                                    <span :class="classeStatus(item.status)">
                                        {{ item.status_rotulo }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-ng-ink-muted">
                                    R$ {{ item.valor }}
                                    <template v-if="item.fornecedor"> · {{ item.fornecedor }}</template>
                                    <template v-if="item.categoria_nome"> · {{ item.categoria_nome }}</template>
                                    · válida até {{ item.data_validade_formatada }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    v-if="item.status === 'em_analise'"
                                    type="button"
                                    class="inline-flex items-center rounded-lg border border-ng-line-strong px-3 py-1.5 text-sm text-ng-ink-secondary hover:bg-ng-brand-soft transition"
                                    @click="abrirEditarServico(item)"
                                >
                                    Editar
                                </button>
                                <button
                                    v-if="item.status === 'em_analise'"
                                    type="button"
                                    class="inline-flex items-center rounded-lg border border-amber-200 px-3 py-1.5 text-sm text-amber-800 hover:bg-amber-50 transition"
                                    @click="recusarCotacao(item)"
                                >
                                    Recusar
                                </button>
                                <button
                                    type="button"
                                    class="ng-btn-danger"
                                    @click="pedirExclusao(item, true)"
                                >
                                    Excluir
                                </button>
                            </div>
                        </div>

                        <div v-if="item.status === 'aprovada'" class="mt-5 rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                            Aprovada em {{ item.data_aprovacao_formatada }} ·
                            {{ item.total_parcelas > 1 ? `${item.total_parcelas}x` : 'À vista' }}
                            via {{ item.forma_pagamento === 'cartao_credito' ? item.cartao_credito_nome : item.conta_bancaria_nome }}
                            · {{ item.compromissos_gerados }} compromisso(s) gerado(s)
                        </div>

                        <div v-else-if="item.status === 'em_analise'" class="mt-5 space-y-4">
                            <div
                                class="rounded-xl border px-4 py-3 text-sm"
                                :class="item.pode_assumir_compromisso
                                    ? 'border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                    : 'border-amber-500/25 bg-amber-500/10 text-amber-800 dark:text-amber-300'"
                            >
                                <p v-if="item.resumo_compromisso" class="mt-1">
                                    {{ item.resumo_compromisso }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg border border-[#1fa67e]/30 px-3 py-1.5 text-sm font-medium text-[#1fa67e] hover:bg-emerald-50 transition"
                                    @click="abrirDetalhe(item)"
                                >
                                    <span class="material-symbols-outlined text-base">analytics</span>
                                    Detalhar
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-lg bg-[#1fa67e] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#198a68] transition"
                                    @click="abrirAprovar(item)"
                                >
                                    Aprovar
                                </button>
                            </div>

                            <p v-if="item.observacao" class="text-sm text-ng-ink-muted border-t border-ng-line pt-3">
                                {{ item.observacao }}
                            </p>
                        </div>

                        <p v-else-if="item.observacao" class="mt-5 text-sm text-ng-ink-muted">
                            {{ item.observacao }}
                        </p>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    v-if="orcamentos.length === 0"
                    class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-10 text-center"
                >
                    <span class="material-symbols-outlined text-4xl text-ng-ink-subtle">account_balance_wallet</span>
                    <p class="mt-3 text-ng-ink-muted">Você ainda não definiu um orçamento por categoria.</p>
                    <p class="mt-1 text-sm text-ng-ink-muted">
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
                        class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-6"
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
                                    <h3 class="text-lg font-semibold text-ng-ink">
                                        {{ orcamento.categoria_nome }}
                                    </h3>
                                    <span
                                        v-if="orcamento.ultrapassado"
                                        :class="BADGE.red"
                                    >
                                        Ultrapassado
                                    </span>
                                    <span
                                        v-if="orcamento.exibir_dashboard === 'S'"
                                        :class="BADGE.zinc"
                                    >
                                        No dashboard
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-ng-ink-muted">
                                    Limite mensal de R$ {{ orcamento.valor_mensal }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-lg border border-ng-line-strong px-3 py-1.5 text-sm text-ng-ink-secondary hover:bg-ng-brand-soft transition"
                                    @click="abrirEditar(orcamento)"
                                >
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    class="ng-btn-danger"
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
                                    :class="orcamento.ultrapassado ? 'text-red-700' : 'text-ng-ink-secondary'"
                                >
                                    {{ orcamento.percentual }}% usado
                                </span>
                                <span :class="orcamento.ultrapassado ? 'text-red-600 font-medium' : 'text-ng-ink-muted'">
                                    R$ {{ orcamento.texto_progresso }}
                                </span>
                            </div>
                            <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-ng-card-muted">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="orcamento.ultrapassado ? 'bg-red-500' : 'bg-[#1fa67e]'"
                                    :style="{ width: `${orcamento.percentual_barra}%` }"
                                />
                            </div>
                            <p v-if="orcamento.ultrapassado" class="mt-2 text-sm text-red-600">
                                Você ultrapassou R$ {{ orcamento.valor_excedente }} neste mês.
                            </p>
                            <p v-else class="mt-2 text-sm text-ng-ink-muted">
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
            :categorias="categorias"
            :contas-bancarias="contasBancarias"
            :cartoes-credito="cartoesCredito"
            :formas-pagamento="formasPagamento"
            :modalidades-pagamento="modalidadesPagamento"
            @fechar="fecharFormularioServico"
        />

        <Modal :aberto="modalDetalheAberto" max-largura="2xl">
            <div v-if="cotacaoDetalhe" class="p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-ng-ink">Detalhe da simulação</h2>
                        <p class="mt-1 text-sm text-ng-ink-muted">
                            {{ cotacaoDetalhe.descricao }} · R$ {{ cotacaoDetalhe.valor }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-ng-ink-subtle hover:bg-ng-brand-soft hover:text-ng-ink-muted"
                        @click="fecharDetalhe"
                    >
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="mt-5 grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="rounded-xl bg-ng-input px-4 py-3">
                        <p class="text-xs text-ng-ink-muted">Modalidade</p>
                        <p class="mt-1 font-semibold text-ng-ink">{{ cotacaoDetalhe.modalidade_pagamento_rotulo }}</p>
                    </div>

                    <div class="rounded-xl bg-ng-input px-4 py-3">
                        <p class="text-xs text-ng-ink-muted">Forma</p>
                        <template v-if="detalheEhCartao">
                            <p class="mt-1 font-semibold text-ng-ink leading-snug">Crédito {{ cotacaoDetalhe.cartao_credito_nome ?? '' }}</p>
                        </template>
                        <template v-else-if="detalheEhConta">
                            <p class="mt-1 font-semibold text-ng-ink leading-snug">PIX {{ cotacaoDetalhe.conta_bancaria_nome ?? '' }}</p>
                        </template>
                    </div>

                    <div v-if="detalheEhCartao" class="rounded-xl bg-ng-input px-4 py-3">
                        <p class="text-xs text-ng-ink-muted">Limite disponível</p>
                        <p class="mt-1 font-semibold text-ng-ink">R$ {{ cotacaoDetalhe.limite_disponivel_cartao ?? '0,00' }}</p>
                    </div>
                    <div v-else-if="detalheEhConta" class="rounded-xl bg-ng-input px-4 py-3">
                        <p class="text-xs text-ng-ink-muted">Saldo disponível</p>
                        <p class="mt-1 font-semibold text-ng-ink">R$ {{ saldoContaDetalhe ?? '0,00' }}</p>
                    </div>

                    <div class="rounded-xl bg-ng-input px-4 py-3">
                        <p class="text-xs text-ng-ink-muted">Receita prevista/mês</p>
                        <p class="mt-1 font-semibold text-ng-ink">R$ {{ cotacaoDetalhe.receita_prevista_mensal ?? '0,00' }}</p>
                    </div>
                </div>

                <div
                    v-if="cotacaoDetalhe.cenarios?.length"
                    class="mt-6 overflow-x-auto rounded-xl border border-ng-line"
                >
                    <table class="min-w-full text-sm">
                        <thead class="bg-ng-input text-left text-xs text-ng-ink-muted">
                            <tr>
                                <th class="px-3 py-2">Opção</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Viável</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="cenario in cenariosVisiveis"
                                :key="cenario.rotulo"
                                class="border-t border-ng-line"
                                :class="cenario.recomendado ? 'bg-emerald-50/50' : ''"
                            >
                                <td class="px-3 py-2 font-medium text-ng-ink">
                                    {{ cenario.rotulo }}
                                    <span v-if="cenario.recomendado" class="ml-1 text-xs text-emerald-700">recomendado</span>
                                </td>
                                <td class="px-3 py-2">
                                    <span :class="cenario.compromete_fluxo ? 'text-red-600' : 'text-emerald-700'">
                                        {{ cenario.rotulo_fluxo || (cenario.compromete_fluxo ? 'Compromete' : 'Ok') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span :class="cenario.viavel ? 'text-emerald-700' : 'text-ng-ink-subtle'">
                                        {{ cenario.viavel ? 'Sim' : 'Não' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <button
                    v-if="temParcelasExtras && !mostrarParcelasExtras"
                    type="button"
                    class="mt-3 text-sm font-medium text-[#1fa67e] hover:underline"
                    @click="mostrarParcelasExtras = true"
                >
                    Ver mais
                </button>
                <button
                    v-else-if="temParcelasExtras && mostrarParcelasExtras"
                    type="button"
                    class="mt-3 text-sm font-medium text-ng-ink-muted hover:underline"
                    @click="mostrarParcelasExtras = false"
                >
                    Mostrar menos
                </button>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm text-ng-ink-secondary border rounded-lg hover:bg-ng-brand-soft"
                        @click="fecharDetalhe"
                    >
                        Fechar
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :aberto="modalAprovarAberto" max-largura="md">
            <form class="p-6" @submit.prevent="confirmarAprovacao">
                <h2 class="text-lg font-semibold text-ng-ink">Aprovar cotação</h2>
                <p class="mt-1 text-sm text-ng-ink-muted">
                    {{ cotacaoParaAprovar?.descricao }} · R$ {{ cotacaoParaAprovar?.valor }}
                    <template v-if="cenarioSelecionado"> · {{ cenarioSelecionado.rotulo }}</template>
                </p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-ng-ink-muted">Forma de pagamento</label>
                        <select
                            v-model="formularioAprovacao.forma_pagamento"
                            class="mt-1 block w-full rounded-lg border-ng-line-strong"
                        >
                            <option v-for="opcao in formasPagamento" :key="opcao.valor" :value="opcao.valor">
                                {{ opcao.valor === 'conta_bancaria' ? 'PIX / conta bancária' : opcao.rotulo }}
                            </option>
                        </select>
                    </div>

                    <div v-if="aprovacaoConta">
                        <label class="block text-sm font-medium text-ng-ink-muted">Conta bancária</label>
                        <select v-model="formularioAprovacao.id_conta_bancaria" class="mt-1 block w-full rounded-lg border-ng-line-strong">
                            <option v-for="conta in contasBancarias" :key="conta.id" :value="conta.id">
                                {{ conta.nome }}
                            </option>
                        </select>
                        <p v-if="formularioAprovacao.errors.id_conta_bancaria" class="mt-1 text-sm text-red-600">
                            {{ formularioAprovacao.errors.id_conta_bancaria }}
                        </p>
                    </div>

                    <div v-if="aprovacaoCartao">
                        <label class="block text-sm font-medium text-ng-ink-muted">Cartão de crédito</label>
                        <select v-model="formularioAprovacao.id_cartao_credito" class="mt-1 block w-full rounded-lg border-ng-line-strong">
                            <option v-for="cartao in cartoesCredito" :key="cartao.id" :value="cartao.id">
                                {{ cartao.nome }} · limite disp. R$ {{ cartao.limite_disponivel }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-ng-ink-muted">
                            O limite do cartão será verificado na aprovação (não é saldo em conta).
                        </p>
                        <p v-if="formularioAprovacao.errors.id_cartao_credito" class="mt-1 text-sm text-red-600">
                            {{ formularioAprovacao.errors.id_cartao_credito }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-ng-ink-muted">Modalidade</label>
                            <select v-model="formularioAprovacao.modalidade_pagamento" class="mt-1 block w-full rounded-lg border-ng-line-strong">
                                <option v-for="opcao in modalidadesPagamento" :key="opcao.valor" :value="opcao.valor">
                                    {{ opcao.rotulo }}
                                </option>
                            </select>
                        </div>
                        <div v-if="aprovacaoParcelada">
                            <label class="block text-sm font-medium text-ng-ink-muted">Parcelas</label>
                            <input
                                v-model.number="formularioAprovacao.total_parcelas"
                                type="number"
                                min="2"
                                max="48"
                                class="mt-1 block w-full rounded-lg border-ng-line-strong"
                            >
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 text-sm text-ng-ink-secondary border rounded-lg" @click="fecharAprovar">
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white bg-[#1fa67e] rounded-lg hover:bg-[#198a68]"
                        :disabled="formularioAprovacao.processing"
                    >
                        Confirmar aprovação
                    </button>
                </div>
            </form>
        </Modal>

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
