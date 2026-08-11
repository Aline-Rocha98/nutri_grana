<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import FormularioModal from '@/Pages/Lancamento/FormularioModal.vue';

const props = defineProps({
    ano: { type: Number, required: true },
    mes: { type: Number, required: true },
    lancamentos: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
    totais: { type: Object, default: () => ({}) },
    filtros: { type: Object, default: () => ({}) },
    contasBancarias: { type: Array, default: () => [] },
    cartoesCredito: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
    tipos: { type: Array, default: () => [] },
    formasPagamento: { type: Array, default: () => [] },
    situacoes: { type: Array, default: () => [] },
    frequencias: { type: Array, default: () => [] },
    urlCriar: { type: String, required: true },
    urlBase: { type: String, required: true },
});

const pagina = usePage();
const modalAberto = ref(false);
const lancamentoEmEdicao = ref(null);
const modalExclusaoAberto = ref(false);
const lancamentoParaExcluir = ref(null);
const excluindo = ref(false);

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
const lista = computed(() => props.lancamentos?.data ?? []);
const links = computed(() => props.lancamentos?.links ?? []);

watch(
    () => pagina.props.errors,
    (erros) => {
        if (erros && Object.keys(erros).length > 0) {
            modalAberto.value = true;
        }
    },
    { deep: true, immediate: true },
);

function formatarMoeda(valor) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
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

    router.get(`${props.urlBase}/${a}/${m}`, {}, {
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
    lancamentoEmEdicao.value = null;
    modalAberto.value = true;
}

function abrirEditar(item) {
    lancamentoEmEdicao.value = item;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    lancamentoEmEdicao.value = null;
}

function marcarPago(item) {
    const situacao = item.situacao === 'pago' ? 'pendente' : 'pago';
    router.patch(item.url_situacao, { situacao }, { preserveScroll: true });
}

function pedirExclusao(item) {
    lancamentoParaExcluir.value = item;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    lancamentoParaExcluir.value = null;
}

function confirmarExclusao() {
    if (!lancamentoParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(lancamentoParaExcluir.value.url_excluir, {
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            lancamentoParaExcluir.value = null;
        },
    });
}

function irPagina(url) {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    }
}

const mensagemExclusao = computed(() => {
    const nome = lancamentoParaExcluir.value?.descricao;
    if (!nome) {
        return 'Deseja excluir este lançamento?';
    }

    return `Deseja excluir "${nome}"?`;
});
</script>

