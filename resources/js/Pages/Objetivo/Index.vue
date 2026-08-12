<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import FormularioModal from '@/Pages/Objetivo/FormularioModal.vue';
import AporteModal from '@/Pages/Objetivo/AporteModal.vue';

const props = defineProps({
    objetivos: {
        type: Array,
        default: () => [],
    },
    contasBancarias: {
        type: Array,
        default: () => [],
    },
    tiposAporte: {
        type: Array,
        default: () => [],
    },
});

const pagina = usePage();
const modalFormularioAberto = ref(false);
const objetivoEmEdicao = ref(null);
const modalAporteAberto = ref(false);
const objetivoParaAporte = ref(null);
const modalExclusaoAberto = ref(false);
const objetivoParaExcluir = ref(null);
const excluindo = ref(false);
const aportesAbertos = ref({});

watch(
    () => pagina.props.errors,
    (erros) => {
        if (!erros || Object.keys(erros).length === 0) {
            return;
        }

        const camposAporte = ['tipo', 'valor', 'data_aporte', 'id_conta_bancaria', 'observacao'];
        const ehErroAporte = camposAporte.some((campo) => Object.prototype.hasOwnProperty.call(erros, campo));

        if (ehErroAporte) {
            modalAporteAberto.value = true;
            return;
        }

        modalFormularioAberto.value = true;
    },
    { deep: true, immediate: true },
);

function classeSituacao(situacao) {
    return {
        adiantado: 'bg-emerald-50 text-emerald-700',
        em_dia: 'bg-sky-50 text-sky-700',
        atrasado: 'bg-amber-50 text-amber-700',
        concluido: 'bg-[#1fa67e]/10 text-[#198a68]',
        vencido: 'bg-red-50 text-red-700',
    }[situacao] ?? 'bg-gray-50 text-gray-600';
}

function abrirCriar() {
    objetivoEmEdicao.value = null;
    modalFormularioAberto.value = true;
}

function abrirEditar(objetivo) {
    objetivoEmEdicao.value = objetivo;
    modalFormularioAberto.value = true;
}

function fecharFormulario() {
    modalFormularioAberto.value = false;
    objetivoEmEdicao.value = null;
}

function abrirAporte(objetivo) {
    objetivoParaAporte.value = objetivo;
    modalAporteAberto.value = true;
}

function fecharAporte() {
    modalAporteAberto.value = false;
    objetivoParaAporte.value = null;
}

function pedirExclusao(objetivo) {
    objetivoParaExcluir.value = objetivo;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    objetivoParaExcluir.value = null;
}

function confirmarExclusao() {
    if (!objetivoParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(objetivoParaExcluir.value.url_excluir, {
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            objetivoParaExcluir.value = null;
        },
    });
}

function alternarAportes(objetivo) {
    aportesAbertos.value[objetivo.id] = !aportesAbertos.value[objetivo.id];
}

const mensagemExclusao = computed(() => {
    const descricao = objetivoParaExcluir.value?.descricao;
    if (!descricao) {
        return 'Deseja excluir este objetivo? Esta ação não pode ser desfeita.';
    }

    return `Deseja excluir o objetivo "${descricao}"? Os aportes serão removidos. Lançamentos gerados em contas permanecem no histórico.`;
});
</script>

<template>
    <Head title="Objetivos" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Objetivos
                </h2>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#198a68] transition"
                    @click="abrirCriar"
                >
                    <span class="material-symbols-outlined text-base leading-none">add</span>
                    Novo objetivo
                </button>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div v-if="objetivos.length === 0" class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-10 text-center">
                <span class="material-symbols-outlined text-4xl text-gray-300">flag</span>
                <p class="mt-3 text-gray-600">Você ainda não cadastrou nenhum objetivo.</p>
                <button
                    type="button"
                    class="mt-4 inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#198a68] transition"
                    @click="abrirCriar"
                >
                    Criar primeiro objetivo
                </button>
            </div>

            <div
                v-for="objetivo in objetivos"
                :key="objetivo.id"
                class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-gray-900">{{ objetivo.descricao }}</h3>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="classeSituacao(objetivo.situacao_ritmo)"
                            >
                                {{ objetivo.situacao_ritmo_rotulo }}
                            </span>
                            <span
                                v-if="objetivo.exibir_dashboard === 'S'"
                                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
                            >
                                No dashboard
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Meta até {{ objetivo.data_limite_formatada }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-[#1fa67e] px-3 py-1.5 text-sm font-medium text-[#1fa67e] hover:bg-[#1fa67e]/5 transition"
                            @click="abrirAporte(objetivo)"
                        >
                            <span class="material-symbols-outlined text-base leading-none">savings</span>
                            Aportar
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 transition"
                            @click="abrirEditar(objetivo)"
                        >
                            Editar
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 transition"
                            @click="pedirExclusao(objetivo)"
                        >
                            Excluir
                        </button>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-700">{{ objetivo.percentual_atual }}% concluído</span>
                        <span class="text-gray-500">
                            R$ {{ objetivo.valor_guardado }} de R$ {{ objetivo.valor_meta }}
                        </span>
                    </div>
                    <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full rounded-full bg-[#1fa67e] transition-all"
                            :style="{ width: `${Math.min(100, objetivo.percentual_atual)}%` }"
                        />
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="rounded-xl bg-gray-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Guardado</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">R$ {{ objetivo.valor_guardado }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Falta</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">R$ {{ objetivo.valor_faltante }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Depósito mensal sugerido</p>
                        <p class="mt-1 text-base font-semibold text-[#1fa67e]">
                            R$ {{ objetivo.deposito_mensal_sugerido }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-gray-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Esperado hoje</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">
                            R$ {{ objetivo.valor_esperado_hoje }}
                        </p>
                    </div>
                </div>

                <div v-if="objetivo.aportes?.length" class="mt-5 border-t border-gray-100 pt-4">
                    <span
                        class="cursor-pointer text-sm font-medium text-gray-700 hover:text-[#1fa67e]"
                        role="button"
                        tabindex="0"
                        @click="alternarAportes(objetivo)"
                        @keydown.enter.prevent="alternarAportes(objetivo)"
                    >
                        Aportes realizados
                        <span class="material-symbols-outlined align-middle text-base">
                            {{ aportesAbertos[objetivo.id] ? 'expand_less' : 'expand_more' }}
                        </span>
                    </span>
                    <ul v-if="aportesAbertos[objetivo.id]" class="mt-2 space-y-2">
                        <li
                            v-for="aporte in objetivo.aportes"
                            :key="aporte.id"
                            class="flex flex-wrap items-center justify-between gap-2 text-sm text-gray-600"
                        >
                            <span>
                                {{ aporte.data_aporte_formatada }} —
                                {{ aporte.tipo_rotulo }}
                                <span v-if="aporte.conta_bancaria_nome" class="text-gray-400">
                                    ({{ aporte.conta_bancaria_nome }})
                                </span>
                            </span>
                            <span class="font-medium text-gray-800">R$ {{ aporte.valor }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <FormularioModal
            :aberto="modalFormularioAberto"
            :objetivo="objetivoEmEdicao"
            @fechar="fecharFormulario"
        />

        <AporteModal
            :aberto="modalAporteAberto"
            :objetivo="objetivoParaAporte"
            :contas-bancarias="contasBancarias"
            :tipos-aporte="tiposAporte"
            @fechar="fecharAporte"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir objetivo"
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
