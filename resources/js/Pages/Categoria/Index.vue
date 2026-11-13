<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import GrupoCategoria from '@/Components/Categoria/GrupoCategoria.vue';
import LinhaCategoria from '@/Components/Categoria/LinhaCategoria.vue';
import BotaoAcaoCompacto from '@/Components/Ui/BotaoAcaoCompacto.vue';
import BotaoAdicionarInline from '@/Components/Ui/BotaoAdicionarInline.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';
import CabecalhoSecao from '@/Components/Ui/CabecalhoSecao.vue';
import CampoBusca from '@/Components/Ui/CampoBusca.vue';
import CardPainel from '@/Components/Ui/CardPainel.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';
import LinkAcaoPainel from '@/Components/Ui/LinkAcaoPainel.vue';
import SecaoRecolhivel from '@/Components/Ui/SecaoRecolhivel.vue';
import FormularioModal from '@/Pages/Categoria/FormularioModal.vue';
import { normalizarTextoParaBusca } from '@/Helpers/texto';
import {
    TIPO_CATEGORIA_ENTRADA,
    TIPO_CATEGORIA_SAIDA,
    iconeTipoCategoria,
    rotuloTipoCategoria,
} from '@/Helpers/tipoCategoria';

const MENSAGEM_EXCLUSAO_BLOQUEADA = 'Não é possível excluir com lançamentos vinculados. Arquive em vez disso.';

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
const filtroTipo = ref(TIPO_CATEGORIA_SAIDA);
const termoBusca = ref('');
const idsCategoriasExpandidas = ref(new Set());
const secaoArquivadasAberta = ref(false);
const modalFormularioAberto = ref(false);
const categoriaEmEdicao = ref(null);
const categoriaPaiInicial = ref(null);
const modalExclusaoAberto = ref(false);
const categoriaParaExcluir = ref(null);
const excluindo = ref(false);

watch(
    () => pagina.props.errors,
    (erros) => {
        if (erros && Object.keys(erros).length > 0) {
            modalFormularioAberto.value = true;
        }
    },
    { deep: true, immediate: true },
);

function estaArquivada(item) {
    return item.arquivada === 'S';
}

function subcategoriasAtivasDe(categoria) {
    return (categoria.subcategorias ?? []).filter((subcategoria) => !estaArquivada(subcategoria));
}

function contarCategoriasAtivasDoTipo(tipo) {
    return props.categorias
        .filter((categoria) => categoria.tipo === tipo && !estaArquivada(categoria))
        .reduce((total, categoria) => total + 1 + subcategoriasAtivasDe(categoria).length, 0);
}

const opcoesFiltroTipo = computed(() => [TIPO_CATEGORIA_ENTRADA, TIPO_CATEGORIA_SAIDA].map((tipo) => ({
    valor: tipo,
    rotulo: rotuloTipoCategoria(tipo, { plural: true }),
    icone: iconeTipoCategoria(tipo),
    variante: tipo === TIPO_CATEGORIA_SAIDA ? 'red' : 'brand',
    contador: contarCategoriasAtivasDoTipo(tipo),
})));

const termoBuscaNormalizado = computed(() => normalizarTextoParaBusca(termoBusca.value));
const buscaAtiva = computed(() => termoBuscaNormalizado.value.length > 0);

function nomeCorrespondeABusca(nome) {
    return normalizarTextoParaBusca(nome).includes(termoBuscaNormalizado.value);
}

function montarGrupoCategoriaFiltrado(categoria, subcategorias) {
    if (!buscaAtiva.value) {
        return { categoria, subcategorias, possuiSubcategoriaCorrespondente: false };
    }

    const categoriaCorresponde = nomeCorrespondeABusca(categoria.nome);
    const subcategoriasCorrespondentes = subcategorias.filter((subcategoria) => nomeCorrespondeABusca(subcategoria.nome));

    if (!categoriaCorresponde && subcategoriasCorrespondentes.length === 0) {
        return null;
    }

    return {
        categoria,
        subcategorias: categoriaCorresponde ? subcategorias : subcategoriasCorrespondentes,
        possuiSubcategoriaCorrespondente: subcategoriasCorrespondentes.length > 0,
    };
}

