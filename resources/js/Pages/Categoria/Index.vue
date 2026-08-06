<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import FormularioModal from '@/Pages/Categoria/FormularioModal.vue';

const props = defineProps({
    categorias: {
        type: Array,
        default: () => [],
    },
    tipos: {
        type: Array,
        default: () => [],
    },
    icones: {
        type: Array,
        default: () => [],
    },
});

const pagina = usePage();
const filtroTipo = ref('saida');
const modalAberto = ref(false);
const categoriaEmEdicao = ref(null);
const categoriaPaiInicial = ref(null);
const modalExclusaoAberto = ref(false);
const categoriaParaExcluir = ref(null);
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

const categoriasDoTipo = computed(() =>
    props.categorias.filter((categoria) => categoria.tipo === filtroTipo.value),
);

const categoriasAtivas = computed(() =>
    categoriasDoTipo.value.filter((categoria) => categoria.arquivada !== 'S'),
);

const categoriasArquivadas = computed(() =>
    categoriasDoTipo.value.filter((categoria) => categoria.arquivada === 'S'),
);

const categoriasPaisParaModal = computed(() =>
    props.categorias.filter((categoria) => categoria.arquivada !== 'S'),
);

function abrirCriar() {
    categoriaEmEdicao.value = null;
    categoriaPaiInicial.value = null;
    modalAberto.value = true;
}

function abrirCriarSub(categoriaPai) {
    categoriaEmEdicao.value = null;
    categoriaPaiInicial.value = categoriaPai;
    modalAberto.value = true;
}

function abrirEditar(categoria, pai = null) {
    categoriaEmEdicao.value = categoria;
    categoriaPaiInicial.value = pai;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    categoriaEmEdicao.value = null;
    categoriaPaiInicial.value = null;
}

function alternarArquivada(categoria) {
    router.patch(categoria.url_arquivar, {
        arquivada: categoria.arquivada === 'S' ? 'N' : 'S',
    }, {
        preserveScroll: true,
    });
}

function pedirExclusao(categoria) {
    if (!categoria.pode_excluir) {
        return;
    }

    categoriaParaExcluir.value = categoria;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    categoriaParaExcluir.value = null;
}

function confirmarExclusao() {
    if (!categoriaParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(categoriaParaExcluir.value.url_excluir, {
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            categoriaParaExcluir.value = null;
        },
    });
}

const mensagemExclusao = computed(() => {
    const item = categoriaParaExcluir.value;
    if (!item) {
        return 'Deseja excluir esta categoria? Esta ação não pode ser desfeita.';
    }

    const tipo = item.id_categoria_pai ? 'subcategoria' : 'categoria';
    const avisoFilhos = !item.id_categoria_pai && (item.subcategorias?.length ?? 0) > 0
        ? ' As subcategorias vinculadas também serão excluídas.'
        : '';

    return `Deseja excluir a ${tipo} "${item.nome}"? Esta ação não pode ser desfeita.${avisoFilhos}`;
});

function subcategoriasVisiveis(categoria) {
    return (categoria.subcategorias ?? []).filter((sub) => sub.arquivada !== 'S');
}

function subcategoriasArquivadas(categoria) {
    return (categoria.subcategorias ?? []).filter((sub) => sub.arquivada === 'S');
}
</script>

