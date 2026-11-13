<script setup>
import { computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import LinhaCategoria from '@/Components/Categoria/LinhaCategoria.vue';
import CampoBase from '@/Components/Formulario/CampoBase.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import SeletorIcone from '@/Components/Formulario/SeletorIcone.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';
import {
    TIPO_CATEGORIA_SAIDA,
    iconeTipoCategoria,
    rotuloTipoCategoria,
} from '@/Helpers/tipoCategoria';

const COR_PADRAO_CATEGORIA = '#6b7280';
const ICONE_PADRAO_CATEGORIA = 'category';

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
    categoriasPais: {
        type: Array,
        default: () => [],
    },
    categoria: {
        type: Object,
        default: null,
    },
    categoriaPaiInicial: {
        type: Object,
        default: null,
    },
    tipoInicial: {
        type: String,
        default: TIPO_CATEGORIA_SAIDA,
    },
});

const emit = defineEmits(['fechar']);

const pagina = usePage();
const urlCriar = computed(() => pagina.props.rotas.categoriasCriar);
const editando = computed(() => Boolean(props.categoria));
const iconeInicial = computed(() => props.icones[0]?.valor ?? ICONE_PADRAO_CATEGORIA);

const formulario = useForm({
    nome: '',
    tipo: props.tipoInicial,
    icone: ICONE_PADRAO_CATEGORIA,
    id_categoria_pai: null,
});

const ehSubcategoria = computed(() => {
    if (props.categoria) {
        return Boolean(props.categoria.id_categoria_pai);
    }

    return Boolean(formulario.id_categoria_pai);
});

const categoriaPaiSelecionada = computed(() => {
    const idCategoriaPai = props.categoria?.id_categoria_pai ?? formulario.id_categoria_pai;

    if (!idCategoriaPai) {
        return null;
    }

    return props.categoriasPais.find((categoria) => categoria.id === idCategoriaPai)
        ?? props.categoriaPaiInicial;
});

const opcoesTipo = computed(() => props.tipos.map((tipo) => ({
    valor: tipo.valor,
    rotulo: rotuloTipoCategoria(tipo.valor),
    icone: iconeTipoCategoria(tipo.valor),
    variante: tipo.valor === TIPO_CATEGORIA_SAIDA ? 'red' : 'brand',
})));

const tituloModal = computed(() => {
    if (editando.value) {
        return ehSubcategoria.value ? 'Editar subcategoria' : 'Editar categoria';
    }

    return ehSubcategoria.value ? 'Nova subcategoria' : 'Nova categoria';
});

const subtituloModal = computed(() => (ehSubcategoria.value
    ? 'Defina o nome e o ícone da subcategoria.'
    : 'Defina o nome, o tipo e o ícone da categoria.'));

const corPreVisualizacao = computed(
    () => props.categoria?.cor ?? categoriaPaiSelecionada.value?.cor ?? COR_PADRAO_CATEGORIA,
);

const corDestaqueIconeSelecionado = computed(
    () => (corPreVisualizacao.value === COR_PADRAO_CATEGORIA ? '' : corPreVisualizacao.value),
);

const descricaoPreVisualizacao = computed(() => {
    if (ehSubcategoria.value && categoriaPaiSelecionada.value) {
        return `Subcategoria de ${categoriaPaiSelecionada.value.nome}`;
    }

    return rotuloTipoCategoria(formulario.tipo);
});

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.nome = props.categoria?.nome ?? '';
    formulario.icone = props.categoria?.icone ?? iconeInicial.value;
    formulario.id_categoria_pai = props.categoria?.id_categoria_pai
        ?? props.categoriaPaiInicial?.id
        ?? null;
    formulario.tipo = props.categoria?.tipo
        ?? props.categoriaPaiInicial?.tipo
        ?? props.tipoInicial;
}

watch(
    () => [props.aberto, props.categoria, props.categoriaPaiInicial, props.tipoInicial],
    () => {
        if (props.aberto) {
            reiniciarFormulario();
        }
    },
);

function fechar() {
    emit('fechar');
}

function salvarCategoria() {
    const opcoes = {
        preserveScroll: true,
        onSuccess: () => fechar(),
    };

    if (editando.value) {
        formulario.put(props.categoria.url_atualizar, opcoes);
        return;
    }

    formulario.post(urlCriar.value, opcoes);
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        icone="category"
        :titulo="tituloModal"
        :subtitulo="subtituloModal"
        :texto-confirmar="editando ? 'Salvar alterações' : 'Salvar categoria'"
        :processando="formulario.processing"
        @confirmar="salvarCategoria"
        @fechar="fechar"
    >
        <div class="space-y-5">
            <div class="space-y-2">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-ng-ink-subtle">
                    Visualização em tempo real
                </span>
                <div class="overflow-hidden rounded-xl border border-ng-line">
                    <LinhaCategoria
                        :nome="formulario.nome || 'Nome da categoria'"
                        :icone="formulario.icone"
                        :cor="corPreVisualizacao"
                        :descricao="descricaoPreVisualizacao"
                        :padrao="categoria?.padrao === 'S'"
                        :subcategoria="ehSubcategoria"
                    />
                </div>
            </div>

            <CampoTexto
                id="categoria-nome"
                v-model="formulario.nome"
                rotulo="Nome"
                :dica="ehSubcategoria ? 'Ex: Mercado, Combustível' : 'Ex: Alimentação, Transporte'"
                obrigatorio
                :maxlength="100"
                :erro="formulario.errors.nome"
            />

            <CampoBase
                v-if="ehSubcategoria && categoriaPaiSelecionada"
                rotulo="Categoria principal"
                :erro="formulario.errors.id_categoria_pai"
            >
                <p class="ng-card-muted rounded-lg px-3 py-2.5 text-sm font-medium text-ng-ink">
                    {{ categoriaPaiSelecionada.nome }}
                </p>
            </CampoBase>

            <CampoBase
                rotulo="Tipo"
                :dica="ehSubcategoria ? 'Herdado da categoria principal' : ''"
                :erro="formulario.errors.tipo"
            >
                <GrupoSegmentado
                    v-model="formulario.tipo"
                    :opcoes="opcoesTipo"
                    rotulo="Tipo da categoria"
                    :desabilitado="ehSubcategoria"
                    expandido
                />
            </CampoBase>

            <SeletorIcone
                v-model="formulario.icone"
                rotulo="Ícone"
                :opcoes="icones"
                :cor="corDestaqueIconeSelecionado"
                obrigatorio
                :erro="formulario.errors.icone"
            />
        </div>
    </Modal>
</template>
