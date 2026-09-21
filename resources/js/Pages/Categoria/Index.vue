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
                <h2 class="font-semibold text-xl text-ng-ink leading-tight">
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
                        : 'bg-ng-card text-ng-ink-muted border border-ng-line-strong hover:bg-ng-brand-soft'"
                    @click="filtroTipo = tipo.valor"
                >
                    {{ tipo.rotulo === 'Saída' ? 'Despesas' : 'Entradas' }}
                </button>
            </div>

            <div class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line">
                <div class="px-6 py-4 border-b border-ng-line">
                    <h3 class="text-base font-semibold text-ng-ink">
                        {{ filtroTipo === 'saida' ? 'Despesas' : 'Entradas' }}
                    </h3>
                </div>

                <div class="divide-y divide-ng-line">
                    <template v-for="categoria in categoriasAtivas" :key="categoria.id">
                        <div class="flex items-center gap-4 px-6 py-4 hover:bg-ng-brand-soft/80 transition">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full"
                                :style="{ backgroundColor: `${categoria.cor}20`, color: categoria.cor }"
                            >
                                <span class="material-symbols-outlined text-[22px]">{{ categoria.icone }}</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 min-w-0">
                                    <p class="truncate font-semibold text-ng-ink">{{ categoria.nome }}</p>
                                    <span
                                        v-if="categoria.padrao === 'S'"
                                        class="ng-badge ng-badge--brand shrink-0 !px-1.5 !py-0.5 !text-[10px]"
                                    >
                                        Padrão
                                    </span>
                                </div>
                                <p class="text-sm text-ng-ink-muted">
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
                                    class="rounded-lg p-2 text-ng-ink-muted hover:bg-ng-brand-soft hover:text-[#1fa67e]"
                                    title="Adicionar subcategoria"
                                    @click="abrirCriarSub(categoria)"
                                >
                                    <span class="material-symbols-outlined text-[20px]">add</span>
                                </button>

                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-ng-ink-muted hover:bg-ng-brand-soft hover:text-ng-ink"
                                    title="Editar"
                                    @click="abrirEditar(categoria)"
                                >
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </button>

                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-ng-ink-muted hover:bg-ng-brand-soft hover:text-ng-ink"
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
                                    class="rounded-lg p-2 text-ng-ink-subtle cursor-not-allowed"
                                    title="Não é possível excluir categoria com lançamentos. Arquive em vez disso."
                                    disabled
                                >
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="rounded-lg p-2 text-ng-ink-muted hover:bg-red-500/10 hover:text-red-400"
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
                            class="flex items-center gap-4 py-3 pl-14 pr-6 hover:bg-ng-brand-soft/80 transition bg-ng-input/40"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full"
                                :style="{ backgroundColor: `${sub.cor}20`, color: sub.cor }"
                            >
                                <span class="material-symbols-outlined text-[18px]">{{ sub.icone }}</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ng-ink">{{ sub.nome }}</p>
                                <p class="text-xs text-ng-ink-muted">Subcategoria</p>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-ng-ink-muted hover:bg-ng-brand-soft hover:text-ng-ink"
                                    title="Editar"
                                    @click="abrirEditar(sub, categoria)"
                                >
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </button>

                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-ng-ink-muted hover:bg-ng-brand-soft hover:text-ng-ink"
                                    title="Arquivar"
                                    @click="alternarArquivada(sub)"
                                >
                                    <span class="material-symbols-outlined text-[18px]">archive</span>
                                </button>

                                <button
                                    v-if="!sub.pode_excluir"
                                    type="button"
                                    class="rounded-lg p-2 text-ng-ink-subtle cursor-not-allowed"
                                    title="Não é possível excluir subcategoria com lançamentos."
                                    disabled
                                >
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="rounded-lg p-2 text-ng-ink-muted hover:bg-red-500/10 hover:text-red-400"
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
                            class="flex items-center gap-4 py-3 pl-14 pr-6 hover:bg-ng-brand-soft/80 transition bg-ng-input/40 opacity-70"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full"
                                :style="{ backgroundColor: `${sub.cor}20`, color: sub.cor }"
                            >
                                <span class="material-symbols-outlined text-[18px]">{{ sub.icone }}</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ng-ink">{{ sub.nome }}</p>
                                <p class="text-xs text-ng-ink-muted">Subcategoria arquivada</p>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-ng-ink-muted hover:bg-ng-brand-soft"
                                    title="Editar"
                                    @click="abrirEditar(sub, categoria)"
                                >
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-ng-ink-muted hover:bg-ng-brand-soft"
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
                        class="px-6 py-10 text-center text-sm text-ng-ink-muted"
                    >
                        Nenhuma categoria cadastrada. Clique em <strong>Adicionar categoria</strong> para começar.
                    </div>
                </div>
            </div>

            <div
                v-if="categoriasArquivadas.length > 0"
                class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line opacity-80"
            >
                <div class="px-6 py-4 border-b border-ng-line">
                    <h3 class="text-base font-semibold text-ng-ink">Arquivadas</h3>
                </div>
                <div class="divide-y divide-ng-line">
                    <template v-for="categoria in categoriasArquivadas" :key="categoria.id">
                        <div class="flex items-center gap-4 px-6 py-4 hover:bg-ng-brand-soft/80 transition">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full"
                                :style="{ backgroundColor: `${categoria.cor}20`, color: categoria.cor }"
                            >
                                <span class="material-symbols-outlined text-[22px]">{{ categoria.icone }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-ng-ink">{{ categoria.nome }}</p>
                                <p class="text-sm text-ng-ink-muted">{{ categoria.tipo_rotulo }}</p>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-ng-ink-muted hover:bg-ng-brand-soft"
                                    title="Editar"
                                    @click="abrirEditar(categoria)"
                                >
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-ng-ink-muted hover:bg-ng-brand-soft"
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
                            class="flex items-center gap-4 py-3 pl-14 pr-6 bg-ng-input/40"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full"
                                :style="{ backgroundColor: `${sub.cor}20`, color: sub.cor }"
                            >
                                <span class="material-symbols-outlined text-[18px]">{{ sub.icone }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ng-ink">{{ sub.nome }}</p>
                                <p class="text-xs text-ng-ink-muted">Subcategoria</p>
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