const categoriasDoTipo = computed(() =>
    props.categorias.filter((categoria) => categoria.tipo === filtroTipo.value),
);

const categoriasAtivasDoTipo = computed(() =>
    categoriasDoTipo.value.filter((categoria) => !estaArquivada(categoria)),
);

const gruposCategoriasAtivas = computed(() => categoriasAtivasDoTipo.value
    .map((categoria) => montarGrupoCategoriaFiltrado(categoria, subcategoriasAtivasDe(categoria)))
    .filter(Boolean));

const gruposCategoriasArquivadas = computed(() => categoriasDoTipo.value
    .filter(estaArquivada)
    .map((categoria) => montarGrupoCategoriaFiltrado(categoria, categoria.subcategorias ?? []))
    .filter(Boolean));

const subcategoriasArquivadasDeCategoriasAtivas = computed(() => categoriasAtivasDoTipo.value.flatMap((categoria) =>
    (categoria.subcategorias ?? [])
        .filter(estaArquivada)
        .filter((subcategoria) => !buscaAtiva.value
            || nomeCorrespondeABusca(subcategoria.nome)
            || nomeCorrespondeABusca(categoria.nome))
        .map((subcategoria) => ({ subcategoria, pai: categoria })),
));

const quantidadeArquivadas = computed(
    () => gruposCategoriasArquivadas.value.length + subcategoriasArquivadasDeCategoriasAtivas.value.length,
);

const subcategoriasRecolhiveis = computed(() => filtroTipo.value === TIPO_CATEGORIA_SAIDA);
const rotuloTipoSelecionado = computed(() => rotuloTipoCategoria(filtroTipo.value, { plural: true }));

const subtituloPainelCategorias = computed(() => {
    const quantidade = categoriasAtivasDoTipo.value.length;

    return quantidade === 1 ? '1 categoria principal' : `${quantidade} categorias principais`;
});

const mensagemListaVazia = computed(() => (buscaAtiva.value
    ? 'Nenhuma categoria encontrada para esta busca.'
    : 'Nenhuma categoria cadastrada. Clique em Adicionar categoria para começar.'));

const gruposComSubcategorias = computed(() =>
    gruposCategoriasAtivas.value.filter((grupo) => grupo.subcategorias.length > 0),
);

const todasCategoriasExpandidas = computed(() => gruposComSubcategorias.value.length > 0
    && gruposComSubcategorias.value.every((grupo) => idsCategoriasExpandidas.value.has(grupo.categoria.id)));

watch(termoBuscaNormalizado, () => {
    idsCategoriasExpandidas.value = new Set(
        gruposCategoriasAtivas.value
            .filter((grupo) => grupo.possuiSubcategoriaCorrespondente)
            .map((grupo) => grupo.categoria.id),
    );
});

function categoriaEstaExpandida(idCategoria) {
    return idsCategoriasExpandidas.value.has(idCategoria);
}

function definirExpansaoCategoria(idCategoria, expandida) {
    const idsAtualizados = new Set(idsCategoriasExpandidas.value);

    if (expandida) {
        idsAtualizados.add(idCategoria);
    } else {
        idsAtualizados.delete(idCategoria);
    }

    idsCategoriasExpandidas.value = idsAtualizados;
}

function alternarExpansaoDeTodasCategorias() {
    idsCategoriasExpandidas.value = todasCategoriasExpandidas.value
        ? new Set()
        : new Set(gruposComSubcategorias.value.map((grupo) => grupo.categoria.id));
}

const categoriasPaisParaModal = computed(() =>
    props.categorias.filter((categoria) => !estaArquivada(categoria)),
);

function abrirCadastroCategoria() {
    categoriaEmEdicao.value = null;
    categoriaPaiInicial.value = null;
    modalFormularioAberto.value = true;
}

function abrirCadastroSubcategoria(categoriaPai) {
    categoriaEmEdicao.value = null;
    categoriaPaiInicial.value = categoriaPai;
    modalFormularioAberto.value = true;
    definirExpansaoCategoria(categoriaPai.id, true);
}

function abrirEdicaoCategoria(categoria, categoriaPai = null) {
    categoriaEmEdicao.value = categoria;
    categoriaPaiInicial.value = categoriaPai;
    modalFormularioAberto.value = true;
}