<template>
    <Head title="Categorias" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Categorias
                </h2>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#198a68] transition"
                    @click="abrirCriar"
                >
                    <span class="material-symbols-outlined text-base leading-none">add</span>
                    Adicionar categoria
                </button>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div class="flex gap-2">
                <button
                    v-for="tipo in tipos"
                    :key="tipo.valor"
                    type="button"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                    :class="filtroTipo === tipo.valor
                        ? 'bg-[#1fa67e] text-white'
                        : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
                    @click="filtroTipo = tipo.valor"
                >
                    {{ tipo.rotulo === 'Saída' ? 'Despesas' : 'Entradas' }}
                </button>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">
                        {{ filtroTipo === 'saida' ? 'Despesas' : 'Entradas' }}
                    </h3>
                </div>

                <div class="divide-y divide-gray-100">
                    <template v-for="categoria in categoriasAtivas" :key="categoria.id">
                        <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/80 transition">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full"
                                :style="{ backgroundColor: `${categoria.cor}20`, color: categoria.cor }"
                            >
                                <span class="material-symbols-outlined text-[22px]">{{ categoria.icone }}</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 min-w-0">
                                    <p class="truncate font-semibold text-gray-900">{{ categoria.nome }}</p>
                                    <span
                                        v-if="categoria.padrao === 'S'"
                                        class="shrink-0 rounded-md bg-[#e8f7f1] px-1.5 py-0.5 text-[10px] tracking-wide text-[#1fa67e]"
                                    >
                                        Padrão
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500">
                                    {{ categoria.tipo_rotulo }}
                                    <span v-if="(categoria.subcategorias?.length ?? 0) > 0">
                                        · {{ categoria.subcategorias.length }} subcategoria(s)
                                    </span>
                                    <span v-if="categoria.total_lancamentos > 0">
                                        · {{ categoria.total_lancamentos }} lançamento(s)
                                    </span>
                                </p>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-[#1fa67e]"
                                    title="Adicionar subcategoria"
                                    @click="abrirCriarSub(categoria)"
                                >
                                    <span class="material-symbols-outlined text-[20px]">add</span>
                                </button>

                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                                    title="Editar"
                                    @click="abrirEditar(categoria)"
                                >
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </button>

                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                                    :title="categoria.arquivada === 'S' ? 'Desarquivar' : 'Arquivar'"
                                    @click="alternarArquivada(categoria)"
                                >
                                    <span class="material-symbols-outlined text-[20px]">
                                        {{ categoria.arquivada === 'S' ? 'unarchive' : 'archive' }}
                                    </span>
                                </button>

                                <button
                                    v-if="!categoria.pode_excluir"
                                    type="button"
                                    class="rounded-lg p-2 text-gray-300 cursor-not-allowed"
                                    title="Não é possível excluir categoria com lançamentos. Arquive em vez disso."
                                    disabled
                                >
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600"
                                    title="Excluir"
                                    @click="pedirExclusao(categoria)"
                                >
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            </div>
                        </div>

                        <div
                            v-for="sub in subcategoriasVisiveis(categoria)"
                            :key="sub.id"
                            class="flex items-center gap-4 py-3 pl-14 pr-6 hover:bg-gray-50/80 transition bg-gray-50/40"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full"
                                :style="{ backgroundColor: `${sub.cor}20`, color: sub.cor }"
                            >
                                <span class="material-symbols-outlined text-[18px]">{{ sub.icone }}</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-800">{{ sub.nome }}</p>
                                <p class="text-xs text-gray-500">Subcategoria</p>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                                    title="Editar"
                                    @click="abrirEditar(sub, categoria)"
                                >
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </button>

                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                                    title="Arquivar"
                                    @click="alternarArquivada(sub)"
                                >
                                    <span class="material-symbols-outlined text-[18px]">archive</span>
                                </button>

                                <button
                                    v-if="!sub.pode_excluir"
                                    type="button"
                                    class="rounded-lg p-2 text-gray-300 cursor-not-allowed"
                                    title="Não é possível excluir subcategoria com lançamentos."
                                    disabled
                                >
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600"
                                    title="Excluir"
                                    @click="pedirExclusao(sub)"
                                >
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </div>
                        </div>

                        <div
                            v-for="sub in subcategoriasArquivadas(categoria)"
                            :key="`arq-${sub.id}`"
                            class="flex items-center gap-4 py-3 pl-14 pr-6 hover:bg-gray-50/80 transition bg-gray-50/40 opacity-70"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full"
                                :style="{ backgroundColor: `${sub.cor}20`, color: sub.cor }"
                            >
                                <span class="material-symbols-outlined text-[18px]">{{ sub.icone }}</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-800">{{ sub.nome }}</p>
                                <p class="text-xs text-gray-500">Subcategoria arquivada</p>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                                    title="Editar"
                                    @click="abrirEditar(sub, categoria)"
                                >
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                                    title="Desarquivar"
                                    @click="alternarArquivada(sub)"
                                >
                                    <span class="material-symbols-outlined text-[18px]">unarchive</span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <div
                        v-if="categoriasAtivas.length === 0"
                        class="px-6 py-10 text-center text-sm text-gray-500"
                    >
                        Nenhuma categoria cadastrada. Clique em <strong>Adicionar categoria</strong> para começar.
                    </div>
                </div>
            </div>

            <div
                v-if="categoriasArquivadas.length > 0"
                class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 opacity-80"
            >
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Arquivadas</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <template v-for="categoria in categoriasArquivadas" :key="categoria.id">
                        <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/80 transition">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full"
                                :style="{ backgroundColor: `${categoria.cor}20`, color: categoria.cor }"
                            >
                                <span class="material-symbols-outlined text-[22px]">{{ categoria.icone }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-gray-900">{{ categoria.nome }}</p>
                                <p class="text-sm text-gray-500">{{ categoria.tipo_rotulo }}</p>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                                    title="Editar"
                                    @click="abrirEditar(categoria)"
                                >
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                                    title="Desarquivar"
                                    @click="alternarArquivada(categoria)"
                                >
                                    <span class="material-symbols-outlined text-[20px]">unarchive</span>
                                </button>
                            </div>
                        </div>

                        <div
                            v-for="sub in (categoria.subcategorias ?? [])"
                            :key="sub.id"
                            class="flex items-center gap-4 py-3 pl-14 pr-6 bg-gray-50/40"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full"
                                :style="{ backgroundColor: `${sub.cor}20`, color: sub.cor }"
                            >
                                <span class="material-symbols-outlined text-[18px]">{{ sub.icone }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-800">{{ sub.nome }}</p>
                                <p class="text-xs text-gray-500">Subcategoria</p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <FormularioModal
            :aberto="modalAberto"
            :tipos="tipos"
            :icones="icones"
            :categorias-pais="categoriasPaisParaModal"
            :categoria="categoriaEmEdicao"
            :categoria-pai-inicial="categoriaPaiInicial"
            :tipo-inicial="filtroTipo"
            @fechar="fecharModal"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir categoria"
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
