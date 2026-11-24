<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CampoAreaTexto from '@/Components/Formulario/CampoAreaTexto.vue';
import CampoMoeda from '@/Components/Formulario/CampoMoeda.vue';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import LinhaAlternancia from '@/Components/Formulario/LinhaAlternancia.vue';
import AvisoInformativo from '@/Components/Formulario/AvisoInformativo.vue';
import SeletorCategoriaUsuario from '@/Components/Categoria/SeletorCategoriaUsuario.vue';
import SeletorMembroEspaco from '@/Components/EspacoCompartilhado/SeletorMembroEspaco.vue';
import { ultimoDiaDoMes } from '@/Helpers/periodo';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    lancamento: {
        type: Object,
        default: null,
    },
    membros: {
        type: Array,
        default: () => [],
    },
    frequencias: {
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
    urlCriar: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['fechar']);

const editando = computed(() => Boolean(props.lancamento));
const editandoOcorrenciaRecorrente = computed(() => editando.value && Boolean(props.lancamento?.eh_recorrente));

const formulario = useForm({
    descricao: '',
    valor: '0,00',
    data_vencimento: '',
    id_usuario_responsavel: null,
    id_categoria_principal: null,
    id_subcategoria: null,
    observacao: '',
    recorrente: false,
    frequencia_recorrencia: 'mensal',
    mes_final_recorrencia: '',
    aplicar_na_recorrencia: false,
});

const categoriasDoResponsavel = ref([]);
const carregandoCategorias = ref(false);

const idUsuarioAtual = computed(() => props.membros.find((membro) => membro.eh_usuario_atual)?.id_usuario ?? null);

const idCategoriaSelecionada = computed({
    get() {
        return formulario.id_subcategoria || formulario.id_categoria_principal || null;
    },
    set(valor) {
        if (valor === null || valor === undefined || valor === '') {
            formulario.id_categoria_principal = null;
            formulario.id_subcategoria = null;
            return;
        }

        const idSelecionado = Number(valor);
        const categoriaPai = categoriasDoResponsavel.value.find((categoria) => Number(categoria.id) === idSelecionado);

        if (categoriaPai) {
            formulario.id_categoria_principal = categoriaPai.id;
            formulario.id_subcategoria = null;
            return;
        }

        const subcategoria = categoriasDoResponsavel.value
            .flatMap((categoria) => (categoria.subcategorias ?? []).map((item) => ({
                ...item,
                id_categoria_pai: item.id_categoria_pai ?? categoria.id,
            })))
            .find((item) => Number(item.id) === idSelecionado);

        if (subcategoria) {
            formulario.id_categoria_principal = subcategoria.id_categoria_pai;
            formulario.id_subcategoria = subcategoria.id;
            return;
        }

        formulario.id_categoria_principal = idSelecionado;
        formulario.id_subcategoria = null;
    },
});

function dataPadraoDoMes() {
    const hoje = new Date();

    if (hoje.getFullYear() === props.ano && hoje.getMonth() + 1 === props.mes) {
        return hoje.toISOString().slice(0, 10);
    }

    return `${props.ano}-${String(props.mes).padStart(2, '0')}-01`;
}

function obterMembroPorIdUsuario(idUsuario) {
    return props.membros.find((membro) => Number(membro.id_usuario) === Number(idUsuario)) ?? null;
}

async function carregarCategoriasDoResponsavel(idUsuario, { manterSelecao = false } = {}) {
    const membro = obterMembroPorIdUsuario(idUsuario);

    if (!membro?.url_categorias) {
        categoriasDoResponsavel.value = [];
        if (!manterSelecao) {
            formulario.id_categoria_principal = null;
            formulario.id_subcategoria = null;
        }
        return;
    }

    carregandoCategorias.value = true;

    try {
        const resposta = await fetch(membro.url_categorias, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!resposta.ok) {
            throw new Error('Falha ao carregar categorias');
        }

        const dados = await resposta.json();
        categoriasDoResponsavel.value = Array.isArray(dados.categorias) ? dados.categorias : [];

        if (!manterSelecao) {
            formulario.id_categoria_principal = null;
            formulario.id_subcategoria = null;
        }
    } catch {
        categoriasDoResponsavel.value = [];
        if (!manterSelecao) {
            formulario.id_categoria_principal = null;
            formulario.id_subcategoria = null;
        }
    } finally {
        carregandoCategorias.value = false;
    }
}

async function preencherFormulario() {
    formulario.clearErrors();
    formulario.reset();

    if (!props.lancamento) {
        formulario.data_vencimento = dataPadraoDoMes();
        formulario.id_usuario_responsavel = idUsuarioAtual.value;
        await carregarCategoriasDoResponsavel(idUsuarioAtual.value);
        return;
    }

    formulario.descricao = props.lancamento.descricao ?? '';
    formulario.valor = props.lancamento.valor ?? '0,00';
    formulario.data_vencimento = props.lancamento.data_vencimento ?? dataPadraoDoMes();
    formulario.id_usuario_responsavel = props.lancamento.id_usuario_responsavel;
    formulario.observacao = props.lancamento.observacao ?? '';
    await carregarCategoriasDoResponsavel(props.lancamento.id_usuario_responsavel, { manterSelecao: true });
    formulario.id_categoria_principal = props.lancamento.id_categoria_principal ?? null;
    formulario.id_subcategoria = props.lancamento.id_subcategoria ?? null;
}

watch(
    () => [props.aberto, props.lancamento],
    () => {
        if (props.aberto) {
            preencherFormulario();
        }
    },
);

