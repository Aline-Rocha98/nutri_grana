<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import FormularioModal from '@/Pages/ContasBancarias/FormularioModal.vue';

const props = defineProps({
    contasBancarias: {
        type: Array,
        default: () => [],
    },
    tipos: {
        type: Array,
        default: () => [],
    },
    bancosSugeridos: {
        type: Array,
        default: () => [],
    },
});

const pagina = usePage();
const modalAberto = ref(false);
const contaEmEdicao = ref(null);
const modalExclusaoAberto = ref(false);
const contaParaExcluir = ref(null);
const excluindo = ref(false);

watch(
    () => pagina.props.errors,
    (erros) => {
        if (erros && Object.keys(erros).length > 0) {
            modalAberto.value = true;
        }
    },
    { deep: true, immediate: true },
);

const contasAtivas = computed(() => props.contasBancarias.filter((conta) => !conta.arquivada));
const contasArquivadas = computed(() => props.contasBancarias.filter((conta) => conta.arquivada));
const saldoGeral = computed(() =>
    contasAtivas.value.reduce((total, conta) => total + Number(conta.saldo_inicial_numero || 0), 0),
);

function formatarMoeda(valor) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function abrirCriar() {
    contaEmEdicao.value = null;
    modalAberto.value = true;
}

function abrirEditar(conta) {
    contaEmEdicao.value = conta;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    contaEmEdicao.value = null;
}

function alternarArquivada(conta) {
    router.put(conta.url_atualizar, {
        nome: conta.nome,
        tipo: conta.tipo,
        arquivada: conta.arquivada ? 0 : 1,
        padrao_desconto: conta.padrao_desconto,
        exibir_resumo: conta.exibir_resumo,
    }, {
        preserveScroll: true,
    });
}

function pedirExclusao(conta) {
    contaParaExcluir.value = conta;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    contaParaExcluir.value = null;
}

function confirmarExclusao() {
    if (!contaParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(contaParaExcluir.value.url_excluir, {
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            contaParaExcluir.value = null;
        },
    });
}

const mensagemExclusao = computed(() => {
    const nome = contaParaExcluir.value?.nome;
    if (!nome) {
        return 'Deseja excluir esta conta bancária? Esta ação não pode ser desfeita.';
    }

    return `Deseja excluir a conta "${nome}"? Esta ação não pode ser desfeita.`;
});
</script>

<template>
    <Head title="Contas bancárias" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Contas bancárias
                </h2>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#198a68] transition"
                    @click="abrirCriar"
                >
                    <span class="material-symbols-outlined text-base leading-none">add</span>
                    Adicionar conta
                </button>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
                <p class="text-sm font-medium text-gray-500">Saldo geral</p>
                <p class="mt-1 text-2xl font-bold text-[#1fa67e]">
                    R$ {{ formatarMoeda(saldoGeral) }}
                </p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Minhas contas</h3>
                </div>

                <div class="divide-y divide-gray-100">
                    <div
                        v-for="conta in contasAtivas"
                        :key="conta.id"
                        class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/80 transition"
                    >
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#e8f7f1] text-[#1fa67e]">
                            <img
                                v-if="conta.logo"
                                :src="conta.logo"
                                :alt="conta.nome"
                                class="h-full w-full object-cover"
                            >
                            <span v-else class="material-symbols-outlined text-[22px]">
                                {{ conta.tipo === 'poupanca' ? 'savings' : 'account_balance' }}
                            </span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-900">{{ conta.nome }}</p>
                            <p class="text-sm text-gray-500">{{ conta.tipo_rotulo }}</p>
                        </div>

                        <div class="text-right shrink-0">
                            <p
                                class="font-semibold"
                                :class="conta.saldo_inicial_numero >= 0 ? 'text-[#1fa67e]' : 'text-red-600'"
                            >
                                R$ {{ conta.saldo_inicial }}
                            </p>
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            <button
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                                title="Editar"
                                @click="abrirEditar(conta)"
                            >
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>

                            <button
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                                :title="conta.arquivada ? 'Desarquivar' : 'Arquivar'"
                                @click="alternarArquivada(conta)"
                            >
                                <span class="material-symbols-outlined text-[20px]">
                                    {{ conta.arquivada ? 'unarchive' : 'archive' }}
                                </span>
                            </button>

                            <button
                                v-if="conta.total_lancamentos > 0"
                                type="button"
                                class="rounded-lg p-2 text-gray-300 cursor-not-allowed"
                                title="Não é possível excluir: há lançamentos vinculados"
                                disabled
                            >
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                            <button
                                v-else
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600"
                                title="Excluir"
                                @click="pedirExclusao(conta)"
                            >
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="contasAtivas.length === 0"
                        class="px-6 py-10 text-center text-sm text-gray-500"
                    >
                        Nenhuma conta cadastrada. Clique em <strong>Adicionar conta</strong> para começar.
                    </div>
                </div>
            </div>

            <div
                v-if="contasArquivadas.length > 0"
                class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 opacity-80"
            >
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Arquivadas</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <div
                        v-for="conta in contasArquivadas"
                        :key="conta.id"
                        class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/80 transition"
                    >
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#e8f7f1] text-[#1fa67e]">
                            <img
                                v-if="conta.logo"
                                :src="conta.logo"
                                :alt="conta.nome"
                                class="h-full w-full object-cover"
                            >
                            <span v-else class="material-symbols-outlined text-[22px]">
                                account_balance
                            </span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-900">{{ conta.nome }}</p>
                            <p class="text-sm text-gray-500">{{ conta.tipo_rotulo }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-semibold text-gray-500">R$ {{ conta.saldo_inicial }}</p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                                title="Editar"
                                @click="abrirEditar(conta)"
                            >
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>
                            <button
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                                title="Desarquivar"
                                @click="alternarArquivada(conta)"
                            >
                                <span class="material-symbols-outlined text-[20px]">unarchive</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <FormularioModal
            :aberto="modalAberto"
            :tipos="tipos"
            :bancos-sugeridos="bancosSugeridos"
            :conta="contaEmEdicao"
            @fechar="fecharModal"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir conta"
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