function fecharFormularioCategoria() {
    modalFormularioAberto.value = false;
    categoriaEmEdicao.value = null;
    categoriaPaiInicial.value = null;
}

function alternarArquivamentoCategoria(categoria) {
    router.patch(categoria.url_arquivar, {
        arquivada: estaArquivada(categoria) ? 'N' : 'S',
    }, {
        preserveScroll: true,
    });
}

function rotuloArquivamento(categoria) {
    return estaArquivada(categoria) ? 'Desarquivar' : 'Arquivar';
}

function iconeArquivamento(categoria) {
    return estaArquivada(categoria) ? 'unarchive' : 'archive';
}

function pedirExclusaoCategoria(categoria) {
    if (!categoria.pode_excluir) {
        return;
    }

    categoriaParaExcluir.value = categoria;
    modalExclusaoAberto.value = true;
}

function fecharExclusaoCategoria() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    categoriaParaExcluir.value = null;
}

function confirmarExclusaoCategoria() {
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
    const categoria = categoriaParaExcluir.value;

    if (!categoria) {
        return 'Deseja excluir esta categoria? Esta ação não pode ser desfeita.';
    }

    const tipoRegistro = categoria.id_categoria_pai ? 'subcategoria' : 'categoria';
    const avisoSubcategorias = !categoria.id_categoria_pai && (categoria.subcategorias?.length ?? 0) > 0
        ? ' As subcategorias vinculadas também serão excluídas.'
        : '';

    return `Deseja excluir a ${tipoRegistro} "${categoria.nome}"? Esta ação não pode ser desfeita.${avisoSubcategorias}`;
});
</script>

