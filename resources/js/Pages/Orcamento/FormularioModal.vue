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
    categorias: {
        type: Array,
        default: () => [],
    },
    ano: {
        type: Number,
        required: true,
    },
    mes: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['fechar']);

const pagina = usePage();
const urlCriar = computed(() => pagina.props.rotas.orcamentosCriar);
const editando = computed(() => Boolean(props.orcamento));

const categoriasDisponiveis = computed(() => {
    return props.categorias.filter((categoria) => categoria.arquivada !== 'S');
});

const formulario = useForm({
    tipo: 'por_categoria',
    id_categoria: null,
    valor_mensal: '0,00',
    exibir_dashboard: 'N',
    ano: props.ano,
    mes: props.mes,
});

const estadoUi = reactive({
    urlAtualizar: '',
});

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.tipo = 'por_categoria';
    formulario.id_categoria = props.orcamento?.id_categoria ?? null;
    formulario.valor_mensal = props.orcamento?.valor_mensal ?? '0,00';
    formulario.exibir_dashboard = props.orcamento?.exibir_dashboard ?? 'N';
    formulario.ano = props.ano;
    formulario.mes = props.mes;
    estadoUi.urlAtualizar = props.orcamento?.url_atualizar ?? '';
}

watch(
    () => [props.aberto, props.orcamento],
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
            <h2 class="text-lg font-semibold text-ng-ink">
                {{ editando ? 'Editar orçamento' : 'Novo orçamento' }}
            </h2>
            <p class="mt-1 text-sm text-ng-ink-muted">
                Defina um limite mensal para uma categoria de despesa.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <label for="categoria-orcamento" class="block text-sm font-medium text-ng-ink-muted">
                        Categoria
                    </label>
                    <select
                        id="categoria-orcamento"
                        v-model="formulario.id_categoria"
                        class="mt-1 block w-full rounded-lg border-ng-line-strong bg-ng-card text-ng-ink focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                    >
                        <option :value="null">Selecione...</option>
                        <option
                            v-for="categoria in categoriasDisponiveis"
                            :key="categoria.id"
                            :value="categoria.id"
                        >
                            {{ categoria.nome }}
                        </option>
                    </select>
                    <p v-if="formulario.errors.id_categoria" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.id_categoria }}
                    </p>
                </div>

                <div>
                    <label for="valor-mensal-orcamento" class="block text-sm font-medium text-ng-ink-muted">
                        Valor máximo mensal
                    </label>
                    <input
                        id="valor-mensal-orcamento"
                        :value="formulario.valor_mensal"
                        type="text"
                        inputmode="numeric"
                        class="mt-1 block w-full rounded-lg border-ng-line-strong bg-ng-card text-ng-ink focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="0,00"
                        @input="formulario.valor_mensal = aoDigitarMoeda($event)"
                    >
                    <p v-if="formulario.errors.valor_mensal" class="mt-2 text-sm text-red-600">
                        {{ formulario.errors.valor_mensal }}
                    </p>
                </div>

                <div class="rounded-xl border border-ng-line">
                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ng-card-muted text-ng-ink-muted">
                                <span class="material-symbols-outlined text-[20px]">dashboard</span>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm text-ng-ink">Exibir no dashboard</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            class="relative h-6 w-11 shrink-0 rounded-full transition"
                            :class="estaAtivo('exibir_dashboard') ? 'bg-[#1fa67e]' : 'bg-zinc-300 dark:bg-white/15'"
                            :aria-checked="estaAtivo('exibir_dashboard')"
                            @click="toggleSimNao('exibir_dashboard')"
                        >
                            <span
                                class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-ng-card shadow transition"
                                :class="estaAtivo('exibir_dashboard') ? 'translate-x-5' : 'translate-x-0'"
                            />
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    class="inline-flex items-center px-4 py-2 bg-ng-card border border-ng-input-border rounded-md font-semibold text-xs text-ng-ink-secondary uppercase tracking-widest shadow-sm hover:bg-ng-brand-soft"
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
