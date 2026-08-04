<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { aoDigitarMoeda } from '@/Helpers/mascaraMoeda';
import { opcoesDiasDoMes } from '@/Helpers/formatarDia';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    bandeiras: {
        type: Array,
        default: () => [],
    },
    bancosSugeridos: {
        type: Array,
        default: () => [],
    },
    cartao: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['fechar']);

const pagina = usePage();
const urlCriar = computed(() => pagina.props.rotas.cartoesCreditoCriar);
const editando = computed(() => Boolean(props.cartao));
const sugestoesAbertas = ref(false);
const diasDoMes = opcoesDiasDoMes();

const formulario = useForm({
    nome: '',
    limite_total: '0,00',
    dia_fechamento: 1,
    dia_vencimento: 10,
    bandeira: props.bandeiras[0]?.valor ?? 'visa',
    padrao: null,
});

const estadoUi = reactive({
    urlAtualizar: '',
});

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.nome = props.cartao?.nome ?? '';
    formulario.limite_total = props.cartao?.limite_total ?? '0,00';
    formulario.dia_fechamento = props.cartao?.dia_fechamento ?? 1;
    formulario.dia_vencimento = props.cartao?.dia_vencimento ?? 10;
    formulario.bandeira = props.cartao?.bandeira ?? (props.bandeiras[0]?.valor ?? 'visa');
    formulario.padrao = props.cartao?.padrao ?? null;
    estadoUi.urlAtualizar = props.cartao?.url_atualizar ?? '';
    sugestoesAbertas.value = false;
}

watch(
    () => [props.aberto, props.cartao],
    () => {
        if (props.aberto) {
            reiniciarFormulario();
        }
    },
);

const bancosFiltrados = computed(() => {
    const termo = (formulario.nome || '').trim().toLowerCase();
    if (!termo) {
        return props.bancosSugeridos;
    }

    return props.bancosSugeridos.filter((banco) =>
        banco.nome.toLowerCase().includes(termo),
    );
});

const logoSelecionada = computed(() => {
    const nome = (formulario.nome || '').trim().toLowerCase();
    if (!nome) {
        return null;
    }

    return props.bancosSugeridos.find((item) => item.nome.toLowerCase() === nome)?.logo ?? null;
});

function abrirSugestoes() {
    sugestoesAbertas.value = true;
}

function fecharSugestoes() {
    sugestoesAbertas.value = false;
}

function selecionarBanco(banco) {
    formulario.nome = banco.nome;
    sugestoesAbertas.value = false;
}

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
                {{ editando ? 'Editar cartão' : 'Novo cartão' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">Preencha os dados do cartão de crédito manual.</p>

            <div class="mt-6 space-y-4">
                <div class="relative" @focusout="fecharSugestoes">
                    <label for="nome-cartao" class="block text-sm font-medium text-gray-500">Nome do cartão</label>
                    <div class="relative mt-1">
                        <input
                            id="nome-cartao"
                            v-model="formulario.nome"
                            type="text"
                            class="block w-full rounded-lg border-gray-200 shadow-none bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e] pr-12"
                            autocomplete="off"
                            @click="abrirSugestoes"
                            @input="abrirSugestoes"
                            @keydown.escape.stop="fecharSugestoes"
                        >
                        <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center">
                            <span class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-gray-100">
                                <img
                                    v-if="logoSelecionada"
                                    :src="logoSelecionada"
                                    :alt="formulario.nome"
                                    class="h-full w-full object-cover"
                                >
                                <span v-else class="material-symbols-outlined text-[18px] text-gray-400">credit_card</span>
                            </span>
                        </span>
                    </div>

                    <div
                        v-show="sugestoesAbertas && bancosFiltrados.length > 0"
                        class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-gray-100 bg-white shadow-lg"
                    >
                        <ul class="max-h-56 overflow-y-auto py-1">
                            <li v-for="banco in bancosFiltrados" :key="banco.nome">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-sm text-gray-800 hover:bg-gray-50 transition"
                                    @mousedown.prevent="selecionarBanco(banco)"
                                >
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100">
                                        <img
                                            v-if="banco.logo"
                                            :src="banco.logo"
                                            :alt="banco.nome"
                                            class="h-full w-full object-cover"
                                        >
                                        <span v-else class="material-symbols-outlined text-[18px] text-gray-500">credit_card</span>
                                    </span>
                                    <span>{{ banco.nome }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <p v-if="formulario.errors.nome" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.nome }}
                    </p>
                </div>

                <div>
                    <label for="limite-total" class="block text-sm font-medium text-gray-500">
                        Limite total
                    </label>
                    <input
                        id="limite-total"
                        :value="formulario.limite_total"
                        type="text"
                        inputmode="numeric"
                        class="mt-1 block w-full rounded-lg border-gray-200 shadow-none bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="0,00"
                        @input="formulario.limite_total = aoDigitarMoeda($event)"
                    >
                    <p v-if="formulario.errors.limite_total" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.limite_total }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="dia-fechamento" class="block text-sm font-medium text-gray-500">
                            Dia de fechamento
                        </label>
                        <select
                            id="dia-fechamento"
                            v-model.number="formulario.dia_fechamento"
                            class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                            <option
                                v-for="opcao in diasDoMes"
                                :key="`fechamento-${opcao.valor}`"
                                :value="opcao.valor"
                            >
                                {{ opcao.rotulo }}
                            </option>
                        </select>
                        <p v-if="formulario.errors.dia_fechamento" class="mt-2 text-sm text-red-600">
                            {{ formulario.errors.dia_fechamento }}
                        </p>
                    </div>

                    <div>
                        <label for="dia-vencimento" class="block text-sm font-medium text-gray-500">
                            Dia de vencimento
                        </label>
                        <select
                            id="dia-vencimento"
                            v-model.number="formulario.dia_vencimento"
                            class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                            <option
                                v-for="opcao in diasDoMes"
                                :key="`vencimento-${opcao.valor}`"
                                :value="opcao.valor"
                            >
                                {{ opcao.rotulo }}
                            </option>
                        </select>
                        <p v-if="formulario.errors.dia_vencimento" class="mt-2 text-sm text-red-600">
                            {{ formulario.errors.dia_vencimento }}
                        </p>
                    </div>
                </div>

                <div>
                    <label for="bandeira-cartao" class="block text-sm font-medium text-gray-500">
                        Bandeira
                    </label>
                    <select
                        id="bandeira-cartao"
                        v-model="formulario.bandeira"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                    >
                        <option
                            v-for="opcao in bandeiras"
                            :key="opcao.valor"
                            :value="opcao.valor"
                        >
                            {{ opcao.rotulo }}
                        </option>
                    </select>
                    <p v-if="formulario.errors.bandeira" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.bandeira }}
                    </p>
                </div>

                <div class="divide-y divide-gray-100 rounded-xl border border-gray-100">
                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                                <span class="material-symbols-outlined text-[20px]">credit_card</span>
                            </span>
                            <span class="text-sm text-gray-800">Cartão padrão</span>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            class="relative h-6 w-11 shrink-0 rounded-full transition"
                            :class="estaAtivo('padrao') ? 'bg-[#1fa67e]' : 'bg-gray-200'"
                            :aria-checked="estaAtivo('padrao')"
                            @click="toggleSimNao('padrao')"
                        >
                            <span
                                class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition"
                                :class="estaAtivo('padrao') ? 'translate-x-5' : 'translate-x-0'"
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
