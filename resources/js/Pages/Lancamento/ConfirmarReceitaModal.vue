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
    lancamento: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['fechar']);

const formulario = useForm({
    valor_recebido: '',
    data_recebimento: '',
});

function formatarMoeda(valor) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.valor_recebido = props.lancamento?.valor
        ?? props.lancamento?.valor_previsto
        ?? '';
    formulario.data_recebimento = props.lancamento?.data_vencimento
        ?? new Date().toISOString().slice(0, 10);
}

watch(
    () => [props.aberto, props.lancamento],
    () => {
        if (props.aberto) {
            reiniciarFormulario();
        }
    },
);

const valorPrevistoExibicao = computed(() => {
    if (props.lancamento?.valor_previsto) {
        return props.lancamento.valor_previsto;
    }

    return formatarMoeda(props.lancamento?.valor_numero);
});

function fechar() {
    emit('fechar');
}

function confirmar() {
    if (!props.lancamento?.url_confirmar_receita) {
        return;
    }

    formulario.patch(props.lancamento.url_confirmar_receita, {
        preserveScroll: true,
        onSuccess: () => fechar(),
    });
}
</script>

<template>
    <Modal :aberto="aberto">
        <form class="p-6" @submit.prevent="confirmar">
            <h2 class="text-lg font-semibold text-gray-900">Confirmar receita?</h2>
            <p class="mt-1 text-sm text-gray-500">
                Informe o valor realmente recebido para atualizar o lançamento.
            </p>

            <div
                class="mt-4 flex items-center gap-3 rounded-lg bg-[#e8f4fc] px-4 py-3"
                role="status"
            >
                <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-500 text-white"
                    aria-hidden="true"
                >
                    <span class="material-symbols-outlined text-[18px] leading-none">info</span>
                </span>
                <p class="min-w-0 text-sm leading-relaxed text-[#1e3a4c]">
                    Este lançamento pertence ao valor cadastrado em
                    <span class="font-semibold">Rendas</span>.
                    O valor previsto serve apenas como referência, ajuste aqui o valor realmente recebido.
                </p>
            </div>

            <div class="mt-6 space-y-4">
                <div>
                    <label for="valor-recebido" class="block text-sm font-medium text-gray-500">
                        Valor recebido
                    </label>
                    <div class="relative mt-1">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">
                            R$
                        </span>
                        <input
                            id="valor-recebido"
                            :value="formulario.valor_recebido"
                            type="text"
                            inputmode="numeric"
                            class="block w-full rounded-lg border-gray-200 pl-10 shadow-none bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            placeholder="0,00"
                            @input="formulario.valor_recebido = aoDigitarMoeda($event)"
                        >
                    </div>
                    <p v-if="formulario.errors.valor_recebido" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.valor_recebido }}
                    </p>
                </div>

                <div>
                    <label for="data-recebimento" class="block text-sm font-medium text-gray-500">
                        Data recebimento
                    </label>
                    <input
                        id="data-recebimento"
                        v-model="formulario.data_recebimento"
                        type="date"
                        class="mt-1 block w-full rounded-lg border-gray-200 shadow-none bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                    >
                    <p v-if="formulario.errors.data_recebimento" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.data_recebimento }}
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
                    Confirmar
                </button>
            </div>
        </form>
    </Modal>
</template>