<template>
    <Head title="Categorias" />

    <AutenticadoLayout titulo="Categorias">
        <div class="space-y-6 p-6 lg:p-8">
            <CabecalhoSecao
                tamanho="pagina"
                titulo="Categorias"
                subtitulo="Organize suas despesas e receitas em categorias e subcategorias personalizadas."
            >
                <template #acoes>
                    <BotaoAdicionarInline rotulo="Adicionar categoria" @click="abrirCadastroCategoria" />
                </template>
            </CabecalhoSecao>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <GrupoSegmentado
                    v-model="filtroTipo"
                    :opcoes="opcoesFiltroTipo"
                    rotulo="Filtrar categorias por tipo"
                    contador-em-badge
                />
                <CampoBusca
                    v-model="termoBusca"
                    class="w-full sm:w-72"
                    placeholder="Buscar categoria..."
                />
            </div>

            <CardPainel
                class="overflow-hidden"
                :titulo="rotuloTipoSelecionado"
                :subtitulo="subtituloPainelCategorias"
                :vazio="gruposCategoriasAtivas.length === 0"
                :mensagem-vazio="mensagemListaVazia"
            >
                <template #acoes>
                    <LinkAcaoPainel
                        v-if="subcategoriasRecolhiveis && gruposComSubcategorias.length"
                        :rotulo="todasCategoriasExpandidas ? 'Recolher todas' : 'Expandir todas'"
                        :icone-esquerda="todasCategoriasExpandidas ? 'unfold_less' : 'unfold_more'"
                        @click="alternarExpansaoDeTodasCategorias"
                    />
                </template>

                <div class="-mx-5 -mb-5 divide-y divide-ng-line border-t border-ng-line">
                    <GrupoCategoria
                        v-for="grupo in gruposCategoriasAtivas"
                        :key="grupo.categoria.id"
                        :categoria="grupo.categoria"
                        :subcategorias="grupo.subcategorias"
                        :recolhivel="subcategoriasRecolhiveis"
                        :expandido="categoriaEstaExpandida(grupo.categoria.id)"
                        @update:expandido="definirExpansaoCategoria(grupo.categoria.id, $event)"
                    >
                        <template #acoes-categoria="{ categoria }">
                            <BotaoAcaoCompacto
                                class="mr-1"
                                icone="add"
                                rotulo="Subcategoria"
                                @click="abrirCadastroSubcategoria(categoria)"
                            />
                            <BotaoIcone icone="edit" rotulo="Editar" @click="abrirEdicaoCategoria(categoria)" />
                            <BotaoIcone
                                :icone="iconeArquivamento(categoria)"
                                :rotulo="rotuloArquivamento(categoria)"
                                @click="alternarArquivamentoCategoria(categoria)"
                            />
                            <BotaoIcone
                                icone="delete"
                                :rotulo="categoria.pode_excluir ? 'Excluir' : MENSAGEM_EXCLUSAO_BLOQUEADA"
                                variante="perigo"
                                :desabilitado="!categoria.pode_excluir"
                                @click="pedirExclusaoCategoria(categoria)"
                            />
                        </template>

                        <template #acoes-subcategoria="{ subcategoria, pai }">
                            <BotaoIcone icone="edit" rotulo="Editar" @click="abrirEdicaoCategoria(subcategoria, pai)" />
                            <BotaoIcone
                                :icone="iconeArquivamento(subcategoria)"
                                :rotulo="rotuloArquivamento(subcategoria)"
                                @click="alternarArquivamentoCategoria(subcategoria)"
                            />
                            <BotaoIcone
                                icone="delete"
                                :rotulo="subcategoria.pode_excluir ? 'Excluir' : MENSAGEM_EXCLUSAO_BLOQUEADA"
                                variante="perigo"
                                :desabilitado="!subcategoria.pode_excluir"
                                @click="pedirExclusaoCategoria(subcategoria)"
                            />
                        </template>
                    </GrupoCategoria>
                </div>
            </CardPainel>

            <SecaoRecolhivel
                v-if="quantidadeArquivadas > 0"
                v-model:aberto="secaoArquivadasAberta"
                titulo="Arquivadas"
                descricao="Categorias e subcategorias fora de uso. Desarquive para voltar a utilizá-las."
                icone="inventory_2"
                :contador="quantidadeArquivadas"
            >
                <div class="divide-y divide-ng-line overflow-hidden rounded-xl border border-ng-line bg-ng-card">
                    <GrupoCategoria
                        v-for="grupo in gruposCategoriasArquivadas"
                        :key="grupo.categoria.id"
                        :categoria="grupo.categoria"
                        :subcategorias="grupo.subcategorias"
                        arquivada
                    >
                        <template #acoes-categoria="{ categoria }">
                            <BotaoIcone icone="edit" rotulo="Editar" @click="abrirEdicaoCategoria(categoria)" />
                            <BotaoIcone
                                icone="unarchive"
                                rotulo="Desarquivar"
                                @click="alternarArquivamentoCategoria(categoria)"
                            />
                        </template>
                    </GrupoCategoria>

                    <LinhaCategoria
                        v-for="item in subcategoriasArquivadasDeCategoriasAtivas"
                        :key="`subcategoria-arquivada-${item.subcategoria.id}`"
                        :nome="item.subcategoria.nome"
                        :icone="item.subcategoria.icone"
                        :cor="item.subcategoria.cor"
                        :descricao="`Subcategoria de ${item.pai.nome}`"
                        subcategoria
                        arquivada
                    >
                        <template #acoes>
                            <BotaoIcone
                                icone="edit"
                                rotulo="Editar"
                                @click="abrirEdicaoCategoria(item.subcategoria, item.pai)"
                            />
                            <BotaoIcone
                                icone="unarchive"
                                rotulo="Desarquivar"
                                @click="alternarArquivamentoCategoria(item.subcategoria)"
                            />
                        </template>
                    </LinhaCategoria>
                </div>
            </SecaoRecolhivel>
        </div>

        <FormularioModal
            :aberto="modalFormularioAberto"
            :tipos="tipos"
            :icones="icones"
            :categorias-pais="categoriasPaisParaModal"
            :categoria="categoriaEmEdicao"
            :categoria-pai-inicial="categoriaPaiInicial"
            :tipo-inicial="filtroTipo"
            @fechar="fecharFormularioCategoria"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir categoria"
            :mensagem="mensagemExclusao"
            texto-confirmar="Excluir"
            texto-cancelar="Cancelar"
            perigo
            :processando="excluindo"
            @confirmar="confirmarExclusaoCategoria"
            @cancelar="fecharExclusaoCategoria"
        />
    </AutenticadoLayout>
</template>
