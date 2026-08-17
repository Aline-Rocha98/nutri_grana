<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import { aoDigitarMoeda } from '@/Helpers/mascaraMoeda';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    renda: {
        type: Object,
        default: null,
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

const emit = defineEmits(['fechar']);

const editando = computed(() => Boolean(props.renda));
const modalConfirmacaoAberto = ref(false);

const formulario = useForm({
    descricao: '',
    valor_esperado: '',
    id_conta_bancaria: null,
    frequencia: props.frequencias[0]?.valor ?? 'mensal',
    dia_esperado: 1,
    observacao: '',
});

const estadoUi = reactive({
    urlAtualizar: '',
});

const mensagemConfirmacao = computed(() => {
    if (editando.value) {
        return 'Ao salvar, os lançamentos previstos vinculados a esta renda serão atualizados. Deseja confirmar?';
    }

    return 'Ao confirmar, será criado automaticamente um lançamento de receita na tela de Lançamentos. Deseja continuar?';
});

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.descricao = props.renda?.descricao ?? '';
    formulario.valor_esperado = props.renda?.valor_esperado ?? '';
    formulario.id_conta_bancaria = props.renda?.id_conta_bancaria
        ?? props.contasBancarias[0]?.id
        ?? null;
    formulario.frequencia = props.renda?.frequencia
        ?? (props.frequencias[0]?.valor ?? 'mensal');
    formulario.dia_esperado = props.renda?.dia_esperado ?? 1;
    formulario.observacao = props.renda?.observacao ?? '';
    estadoUi.urlAtualizar = props.renda?.url_atualizar ?? '';
    modalConfirmacaoAberto.value = false;
}

watch(
    () => [props.aberto, props.renda],
    () => {
        if (props.aberto) {
            reiniciarFormulario();
        }
    },
);

function fechar() {
    modalConfirmacaoAberto.value = false;
    emit('fechar');
}

function pedirConfirmacao() {
    modalConfirmacaoAberto.value = true;
}

function cancelarConfirmacao() {
    if (formulario.processing) {
        return;
    }

    modalConfirmacaoAberto.value = false;
}

function confirmarSalvar() {
    const opcoes = {
        preserveScroll: true,
        onSuccess: () => fechar(),
        onFinish: () => {
            modalConfirmacaoAberto.value = false;
        },
    };

    if (editando.value) {
        formulario.put(estadoUi.urlAtualizar, opcoes);
        return;
    }

    formulario.post(props.urlCriar, opcoes);
}
</script>

<template>
    <Modal :aberto="aberto">
        <form class="p-6" @submit.prevent="pedirConfirmacao">
            <h2 class="text-lg font-semibold text-gray-900">
                {{ editando ? 'Editar renda' : 'Nova renda' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Cadastre suas rendas mensais, como salários, freelances e outras receitas.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <label for="descricao-renda" class="block text-sm font-medium text-gray-500">
                        Descrição
                    </label>
                    <input
                        id="descricao-renda"
                        v-model="formulario.descricao"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-200 shadow-none bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="Ex.: Salário"
                    >
                    <p v-if="formulario.errors.descricao" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.descricao }}
                    </p>
                </div>

                <div>
                    <label for="valor-esperado-renda" class="block text-sm font-medium text-gray-500">
                        Valor esperado
                    </label>
                    <input
                        id="valor-esperado-renda"
                        :value="formulario.valor_esperado"
                        type="text"
                        inputmode="numeric"
                        class="mt-1 block w-full rounded-lg border-gray-200 shadow-none bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="0,00"
                        @input="formulario.valor_esperado = aoDigitarMoeda($event)"
                    >
                    <p v-if="formulario.errors.valor_esperado" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.valor_esperado }}
                    </p>
                </div>

                <div>
                    <label for="conta-renda" class="block text-sm font-medium text-gray-500">
                        Conta bancária de recebimento
                    </label>
                    <select
                        id="conta-renda"
                        v-model="formulario.id_conta_bancaria"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                    >
                        <option
                            v-for="conta in contasBancarias"
                            :key="conta.id"
                            :value="conta.id"
                        >
                            {{ conta.nome }}
                        </option>
                    </select>
                    <p v-if="formulario.errors.id_conta_bancaria" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.id_conta_bancaria }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="frequencia-renda" class="block text-sm font-medium text-gray-500">
                            Frequência
                        </label>
                        <select
                            id="frequencia-renda"
                            v-model="formulario.frequencia"
                            class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                            <option
                                v-for="opcao in frequencias"
                                :key="opcao.valor"
                                :value="opcao.valor"
                            >
                                {{ opcao.rotulo }}
                            </option>
                        </select>
                        <p v-if="formulario.errors.frequencia" class="mt-2 text-sm text-red-600">
                            {{ formulario.errors.frequencia }}
                        </p>
                    </div>

                    <div>
                        <label for="dia-esperado-renda" class="block text-sm font-medium text-gray-500">
                            Dia esperado de recebimento
                        </label>
                        <input
                            id="dia-esperado-renda"
                            v-model.number="formulario.dia_esperado"
                            type="number"
                            min="1"
                            max="31"
                            class="mt-1 block w-full rounded-lg border-gray-200 shadow-none bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                        <p v-if="formulario.errors.dia_esperado" class="mt-2 text-sm text-red-600">
                            {{ formulario.errors.dia_esperado }}
                        </p>
                    </div>
                </div>

                <div>
                    <label for="observacao-renda" class="block text-sm font-medium text-gray-500">
                        Observações
                    </label>
                    <textarea
                        id="observacao-renda"
                        v-model="formulario.observacao"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-200 shadow-none bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                    />
                    <p v-if="formulario.errors.observacao" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.observacao }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                    @click="fechar"
                >
                    Cancelar
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

    <ModalNotificacao
        :aberto="modalConfirmacaoAberto"
        :titulo="editando ? 'Atualizar renda?' : 'Confirmar cadastro?'"
        :mensagem="mensagemConfirmacao"
        texto-confirmar="Confirmar"
        texto-cancelar="Cancelar"
        :processando="formulario.processing"
        @confirmar="confirmarSalvar"
        @cancelar="cancelarConfirmacao"
    />
</template>