watch(
    () => formulario.id_usuario_responsavel,
    (idResponsavel, idAnterior) => {
        if (!props.aberto || idResponsavel === idAnterior) {
            return;
        }

        carregarCategoriasDoResponsavel(idResponsavel);
    },
);

function fechar() {
    emit('fechar');
}

function resolverIdCategoria(dados) {
    return dados.id_subcategoria || dados.id_categoria_principal || null;
}

function montarDadosDeCriacao(dados) {
    const dadosDeCriacao = {
        descricao: dados.descricao,
        valor: dados.valor,
        data_vencimento: dados.data_vencimento,
        id_usuario_responsavel: dados.id_usuario_responsavel,
        id_categoria: resolverIdCategoria(dados),
        observacao: dados.observacao,
        recorrente: dados.recorrente,
    };

    if (dados.recorrente) {
        dadosDeCriacao.frequencia_recorrencia = dados.frequencia_recorrencia;
        dadosDeCriacao.recorrencia_ate = ultimoDiaDoMes(dados.mes_final_recorrencia);
    }

    return dadosDeCriacao;
}

function montarDadosDeAtualizacao(dados) {
    return {
        versao: props.lancamento.versao,
        descricao: dados.descricao,
        valor: dados.valor,
        data_vencimento: dados.data_vencimento,
        id_usuario_responsavel: dados.id_usuario_responsavel,
        id_categoria: resolverIdCategoria(dados),
        observacao: dados.observacao,
        aplicar_na_recorrencia: editandoOcorrenciaRecorrente.value && dados.aplicar_na_recorrencia,
    };
}

function salvarLancamentoCompartilhado() {
    const opcoes = { preserveScroll: true, onSuccess: fechar };

    if (editando.value) {
        formulario.transform(montarDadosDeAtualizacao).put(props.lancamento.url_atualizar, opcoes);
        return;
    }

    formulario.transform(montarDadosDeCriacao).post(props.urlCriar, opcoes);
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        icone="group"
        :titulo="editando ? 'Editar lançamento compartilhado' : 'Novo lançamento compartilhado'"
        subtitulo="A categoria é do responsável e entra no orçamento pessoal dele."
        :texto-confirmar="editando ? 'Salvar alterações' : 'Salvar lançamento'"
        :processando="formulario.processing || carregandoCategorias"
        @confirmar="salvarLancamentoCompartilhado"
        @fechar="fechar"
    >
        <div class="space-y-5">
            <AvisoInformativo
                v-if="formulario.errors.versao"
                variante="amber"
                :rotulo="formulario.errors.versao"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <CampoTexto
                    id="lancamento-compartilhado-descricao"
                    v-model="formulario.descricao"
                    class="sm:col-span-2"
                    rotulo="Descrição"
                    placeholder="Ex.: Aluguel, Mercado, Internet"
                    obrigatorio
                    :maxlength="255"
                    :erro="formulario.errors.descricao"
                />
                <CampoMoeda
                    id="lancamento-compartilhado-valor"
                    v-model="formulario.valor"
                    rotulo="Valor"
                    obrigatorio
                    variante="red"
                    :erro="formulario.errors.valor"
                />
            </div>

            <CampoTexto
                id="lancamento-compartilhado-vencimento"
                v-model="formulario.data_vencimento"
                tipo="date"
                rotulo="Vencimento"
                obrigatorio
                :erro="formulario.errors.data_vencimento"
            />

            <SeletorMembroEspaco
                v-model="formulario.id_usuario_responsavel"
                :membros="membros"
                rotulo="Quem vai pagar"
                :erro="formulario.errors.id_usuario_responsavel"
            />

            <SeletorCategoriaUsuario
                v-model="idCategoriaSelecionada"
                rotulo="Categoria do responsável"
                :categorias="categoriasDoResponsavel"
                :desabilitado="!formulario.id_usuario_responsavel || carregandoCategorias"
                :erro="formulario.errors.id_categoria"
                id-prefixo="lancamento-compartilhado-categoria"
            />

            <template v-if="!editando">
                <LinhaAlternancia
                    v-model="formulario.recorrente"
                    icone="autorenew"
                    titulo="Lançamento recorrente"
                    descricao="Gera automaticamente nos próximos períodos"
                    :valor-ativo="true"
                    :valor-inativo="false"
                />

                <div v-if="formulario.recorrente" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <CampoSelecao
                        id="lancamento-compartilhado-frequencia"
                        v-model="formulario.frequencia_recorrencia"
                        rotulo="Frequência"
                        :opcoes="frequencias"
                        :erro="formulario.errors.frequencia_recorrencia"
                    />
                    <CampoTexto
                        id="lancamento-compartilhado-recorrencia-ate"
                        v-model="formulario.mes_final_recorrencia"
                        tipo="month"
                        rotulo="Repetir até (opcional)"
                        :erro="formulario.errors.recorrencia_ate"
                    />
                </div>
            </template>

            <LinhaAlternancia
                v-if="editandoOcorrenciaRecorrente"
                v-model="formulario.aplicar_na_recorrencia"
                icone="autorenew"
                titulo="Aplicar nas próximas ocorrências"
                descricao="Atualiza descrição, valor, responsável e categoria dos próximos lançamentos gerados"
                :valor-ativo="true"
                :valor-inativo="false"
            />

            <CampoAreaTexto
                id="lancamento-compartilhado-observacao"
                v-model="formulario.observacao"
                rotulo="Observação"
                :linhas="2"
                :maxlength="1000"
                :erro="formulario.errors.observacao"
            />
        </div>
    </Modal>
</template>
