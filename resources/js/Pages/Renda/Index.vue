<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import FormularioModal from '@/Pages/Renda/FormularioModal.vue';

const props = defineProps({
    rendas: {
        type: Array,
        default: () => [],
    },
    contasBancarias: {
        type: Array,
        default: () => [],
    },
    frequencias: {
        type: Array,
        default: () => [],
    },
    urlCriar: {
        type: String,
        required: true,
    },
});

const pagina = usePage();
const modalAberto = ref(false);
const rendaEmEdicao = ref(null);
const modalExclusaoAberto = ref(false);
const rendaParaExcluir = ref(null);
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

function formatarMoeda(valor) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function abrirCriar() {
    rendaEmEdicao.value = null;
    modalAberto.value = true;
}

function abrirEditar(renda) {
    rendaEmEdicao.value = renda;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    rendaEmEdicao.value = null;
}

function pedirExclusao(renda) {
    rendaParaExcluir.value = renda;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    rendaParaExcluir.value = null;
}

function confirmarExclusao() {
    if (!rendaParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(rendaParaExcluir.value.url_excluir, {
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            rendaParaExcluir.value = null;
        },
    });
}

const mensagemExclusao = computed(() => {
    const nome = rendaParaExcluir.value?.descricao;
    if (!nome) {
        return 'Deseja excluir esta renda? Os lançamentos previstos serão cancelados.';
    }

    return `Deseja excluir a renda "${nome}"? Os lançamentos previstos serão cancelados.`;
});

const totalEsperado = computed(() =>
    props.rendas.reduce((total, renda) => total + Number(renda.valor_esperado_numero ?? 0), 0),
);
</script>

<template>
    <Head title="Rendas" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rendas</h2>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#198a68] transition"
                    @click="abrirCriar"
                >
                    <span class="material-symbols-outlined text-base leading-none">add</span>
                    Nova renda
                </button>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div
                class="flex items-center gap-3 rounded-lg bg-[#fff9e6] px-4 py-4"
                role="status"
            >
                <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-400 text-white"
                    aria-hidden="true"
                >
                    <span class="material-symbols-outlined text-[18px] leading-none">priority_high</span>
                </span>
                <p class="min-w-0 text-sm leading-relaxed text-[#3d3426]">
                    <span class="font-semibold">Atenção:</span>
                    Como o valor de algumas rendas pode mudar como Salário, cadastre o valor médio de recebimento para que seja usado como previsão na sua saúde financeira.
                </p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
                <p class="text-sm font-medium text-gray-500">Total esperado mensal</p>
                <p class="mt-1 text-2xl font-bold text-[#1fa67e]">
                    R$ {{ formatarMoeda(totalEsperado) }}
                </p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Minhas rendas</h3>
                </div>

                <div class="divide-y divide-gray-100">
                    <div
                        v-for="renda in rendas"
                        :key="renda.id"
                        class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/80 transition"
                    >
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#e8f7f1] text-[#1fa67e]">
                            <span class="material-symbols-outlined text-[22px]">payments</span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-900">{{ renda.descricao }}</p>
                            <p class="text-sm text-gray-500">
                                {{ renda.frequencia_rotulo }}
                                · dia {{ renda.dia_esperado }}
                                <span v-if="renda.conta_bancaria_nome">
                                    · {{ renda.conta_bancaria_nome }}
                                </span>
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="font-semibold text-[#1fa67e]">
                                R$ {{ renda.valor_esperado }}
                            </p>
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            <button
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                                title="Editar"
                                @click="abrirEditar(renda)"
                            >
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>
                            <button
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600"
                                title="Excluir"
                                @click="pedirExclusao(renda)"
                            >
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="rendas.length === 0"
                        class="px-6 py-10 text-center text-sm text-gray-500"
                    >
                        Nenhuma renda cadastrada. Clique em <strong>Nova renda</strong> para começar.
                    </div>
                </div>
            </div>
        </div>

        <FormularioModal
            :aberto="modalAberto"
            :renda="rendaEmEdicao"
            :contas-bancarias="contasBancarias"
            :frequencias="frequencias"
            :url-criar="urlCriar"
            @fechar="fecharModal"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir renda"
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
