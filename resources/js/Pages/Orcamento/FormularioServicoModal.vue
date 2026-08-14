<script setup>
import { computed, reactive, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { aoDigitarMoeda } from '@/Helpers/mascaraMoeda';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    orcamento: {
        type: Object,
        default: null,
    },
    simulacao: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['fechar']);

const pagina = usePage();
const urlCriar = computed(() => pagina.props.rotas.orcamentosServicoCriar);
const urlSimular = computed(() => pagina.props.rotas.orcamentosServicoSimular);
const editando = computed(() => Boolean(props.orcamento));

const formulario = useForm({
    descricao: '',
    valor: '0,00',
    data_orcamento: '',
    data_validade: '',
    observacao: '',
});

const estadoUi = reactive({
    urlAtualizar: '',
});

const viabilidade = computed(() => props.simulacao?.viabilidade ?? null);

function dataHoje() {
    return new Date().toISOString().slice(0, 10);
}

function reiniciarFormulario() {
    formulario.clearErrors();

    if (props.simulacao && !props.orcamento) {
        formulario.descricao = props.simulacao.descricao ?? '';
        formulario.valor = props.simulacao.valor ?? '0,00';
        formulario.data_orcamento = props.simulacao.data_orcamento ?? dataHoje();
        formulario.data_validade = props.simulacao.data_validade ?? '';
        formulario.observacao = props.simulacao.observacao ?? '';
    } else {
        formulario.descricao = props.orcamento?.descricao ?? '';
        formulario.valor = props.orcamento?.valor ?? '0,00';
        formulario.data_orcamento = props.orcamento?.data_orcamento ?? dataHoje();
        formulario.data_validade = props.orcamento?.data_validade ?? '';
        formulario.observacao = props.orcamento?.observacao ?? '';
    }

    estadoUi.urlAtualizar = props.orcamento?.url_atualizar ?? '';
}

watch(
    () => [props.aberto, props.orcamento, props.simulacao],
    () => {
        if (props.aberto) {
            reiniciarFormulario();
        }
    },
);

function fechar() {
    emit('fechar');
}

function salvar() {
    const opcoes = {
        preserveScroll: true,
        onSuccess: () => fechar(),
    };

    if (editando.value) {
        formulario.put(estadoUi.urlAtualizar, opcoes);
        return;
    }

    formulario.post(urlCriar.value, opcoes);
}

function simular() {
    formulario.post(urlSimular.value, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <Modal :aberto="aberto">
        <form class="p-6" @submit.prevent="salvar">
            <h2 class="text-lg font-semibold text-gray-900">
                {{ editando ? 'Editar orçamento por serviço' : 'Novo orçamento por serviço' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Registre uma proposta comercial e veja se cabe no seu fluxo financeiro.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <label for="descricao-orcamento-servico" class="block text-sm font-medium text-gray-500">
                        Descrição
                    </label>
                    <input
                        id="descricao-orcamento-servico"
                        v-model="formulario.descricao"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="Ex.: Reforma da loja, pintura do apartamento..."
                    >
                    <p v-if="formulario.errors.descricao" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.descricao }}
                    </p>
                </div>

                <div>
                    <label for="valor-orcamento-servico" class="block text-sm font-medium text-gray-500">
                        Valor
                    </label>
                    <input
                        id="valor-orcamento-servico"
                        :value="formulario.valor"
                        type="text"
                        inputmode="numeric"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="0,00"
                        @input="formulario.valor = aoDigitarMoeda($event)"
                    >
                    <p v-if="formulario.errors.valor" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.valor }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="data-orcamento-servico" class="block text-sm font-medium text-gray-500">
                            Data do orçamento
                        </label>
                        <input
                            id="data-orcamento-servico"
                            v-model="formulario.data_orcamento"
                            type="date"
                            class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                        <p v-if="formulario.errors.data_orcamento" class="mt-2 text-sm text-red-600">
                            {{ formulario.errors.data_orcamento }}
                        </p>
                    </div>
                    <div>
                        <label for="data-validade-servico" class="block text-sm font-medium text-gray-500">
                            Data de validade
                        </label>
                        <input
                            id="data-validade-servico"
                            v-model="formulario.data_validade"
                            type="date"
                            class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                        <p v-if="formulario.errors.data_validade" class="mt-2 text-sm text-red-600">
                            {{ formulario.errors.data_validade }}
                        </p>
                    </div>
                </div>

                <div>
                    <label for="observacao-orcamento-servico" class="block text-sm font-medium text-gray-500">
                        Observação
                    </label>
                    <textarea
                        id="observacao-orcamento-servico"
                        v-model="formulario.observacao"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="Detalhes da proposta, condições de pagamento..."
                    />
                    <p v-if="formulario.errors.observacao" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.observacao }}
                    </p>
                </div>

                <div
                    v-if="viabilidade"
                    class="rounded-xl border p-4 space-y-3"
                    :class="viabilidade.compromete_fluxo ? 'border-amber-200 bg-amber-50' : 'border-emerald-100 bg-emerald-50/60'"
                >
                    <p class="text-sm font-medium text-gray-800">
                        {{ viabilidade.mensagem_principal }}
                    </p>
                    <p class="text-sm text-gray-600">
                        {{ viabilidade.mensagem_disponivel }}
                    </p>

                    <div
                        v-if="viabilidade.comparativo?.mes_rotulo"
                        class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1"
                    >
                        <div class="rounded-lg bg-white/80 px-3 py-2 border border-gray-100">
                            <p class="text-xs text-gray-500">Sem o orçamento · {{ viabilidade.comparativo.mes_rotulo }}</p>
                            <p
                                class="mt-1 text-sm font-semibold"
                                :class="viabilidade.comparativo.saldo_sem_orcamento_numero >= 0 ? 'text-[#1fa67e]' : 'text-red-600'"
                            >
                                R$ {{ viabilidade.comparativo.saldo_sem_orcamento }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-white/80 px-3 py-2 border border-gray-100">
                            <p class="text-xs text-gray-500">Com o orçamento · {{ viabilidade.comparativo.mes_rotulo }}</p>
                            <p
                                class="mt-1 text-sm font-semibold"
                                :class="viabilidade.comparativo.saldo_com_orcamento_numero >= 0 ? 'text-[#1fa67e]' : 'text-red-600'"
                            >
                                R$ {{ viabilidade.comparativo.saldo_com_orcamento }}
                            </p>
                        </div>
                    </div>

                    <p v-if="viabilidade.mensagem_alerta" class="text-sm font-medium text-amber-800">
                        {{ viabilidade.mensagem_alerta }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                    @click="fechar"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="inline-flex items-center px-4 py-2 bg-white border border-[#1fa67e] rounded-md font-semibold text-xs text-[#1fa67e] uppercase tracking-widest shadow-sm hover:bg-emerald-50"
                    :disabled="formulario.processing"
                    @click="simular"
                >
                    Simular impacto
                </button>
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 rounded-md bg-[#1fa67e] text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#198a68] transition"
                    :disabled="formulario.processing"
                >
                    Salvar
                </button>
            </div>
        </form>
    </Modal>
</template>
