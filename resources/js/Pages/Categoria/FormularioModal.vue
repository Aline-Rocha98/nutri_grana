<script setup>
import { computed, reactive, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    tipos: {
        type: Array,
        default: () => [],
    },
    icones: {
        type: Array,
        default: () => [],
    },
    categoria: {
        type: Object,
        default: null,
    },
    tipoInicial: {
        type: String,
        default: 'saida',
    },
});

const emit = defineEmits(['fechar']);

const pagina = usePage();
const urlCriar = computed(() => pagina.props.rotas.categoriasCriar);
const editando = computed(() => Boolean(props.categoria));

const formulario = useForm({
    nome: '',
    tipo: props.tipoInicial,
    icone: props.icones[0]?.valor ?? 'category',
});

const estadoUi = reactive({
    urlAtualizar: '',
});

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.nome = props.categoria?.nome ?? '';
    formulario.tipo = props.categoria?.tipo ?? props.tipoInicial;
    formulario.icone = props.categoria?.icone ?? (props.icones[0]?.valor ?? 'category');
    estadoUi.urlAtualizar = props.categoria?.url_atualizar ?? '';
}

watch(
    () => [props.aberto, props.categoria, props.tipoInicial],
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
</script>

<template>
    <Modal :aberto="aberto">
        <form class="p-6" @submit.prevent="salvar">
            <h2 class="text-lg font-semibold text-gray-900">
                {{ editando ? 'Editar categoria' : 'Nova categoria' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">Defina o nome, tipo e ícone da categoria.</p>

            <div class="mt-6 space-y-4">
                <div>
                    <label for="nome-categoria" class="block text-sm font-medium text-gray-500">
                        Nome
                    </label>
                    <input
                        id="nome-categoria"
                        v-model="formulario.nome"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-200 shadow-none bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        maxlength="100"
                    >
                    <p v-if="formulario.errors.nome" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.nome }}
                    </p>
                </div>

                <div>
                    <label for="tipo-categoria" class="block text-sm font-medium text-gray-500">
                        Tipo
                    </label>
                    <select
                        id="tipo-categoria"
                        v-model="formulario.tipo"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                    >
                        <option
                            v-for="opcao in tipos"
                            :key="opcao.valor"
                            :value="opcao.valor"
                        >
                            {{ opcao.rotulo === 'Saída' ? 'Despesa' : opcao.rotulo }}
                        </option>
                    </select>
                    <p v-if="formulario.errors.tipo" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.tipo }}
                    </p>
                </div>

                <div>
                    <span class="block text-sm font-medium text-gray-500">Ícone</span>
                    <div class="mt-2 grid grid-cols-7 gap-2">
                        <button
                            v-for="opcao in icones"
                            :key="opcao.valor"
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-lg border transition"
                            :class="formulario.icone === opcao.valor
                                ? 'border-[#1fa67e] bg-[#e8f7f1] text-[#1fa67e]'
                                : 'border-gray-200 text-gray-500 hover:bg-gray-50'"
                            :title="opcao.valor"
                            @click="formulario.icone = opcao.valor"
                        >
                            <span class="material-symbols-outlined text-[20px]">{{ opcao.valor }}</span>
                        </button>
                    </div>
                    <p v-if="formulario.errors.icone" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.icone }}
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
</template>
