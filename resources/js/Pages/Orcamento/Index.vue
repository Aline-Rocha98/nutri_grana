<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import FormularioModal from '@/Pages/Orcamento/FormularioModal.vue';

const props = defineProps({
    orcamentos: {
        type: Array,
        default: () => [],
    },
    categorias: {
        type: Array,
        default: () => [],
    },
    tiposOrcamento: {
        type: Array,
        default: () => [],
    },
    tipoAtivo: {
        type: String,
        default: 'por_categoria',
    },
});

const pagina = usePage();
const modalFormularioAberto = ref(false);
const orcamentoEmEdicao = ref(null);
const modalExclusaoAberto = ref(false);
const orcamentoParaExcluir = ref(null);
const excluindo = ref(false);

const mesReferenciaRotulo = computed(() => {
    return props.orcamentos[0]?.mes_referencia_rotulo
        ?? new Date().toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
});

watch(
    () => pagina.props.errors,
    (erros) => {
        if (!erros || Object.keys(erros).length === 0) {
            return;
        }

        modalFormularioAberto.value = true;
    },
    { deep: true, immediate: true },
);

function abrirCriar() {
    orcamentoEmEdicao.value = null;
    modalFormularioAberto.value = true;
}

function abrirEditar(orcamento) {
    orcamentoEmEdicao.value = orcamento;
    modalFormularioAberto.value = true;
}

function fecharFormulario() {
    modalFormularioAberto.value = false;
    orcamentoEmEdicao.value = null;
}

function pedirExclusao(orcamento) {
    orcamentoParaExcluir.value = orcamento;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    orcamentoParaExcluir.value = null;
}

function confirmarExclusao() {
    if (!orcamentoParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(orcamentoParaExcluir.value.url_excluir, {
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            orcamentoParaExcluir.value = null;
        },
    });
}

const mensagemExclusao = computed(() => {
    const nome = orcamentoParaExcluir.value?.categoria_nome;
    if (!nome) {
        return 'Deseja excluir este orçamento? Esta ação não pode ser desfeita.';
    }

    return `Deseja excluir o orçamento de "${nome}"? O histórico de lançamentos permanece intacto.`;
});
</script>

<template>
    <Head title="Orçamentos" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Orçamentos
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Acompanhe quanto já usou neste mês · {{ mesReferenciaRotulo }}
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

            <div v-if="orcamentos.length === 0" class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-10 text-center">
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
                            @click="pedirExclusao(orcamento)"
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

        <FormularioModal
            :aberto="modalFormularioAberto"
            :orcamento="orcamentoEmEdicao"
            :categorias="categorias"
            @fechar="fecharFormulario"
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
