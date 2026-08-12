<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { aoDigitarMoeda } from '@/Helpers/mascaraMoeda';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    objetivo: {
        type: Object,
        default: null,
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

const emit = defineEmits(['fechar']);

const hoje = new Date().toISOString().slice(0, 10);

const formulario = useForm({
    tipo: 'manual',
    valor: '0,00',
    data_aporte: hoje,
    id_conta_bancaria: null,
    observacao: '',
});

const usaContaBancaria = computed(() => formulario.tipo === 'conta_bancaria');

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.tipo = 'manual';
    formulario.valor = '0,00';
    formulario.data_aporte = hoje;
    formulario.id_conta_bancaria = props.contasBancarias[0]?.id ?? null;
    formulario.observacao = '';
}

watch(
    () => [props.aberto, props.objetivo],
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
    if (!props.objetivo?.url_aportar) {
        return;
    }

    formulario.post(props.objetivo.url_aportar, {
        preserveScroll: true,
        onSuccess: () => fechar(),
    });
}
</script>

<template>
    <Modal :aberto="aberto" max-largura="lg">
        <form class="p-6" @submit.prevent="salvar">
            <h2 class="text-lg font-semibold text-gray-900">Registrar aporte</h2>
            <p class="mt-1 text-sm text-gray-500">
                Adicione valor ao objetivo
                <span v-if="objetivo" class="font-medium text-gray-700">"{{ objetivo.descricao }}"</span>.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <label for="tipo-aporte" class="block text-sm font-medium text-gray-500">
                        Forma do aporte
                    </label>
                    <select
                        id="tipo-aporte"
                        v-model="formulario.tipo"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                    >
                        <option
                            v-for="opcao in tiposAporte"
                            :key="opcao.valor"
                            :value="opcao.valor"
                        >
                            {{ opcao.rotulo }}
                        </option>
                    </select>
                    <p v-if="formulario.errors.tipo" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.tipo }}
                    </p>
                </div>

                <div>
                    <label for="valor-aporte" class="block text-sm font-medium text-gray-500">
                        Valor
                    </label>
                    <input
                        id="valor-aporte"
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

                <div>
                    <label for="data-aporte" class="block text-sm font-medium text-gray-500">
                        Data do aporte
                    </label>
                    <input
                        id="data-aporte"
                        v-model="formulario.data_aporte"
                        type="date"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                    >
                    <p v-if="formulario.errors.data_aporte" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.data_aporte }}
                    </p>
                </div>

                <div v-if="usaContaBancaria">
                    <label for="conta-aporte" class="block text-sm font-medium text-gray-500">
                        Conta bancária
                    </label>
                    <select
                        id="conta-aporte"
                        v-model="formulario.id_conta_bancaria"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                    >
                        <option :value="null" disabled>Selecione a conta</option>
                        <option
                            v-for="conta in contasBancarias"
                            :key="conta.id"
                            :value="conta.id"
                        >
                            {{ conta.nome }} — R$ {{ conta.saldo_atual }}
                        </option>
                    </select>
                    <p v-if="formulario.errors.id_conta_bancaria" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.id_conta_bancaria }}
                    </p>
                    <p v-if="contasBancarias.length === 0" class="mt-2 text-sm text-amber-600">
                        Cadastre uma conta bancária ativa para usar esta opção.
                    </p>
                </div>

                <div>
                    <label for="observacao-aporte" class="block text-sm font-medium text-gray-500">
                        Observação (opcional)
                    </label>
                    <input
                        id="observacao-aporte"
                        v-model="formulario.observacao"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        maxlength="255"
                    >
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
                    :disabled="formulario.processing || (usaContaBancaria && contasBancarias.length === 0)"
                >
                    Registrar aporte
                </button>
            </div>
        </form>
    </Modal>
</template>
