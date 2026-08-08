<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import FormularioModal from '@/Pages/CartaoCredito/FormularioModal.vue';

const props = defineProps({
    cartoesCredito: {
        type: Array,
        default: () => [],
    },
    bandeiras: {
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
const cartaoEmEdicao = ref(null);
const modalExclusaoAberto = ref(false);
const cartaoParaExcluir = ref(null);
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

const cartoesAtivos = computed(() => props.cartoesCredito.filter((cartao) => cartao.arquivada !== 'S'));
const cartoesArquivados = computed(() => props.cartoesCredito.filter((cartao) => cartao.arquivada === 'S'));
const limiteGeral = computed(() =>
    cartoesAtivos.value.reduce((total, cartao) => total + Number(cartao.limite_total_numero || 0), 0),
);

function formatarMoeda(valor) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function abrirCriar() {
    cartaoEmEdicao.value = null;
    modalAberto.value = true;
}

function abrirEditar(cartao) {
    cartaoEmEdicao.value = cartao;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    cartaoEmEdicao.value = null;
}

function alternarArquivada(cartao) {
    router.patch(cartao.url_arquivar, {
        arquivada: cartao.arquivada === 'S' ? 'N' : 'S',
    }, {
        preserveScroll: true,
    });
}

function pedirExclusao(cartao) {
    if (!cartao.pode_excluir) {
        return;
    }

    cartaoParaExcluir.value = cartao;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    cartaoParaExcluir.value = null;
}

function confirmarExclusao() {
    if (!cartaoParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(cartaoParaExcluir.value.url_excluir, {
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            cartaoParaExcluir.value = null;
        },
    });
}

const mensagemExclusao = computed(() => {
    const nome = cartaoParaExcluir.value?.nome;
    if (!nome) {
        return 'Deseja excluir este cartão de crédito? Esta ação não pode ser desfeita.';
    }

    return `Deseja excluir o cartão "${nome}"? Esta ação não pode ser desfeita.`;
});
</script>

<template>
    <Head title="Cartões de crédito" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Cartões de crédito
                </h2>
                <button type="button"
                    class="inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#198a68] transition"
                    @click="abrirCriar"
                >
                    <span class="material-symbols-outlined text-base leading-none">add</span>
                    Adicionar cartão
                </button>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
                <p class="text-sm font-medium text-gray-500">Limite total</p>
                <p class="mt-1 text-2xl font-bold text-[#1fa67e]">
                    R$ {{ formatarMoeda(limiteGeral) }}
                </p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Meus cartões</h3>
                </div>

                <div class="divide-y divide-gray-100">
                    <div v-for="cartao in cartoesAtivos"
                        :key="cartao.id" class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/80 transition"
                    >
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#e8f7f1] text-[#1fa67e]">
                            <img
                                v-if="cartao.logo"
                                :src="cartao.logo"
                                :alt="cartao.nome"
                                class="h-full w-full object-cover"
                            >
                            <span v-else class="material-symbols-outlined text-[22px]">credit_card</span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <p class="truncate font-semibold text-gray-900">{{ cartao.nome }}</p>
                                <span v-if="cartao.padrao === 'S'"
                                    class="shrink-0 rounded-md bg-[#e8f7f1] px-1.5 py-0.5 text-[10px] tracking-wide text-[#1fa67e]"
                                >
                                    Padrão
                                </span>
                            </div>
                            <p class="text-sm text-gray-500">
                                {{ cartao.bandeira_rotulo }}
                                · Fecha dia {{ cartao.dia_fechamento_formatado }}
                                · Vence dia {{ cartao.dia_vencimento_formatado }}
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="font-semibold text-[#1fa67e]">
                                R$ {{ cartao.limite_total }}
                            </p>
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            <a
                                :href="cartao.url_faturas"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                                title="Faturas"
                            >
                                <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                            </a>

                            <button type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                                title="Editar" @click="abrirEditar(cartao)"
                            >
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>

                            <button type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                                :title="cartao.arquivada === 'S' ? 'Desarquivar' : 'Arquivar'"
                                @click="alternarArquivada(cartao)"
                            >
                                <span class="material-symbols-outlined text-[20px]">
                                    {{ cartao.arquivada === 'S' ? 'unarchive' : 'archive' }}
                                </span>
                            </button>

                            <button
                                v-if="!cartao.pode_excluir"
                                type="button"
                                class="rounded-lg p-2 text-gray-300 cursor-not-allowed"
                                title="Dê baixa na fatura antes de excluir"
                                disabled
                            >
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                            <button
                                v-else
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600"
                                title="Excluir"
                                @click="pedirExclusao(cartao)"
                            >
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="cartoesAtivos.length === 0"
                        class="px-6 py-10 text-center text-sm text-gray-500"
                    >
                        Nenhum cartão cadastrado. Clique em <strong>Adicionar cartão</strong> para começar.
                    </div>
                </div>
            </div>

            <div v-if="cartoesArquivados.length > 0"
                class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 opacity-80"
            >
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Arquivados</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <div v-for="cartao in cartoesArquivados"
                        :key="cartao.id" class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/80 transition"
                    >
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#e8f7f1] text-[#1fa67e]">
                            <img
                                v-if="cartao.logo"
                                :src="cartao.logo"
                                :alt="cartao.nome"
                                class="h-full w-full object-cover"
                            >
                            <span v-else class="material-symbols-outlined text-[22px]">credit_card</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-900">{{ cartao.nome }}</p>
                            <p class="text-sm text-gray-500">{{ cartao.bandeira_rotulo }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-semibold text-gray-500">R$ {{ cartao.limite_total }}</p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                                title="Editar"
                                @click="abrirEditar(cartao)"
                            >
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>
                            <button
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                                title="Desarquivar"
                                @click="alternarArquivada(cartao)"
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
            :bandeiras="bandeiras"
            :bancos-sugeridos="bancosSugeridos"
            :cartao="cartaoEmEdicao"
            @fechar="fecharModal"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir cartão"
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
