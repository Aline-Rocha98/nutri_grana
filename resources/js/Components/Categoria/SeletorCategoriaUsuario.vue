<script setup>
import { computed, ref, watch } from 'vue';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';

const props = defineProps({
    categorias: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: [Number, String, null],
        default: null,
    },
    rotulo: {
        type: String,
        default: 'Categoria',
    },
    erro: {
        type: String,
        default: '',
    },
    desabilitado: {
        type: Boolean,
        default: false,
    },
    idPrefixo: {
        type: String,
        default: 'seletor-categoria',
    },
});

const emit = defineEmits(['update:modelValue']);

const idCategoriaPrincipal = ref(null);
const idSubcategoria = ref(null);

const categoriasPai = computed(() => props.categorias.filter((categoria) => !categoria.id_categoria_pai));

const categoriaPrincipalSelecionada = computed(() =>
    categoriasPai.value.find((categoria) => Number(categoria.id) === Number(idCategoriaPrincipal.value)) ?? null,
);

const opcoesCategoriasPai = computed(() => categoriasPai.value.map((categoria) => ({
    valor: categoria.id,
    rotulo: categoria.nome,
})));

const opcoesSubcategorias = computed(() => {
    const subcategorias = categoriaPrincipalSelecionada.value?.subcategorias ?? [];

    return subcategorias.map((subcategoria) => ({
        valor: subcategoria.id,
        rotulo: subcategoria.nome,
    }));
});

watch(
    () => [props.modelValue, props.categorias],
    () => sincronizarComValorExterno(),
    { immediate: true, deep: true },
);

watch(idCategoriaPrincipal, () => {
    if (!opcoesSubcategorias.value.some((opcao) => Number(opcao.valor) === Number(idSubcategoria.value))) {
        idSubcategoria.value = null;
    }

    emitirValorResolvido();
});

watch(idSubcategoria, () => emitirValorResolvido());

function sincronizarComValorExterno() {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        idCategoriaPrincipal.value = null;
        idSubcategoria.value = null;
        return;
    }

    const idSelecionado = Number(props.modelValue);
    const categoria = props.categorias.find((item) => Number(item.id) === idSelecionado);

    if (!categoria) {
        const subcategoria = props.categorias
            .flatMap((item) => item.subcategorias ?? [])
            .find((item) => Number(item.id) === idSelecionado);

        if (subcategoria) {
            idCategoriaPrincipal.value = subcategoria.id_categoria_pai;
            idSubcategoria.value = subcategoria.id;
            return;
        }

        idCategoriaPrincipal.value = null;
        idSubcategoria.value = null;
        return;
    }

    if (categoria.id_categoria_pai) {
        idCategoriaPrincipal.value = categoria.id_categoria_pai;
        idSubcategoria.value = categoria.id;
        return;
    }

    idCategoriaPrincipal.value = categoria.id;
    idSubcategoria.value = null;
}

function emitirValorResolvido() {
    const valor = idSubcategoria.value || idCategoriaPrincipal.value || null;
    const valorAtual = props.modelValue === null || props.modelValue === undefined || props.modelValue === ''
        ? null
        : Number(props.modelValue);
    const valorNovo = valor === null ? null : Number(valor);

    if (valorAtual !== valorNovo) {
        emit('update:modelValue', valorNovo);
    }
}
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <CampoSelecao
            :id="`${idPrefixo}-principal`"
            v-model="idCategoriaPrincipal"
            :rotulo="rotulo"
            :opcoes="opcoesCategoriasPai"
            obrigatorio
            :desabilitado="desabilitado"
            :erro="erro"
        />
        <CampoSelecao
            :id="`${idPrefixo}-sub`"
            v-model="idSubcategoria"
            rotulo="Subcategoria"
            :opcoes="opcoesSubcategorias"
            :desabilitado="desabilitado || !idCategoriaPrincipal || opcoesSubcategorias.length === 0"
        />
    </div>
</template>
