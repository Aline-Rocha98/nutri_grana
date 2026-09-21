<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import FormularioModal from '@/Pages/Lancamento/FormularioModal.vue';
import ConfirmarReceitaModal from '@/Pages/Lancamento/ConfirmarReceitaModal.vue';
import { badgeSituacaoLancamento } from '@/Helpers/badge';

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
const modalConfirmacaoAberto = ref(false);
const lancamentoParaConfirmar = ref(null);
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
        if (!erros || Object.keys(erros).length === 0) {
            return;
        }

        if (erros.valor_recebido || erros.data_recebimento) {
            modalConfirmacaoAberto.value = true;
            return;
        }

        modalAberto.value = true;
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
    if (item.eh_renda && item.situacao === 'previsto') {
        lancamentoParaConfirmar.value = item;
        modalConfirmacaoAberto.value = true;
        return;
    }

    lancamentoEmEdicao.value = item;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    lancamentoEmEdicao.value = null;
}

function fecharConfirmacao() {
    modalConfirmacaoAberto.value = false;
    lancamentoParaConfirmar.value = null;
}

function marcarPago(item) {
    if (item.eh_renda) {
        abrirEditar(item);
        return;
    }

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
                <h2 class="font-semibold text-xl text-ng-ink leading-tight">Lançamentos</h2>
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
                <div class="bg-ng-card rounded-2xl border border-ng-line p-5 shadow-sm">
                    <p class="flex items-center gap-1 text-sm text-ng-ink-muted">
                        Receitas
                        <span
                            class="group relative inline-flex cursor-help"
                            tabindex="0"
                        >
                            <span class="material-symbols-outlined text-[16px] text-ng-ink-subtle">help</span>
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
                <div class="bg-ng-card rounded-2xl border border-ng-line p-5 shadow-sm">
                    <p class="flex items-center gap-1 text-sm text-ng-ink-muted">
                        Despesas
                        <span
                            class="group relative inline-flex cursor-help"
                            tabindex="0"
                        >
                            <span class="material-symbols-outlined text-[16px] text-ng-ink-subtle">help</span>
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
                <div class="bg-ng-card rounded-2xl border border-ng-line p-5 shadow-sm">
                    <p class="flex items-center gap-1 text-sm text-ng-ink-muted">
                        Saldo do mês
                        <span
                            class="group relative inline-flex cursor-help"
                            tabindex="0"
                        >
                            <span class="material-symbols-outlined text-[16px] text-ng-ink-subtle">help</span>
                            <span
                                class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden w-56 -translate-x-1/2 rounded-lg bg-gray-800 px-2.5 py-1.5 text-center text-xs font-normal text-white shadow-lg group-hover:block group-focus:block"
                            >
                                Receitas do mês menos despesas do mês
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

            <div class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line">
                <div class="flex items-center justify-center gap-6 px-4 py-4">
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

                <div class="space-y-3 px-4 pb-4">
                    <div
                        v-for="item in lista"
                        :key="item.id"
                        class="flex items-center gap-4 rounded-2xl border border-ng-line px-4 py-3 hover:bg-ng-brand-soft/80 transition"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                            :class="item.tipo === 'receita' ? 'ng-tint ng-tint--brand' : 'ng-tint ng-tint--red'"
                        >
                            <span class="material-symbols-outlined text-[20px]">
                                {{ item.tipo === 'receita' ? 'trending_up' : 'trending_down' }}
                            </span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ng-ink">{{ item.descricao }}</p>
                            <p class="text-sm text-ng-ink-muted">
                                {{ item.data_vencimento_formatada }}
                                <span v-if="item.categoria_nome"> · {{ item.categoria_nome }}</span>
                                <span v-if="item.conta_bancaria_nome"> · {{ item.conta_bancaria_nome }}</span>
                                <span v-if="item.cartao_credito_nome"> · {{ item.cartao_credito_nome }}</span>
                                ·
                                <span :class="badgeSituacaoLancamento(item.situacao)">
                                    {{ item.situacao_rotulo }}
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
                                v-if="!item.eh_renda"
                                type="button"
                                class="rounded-lg p-2 text-ng-ink-muted hover:bg-ng-brand-soft"
                                :title="item.situacao === 'pago' ? 'Marcar pendente' : 'Marcar pago'"
                                @click="marcarPago(item)"
                            >
                                <span class="material-symbols-outlined text-[20px]">
                                    {{ item.situacao === 'pago' ? 'undo' : 'check_circle' }}
                                </span>
                            </button>
                            <button
                                v-else-if="item.situacao === 'previsto'"
                                type="button"
                                class="rounded-lg p-2 text-ng-ink-muted hover:bg-ng-brand-soft"
                                title="Confirmar receita"
                                @click="abrirEditar(item)"
                            >
                                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                            </button>
                            <button
                                type="button"
                                class="rounded-lg p-2 text-ng-ink-muted hover:bg-ng-brand-soft"
                                :title="item.eh_renda && item.situacao === 'previsto' ? 'Confirmar receita' : 'Editar'"
                                @click="abrirEditar(item)"
                            >
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>
                            <button
                                type="button"
                                class="rounded-lg p-2 text-ng-ink-muted hover:bg-red-500/10 hover:text-red-400"
                                title="Excluir"
                                @click="pedirExclusao(item)"
                            >
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="lista.length === 0"
                        class="rounded-2xl border border-dashed border-ng-line-strong px-6 py-10 text-center text-sm text-ng-ink-muted"
                    >
                        Nenhum lançamento em {{ nomeMes }}/{{ ano }}.
                    </div>
                </div>

                <div
                    v-if="links.length > 3"
                    class="flex flex-wrap items-center justify-center gap-1 border-t border-ng-line px-4 py-3"
                >
                    <button
                        v-for="(link, idx) in links"
                        :key="idx"
                        type="button"
                        class="min-w-[2rem] rounded-lg px-2 py-1 text-sm"
                        :class="link.active
                            ? 'bg-[#1fa67e] text-white'
                            : link.url
                                ? 'text-ng-ink-muted hover:bg-ng-brand-soft'
                                : 'text-ng-ink-subtle cursor-not-allowed'"
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

        <ConfirmarReceitaModal
            :aberto="modalConfirmacaoAberto"
            :lancamento="lancamentoParaConfirmar"
            @fechar="fecharConfirmacao"
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