<template>
    <Head :title="`Lançamentos · ${nomeMes} ${ano}`" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lançamentos</h2>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#198a68] transition"
                    @click="abrirCriar"
                >
                    <span class="material-symbols-outlined text-base leading-none">add</span>
                    Novo lançamento
                </button>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="flex items-center gap-1 text-sm text-gray-500">
                        Receitas
                        <span
                            class="group relative inline-flex cursor-help"
                            tabindex="0"
                        >
                            <span class="material-symbols-outlined text-[16px] text-gray-400">help</span>
                            <span
                                class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden w-48 -translate-x-1/2 rounded-lg bg-gray-800 px-2.5 py-1.5 text-center text-xs font-normal text-white shadow-lg group-hover:block group-focus:block"
                            >
                                Receitas lançadas neste mês
                            </span>
                        </span>
                    </p>
                    <p class="mt-1 text-2xl font-bold text-[#1fa67e]">
                        R$ {{ formatarMoeda(totais.receitas) }}
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="flex items-center gap-1 text-sm text-gray-500">
                        Despesas
                        <span
                            class="group relative inline-flex cursor-help"
                            tabindex="0"
                        >
                            <span class="material-symbols-outlined text-[16px] text-gray-400">help</span>
                            <span
                                class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden w-48 -translate-x-1/2 rounded-lg bg-gray-800 px-2.5 py-1.5 text-center text-xs font-normal text-white shadow-lg group-hover:block group-focus:block"
                            >
                                Despesas lançadas neste mês
                            </span>
                        </span>
                    </p>
                    <p class="mt-1 text-2xl font-bold text-red-600">
                        R$ {{ formatarMoeda(totais.despesas) }}
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="flex items-center gap-1 text-sm text-gray-500">
                        Saldo do mês
                        <span
                            class="group relative inline-flex cursor-help"
                            tabindex="0"
                        >
                            <span class="material-symbols-outlined text-[16px] text-gray-400">help</span>
                            <span
                                class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden w-56 -translate-x-1/2 rounded-lg bg-gray-800 px-2.5 py-1.5 text-center text-xs font-normal text-white shadow-lg group-hover:block group-focus:block"
                            >
                                Total das contas bancárias menos as despesas
                            </span>
                        </span>
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold"
                        :class="(totais.saldo || 0) >= 0 ? 'text-[#1fa67e]' : 'text-red-600'"
                    >
                        R$ {{ formatarMoeda(totais.saldo) }}
                    </p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="flex items-center justify-center gap-6 px-4 py-4">
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

                <div class="space-y-3 px-4 pb-4">
                    <div
                        v-for="item in lista"
                        :key="item.id"
                        class="flex items-center gap-4 rounded-2xl border border-gray-100 px-4 py-3 hover:bg-gray-50/80 transition"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                            :class="item.tipo === 'receita'
                                ? 'bg-[#e8f7f1] text-[#1fa67e]'
                                : 'bg-red-50 text-red-600'"
                        >
                            <span class="material-symbols-outlined text-[20px]">
                                {{ item.tipo === 'receita' ? 'trending_up' : 'trending_down' }}
                            </span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-900">{{ item.descricao }}</p>
                            <p class="text-sm text-gray-500">
                                {{ item.data_vencimento_formatada }}
                                <span v-if="item.categoria_nome"> · {{ item.categoria_nome }}</span>
                                <span v-if="item.conta_bancaria_nome"> · {{ item.conta_bancaria_nome }}</span>
                                <span v-if="item.cartao_credito_nome"> · {{ item.cartao_credito_nome }}</span>
                                ·
                                <span
                                    :class="{
                                        'text-orange-500 font-medium': item.situacao === 'pendente',
                                        'text-[#1fa67e] font-medium': item.situacao === 'pago',
                                        'text-gray-500': item.situacao !== 'pendente' && item.situacao !== 'pago',
                                    }"
                                >
                                    {{ item.situacao === 'pago' ? 'Pago' : item.situacao_rotulo }}
                                </span>
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <p
                                class="font-semibold"
                                :class="item.tipo === 'receita' ? 'text-[#1fa67e]' : 'text-red-600'"
                            >
                                {{ item.tipo === 'receita' ? '+' : '-' }} R$ {{ item.valor }}
                            </p>
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            <button
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                                :title="item.situacao === 'pago' ? 'Marcar pendente' : 'Marcar pago'"
                                @click="marcarPago(item)"
                            >
                                <span class="material-symbols-outlined text-[20px]">
                                    {{ item.situacao === 'pago' ? 'undo' : 'check_circle' }}
                                </span>
                            </button>
                            <button
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                                title="Editar"
                                @click="abrirEditar(item)"
                            >
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>
                            <button
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600"
                                title="Excluir"
                                @click="pedirExclusao(item)"
                            >
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="lista.length === 0"
                        class="rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center text-sm text-gray-500"
                    >
                        Nenhum lançamento em {{ nomeMes }}/{{ ano }}.
                    </div>
                </div>

                <div
                    v-if="links.length > 3"
                    class="flex flex-wrap items-center justify-center gap-1 border-t border-gray-100 px-4 py-3"
                >
                    <button
                        v-for="(link, idx) in links"
                        :key="idx"
                        type="button"
                        class="min-w-[2rem] rounded-lg px-2 py-1 text-sm"
                        :class="link.active
                            ? 'bg-[#1fa67e] text-white'
                            : link.url
                                ? 'text-gray-600 hover:bg-gray-100'
                                : 'text-gray-300 cursor-not-allowed'"
                        :disabled="!link.url"
                        v-html="link.label"
                        @click="irPagina(link.url)"
                    />
                </div>
            </div>
        </div>

        <FormularioModal
            :aberto="modalAberto"
            :lancamento="lancamentoEmEdicao"
            :ano="ano"
            :mes="mes"
            :contas-bancarias="contasBancarias"
            :cartoes-credito="cartoesCredito"
            :categorias="categorias"
            :tipos="tipos"
            :formas-pagamento="formasPagamento"
            :situacoes="situacoes"
            :frequencias="frequencias"
            :url-criar="urlCriar"
            @fechar="fecharModal"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir lançamento"
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
