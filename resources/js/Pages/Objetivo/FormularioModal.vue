<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
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
});

const emit = defineEmits(['fechar']);

const pagina = usePage();
const urlCriar = computed(() => pagina.props.rotas.objetivosCriar);
const editando = computed(() => Boolean(props.objetivo));

const formulario = useForm({
    descricao: '',
    valor_meta: '0,00',
    data_limite: '',
    exibir_dashboard: 'N',
});

const estadoUi = reactive({
    urlAtualizar: '',
});

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.descricao = props.objetivo?.descricao ?? '';
    formulario.valor_meta = props.objetivo?.valor_meta ?? '0,00';
    formulario.data_limite = props.objetivo?.data_limite ?? '';
    formulario.exibir_dashboard = props.objetivo?.exibir_dashboard ?? 'N';
    estadoUi.urlAtualizar = props.objetivo?.url_atualizar ?? '';
}

watch(
    () => [props.aberto, props.objetivo],
    () => {
        if (props.aberto) {
            reiniciarFormulario();
        }
    },
);

function estaAtivo(campo) {
    return formulario[campo] === 'S';
}

function toggleSimNao(campo) {
    formulario[campo] = formulario[campo] === 'S' ? 'N' : 'S';
}

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
</script>

<template>
    <Modal :aberto="aberto">
        <form class="p-6" @submit.prevent="salvar">
            <h2 class="text-lg font-semibold text-gray-900">
                {{ editando ? 'Editar objetivo' : 'Novo objetivo' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Defina a meta financeira e a data limite para juntar o valor.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <label for="descricao-objetivo" class="block text-sm font-medium text-gray-500">
                        Descrição
                    </label>
                    <input
                        id="descricao-objetivo"
                        v-model="formulario.descricao"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="Ex.: Viagem, reserva de emergência..."
                    >
                    <p v-if="formulario.errors.descricao" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.descricao }}
                    </p>
                </div>

                <div>
                    <label for="valor-meta" class="block text-sm font-medium text-gray-500">
                        Valor meta
                    </label>
                    <input
                        id="valor-meta"
                        :value="formulario.valor_meta"
                        type="text"
                        inputmode="numeric"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="0,00"
                        @input="formulario.valor_meta = aoDigitarMoeda($event)"
                    >
                    <p v-if="formulario.errors.valor_meta" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.valor_meta }}
                    </p>
                </div>

                <div>
                    <label for="data-limite" class="block text-sm font-medium text-gray-500">
                        Data limite
                    </label>
                    <input
                        id="data-limite"
                        v-model="formulario.data_limite"
                        type="date"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                    >
                    <p v-if="formulario.errors.data_limite" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.data_limite }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-100">
                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                                <span class="material-symbols-outlined text-[20px]">dashboard</span>
                            </span>
                            <span class="text-sm text-gray-800">Exibir no dashboard</span>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            class="relative h-6 w-11 shrink-0 rounded-full transition"
                            :class="estaAtivo('exibir_dashboard') ? 'bg-[#1fa67e]' : 'bg-gray-200'"
                            :aria-checked="estaAtivo('exibir_dashboard')"
                            @click="toggleSimNao('exibir_dashboard')"
                        >
                            <span
                                class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition"
                                :class="estaAtivo('exibir_dashboard') ? 'translate-x-5' : 'translate-x-0'"
                            />
                        </button>
                    </div>
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
</template>
