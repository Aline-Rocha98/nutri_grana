<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import BotaoAdicionarInline from '@/Components/Ui/BotaoAdicionarInline.vue';
import CabecalhoSecao from '@/Components/Ui/CabecalhoSecao.vue';
import CardAdicionarPlaceholder from '@/Components/Ui/CardAdicionarPlaceholder.vue';
import CardMetaFinanceira from '@/Components/Ui/CardMetaFinanceira.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';
import NavegadorPeriodo from '@/Components/Ui/NavegadorPeriodo.vue';
import CardCotacaoServico from '@/Components/Orcamento/CardCotacaoServico.vue';
import FormularioModal from '@/Pages/Orcamento/FormularioModal.vue';
import FormularioServicoModal from '@/Pages/Orcamento/FormularioServicoModal.vue';
import DetalheSimulacaoModal from '@/Pages/Orcamento/DetalheSimulacaoModal.vue';
import AprovarCotacaoModal from '@/Pages/Orcamento/AprovarCotacaoModal.vue';
import { varianteStatusCotacao } from '@/Helpers/badge';
import { navegarMes, nomeDoMes } from '@/Helpers/periodo';
import {
    GRUPO_COTACAO_APROVADA,
    GRUPO_COTACAO_EM_ANALISE,
    GRUPO_COTACAO_ENCERRADA,
    grupoDoStatusCotacao,
    situacaoDoOrcamento,
} from '@/Helpers/orcamento';

const FORMULARIO_SERVICO_COTACAO = 'cotacao';
const FORMULARIO_SERVICO_APROVACAO = 'aprovacao';
const STATUS_EM_ANALISE = 'em_analise';
const STATUS_APROVADA = 'aprovada';

const ROTULOS_CARD_ORCAMENTO = {
    editar: 'Editar orçamento',
    excluir: 'Excluir orçamento',
};

const props = defineProps({
    ano: {
        type: Number,
        required: true,
    },
    mes: {
        type: Number,
        required: true,
    },
    orcamentos: {
        type: Array,
        default: () => [],
    },
    orcamentosServico: {
        type: Array,
        default: () => [],
    },
    categorias: {
        type: Array,
        default: () => [],
    },
    tipoAtivo: {
        type: String,
        default: 'por_categoria',
    },
    urlBase: {
        type: String,
        required: true,
    },
    contasBancarias: {
        type: Array,
        default: () => [],
    },
    cartoesCredito: {
        type: Array,
        default: () => [],
    },
    formasPagamento: {
        type: Array,
        default: () => [],
    },
    modalidadesPagamento: {
        type: Array,
        default: () => [],
    },
});

const pagina = usePage();
const filtroCotacao = ref(null);
const modalFormularioAberto = ref(false);
const orcamentoEmEdicao = ref(null);
const modalServicoAberto = ref(false);
const orcamentoServicoEmEdicao = ref(null);
const ultimoFormularioServico = ref(FORMULARIO_SERVICO_COTACAO);
const modalExclusaoAberto = ref(false);
const orcamentoParaExcluir = ref(null);
const exclusaoEhServico = ref(false);
const excluindo = ref(false);
const modalDetalheAberto = ref(false);
const cotacaoDetalhe = ref(null);
const modalAprovarAberto = ref(false);
const cotacaoParaAprovar = ref(null);
const cenarioParaAprovar = ref(null);

const ehPorServico = computed(() => props.tipoAtivo === 'por_servico');
const rotuloPeriodo = computed(() => `${nomeDoMes(props.mes)} ${props.ano}`);

const tituloPagina = computed(() => (ehPorServico.value
    ? 'Cotações por serviço'
    : `Orçamentos · ${rotuloPeriodo.value}`));

watch(
    () => pagina.props.errors,
    (erros) => {
        if (!erros || Object.keys(erros).length === 0) {
            return;
        }

        if (!ehPorServico.value) {
            modalFormularioAberto.value = true;
            return;
        }

        if (ultimoFormularioServico.value === FORMULARIO_SERVICO_APROVACAO) {
            modalAprovarAberto.value = true;
            return;
        }

        modalServicoAberto.value = true;
    },
    { deep: true, immediate: true },
);

function contarCotacoesDoGrupo(grupo) {
    return props.orcamentosServico.filter((cotacao) => grupoDoStatusCotacao(cotacao.status) === grupo).length;
}

const opcoesFiltroCotacao = computed(() => [
    { valor: null, rotulo: 'Todas', contador: props.orcamentosServico.length },
    { valor: GRUPO_COTACAO_EM_ANALISE, rotulo: 'Em análise', contador: contarCotacoesDoGrupo(GRUPO_COTACAO_EM_ANALISE) },
    { valor: GRUPO_COTACAO_APROVADA, rotulo: 'Aprovadas', contador: contarCotacoesDoGrupo(GRUPO_COTACAO_APROVADA) },
    { valor: GRUPO_COTACAO_ENCERRADA, rotulo: 'Encerradas', contador: contarCotacoesDoGrupo(GRUPO_COTACAO_ENCERRADA) },
]);

const cotacoesFiltradas = computed(() => (filtroCotacao.value
    ? props.orcamentosServico.filter((cotacao) => grupoDoStatusCotacao(cotacao.status) === filtroCotacao.value)
    : props.orcamentosServico));

function montarPropriedadesDoCardOrcamento(orcamento) {
    const situacao = situacaoDoOrcamento(orcamento);

    return {
        titulo: orcamento.categoria_nome ?? 'Sem categoria',
        subtitulo: `Limite mensal R$ ${orcamento.valor_mensal}`,
        icone: orcamento.categoria_icone || 'category',
        corIcone: orcamento.categoria_cor || '#1fa67e',
        badgeTexto: situacao.rotulo,
        badgeVariante: situacao.variante,
        rotuloProgresso: 'Gasto',
        valorAtual: orcamento.gasto_mes,
        valorMeta: orcamento.valor_mensal,
        percentual: orcamento.percentual_barra,
        corProgresso: situacao.cor,
        legendaEsquerda: `${orcamento.percentual}% usado`,
        classeLegendaEsquerda: orcamento.ultrapassado ? 'text-rose-500' : 'text-ng-brand',
        legendaDireita: orcamento.ultrapassado
            ? `Excedeu: R$ ${orcamento.valor_excedente}`
            : `Resta: R$ ${orcamento.valor_restante}`,
        metricas: [
            { rotulo: 'Disponível', valor: orcamento.valor_restante, destacado: !orcamento.ultrapassado },
            { rotulo: 'Limite mensal', valor: orcamento.valor_mensal },
        ],
    };
}

const cardsDosOrcamentos = computed(() => props.orcamentos.map((orcamento) => ({
    orcamento,
    propriedades: montarPropriedadesDoCardOrcamento(orcamento),
})));

function montarSubtituloDaCotacao(cotacao) {
    return [
        cotacao.fornecedor,
        cotacao.categoria_nome,
        cotacao.data_validade_formatada ? `válida até ${cotacao.data_validade_formatada}` : '',
    ].filter(Boolean).join(' · ');
}

function montarModalidadeDaCotacao(cotacao) {
    return cotacao.total_parcelas > 1
        ? `${cotacao.total_parcelas}x`
        : (cotacao.modalidade_pagamento_rotulo ?? '');
}

function montarFormaDaCotacao(cotacao) {
    return cotacao.forma_pagamento === 'cartao_credito'
        ? (cotacao.cartao_credito_nome ?? '')
        : (cotacao.conta_bancaria_nome ?? '');
}

function montarResumoDaAprovacao(cotacao) {
    if (cotacao.status !== STATUS_APROVADA) {
        return '';
    }

    const parcelas = cotacao.total_parcelas > 1 ? `${cotacao.total_parcelas}x` : 'À vista';

    return `Aprovada em ${cotacao.data_aprovacao_formatada} · ${parcelas} via ${montarFormaDaCotacao(cotacao)} · ${cotacao.compromissos_gerados} compromisso(s) gerado(s)`;
}

function abrirMesAdjacente(deslocamento) {
    const destino = navegarMes(props.ano, props.mes, deslocamento);
    const url = `${props.urlBase}/${destino.ano}/${destino.mes}`;

    router.get(ehPorServico.value ? `${url}?tipo=por_servico` : url, {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

function abrirCriar() {
    if (ehPorServico.value) {
        abrirFormularioCotacao(null);
        return;
    }

    orcamentoEmEdicao.value = null;
    modalFormularioAberto.value = true;
}

function abrirEditarOrcamento(orcamento) {
    orcamentoEmEdicao.value = orcamento;
    modalFormularioAberto.value = true;
}

function fecharFormulario() {
    modalFormularioAberto.value = false;
    orcamentoEmEdicao.value = null;
}

function abrirFormularioCotacao(cotacao) {
    ultimoFormularioServico.value = FORMULARIO_SERVICO_COTACAO;
    orcamentoServicoEmEdicao.value = cotacao;
    modalServicoAberto.value = true;
}

function fecharFormularioServico() {
    modalServicoAberto.value = false;
    orcamentoServicoEmEdicao.value = null;
}

function abrirDetalheSimulacao(cotacao) {
    cotacaoDetalhe.value = cotacao;
    modalDetalheAberto.value = true;
}

function fecharDetalheSimulacao() {
    modalDetalheAberto.value = false;
    cotacaoDetalhe.value = null;
}

function abrirAprovacaoCotacao(cotacao, cenario = null) {
    ultimoFormularioServico.value = FORMULARIO_SERVICO_APROVACAO;
    cotacaoParaAprovar.value = cotacao;
    cenarioParaAprovar.value = cenario;
    modalAprovarAberto.value = true;
}

function aprovarCotacaoDoDetalhe(cenario) {
    abrirAprovacaoCotacao(cotacaoDetalhe.value, cenario);
}

function fecharAprovacaoCotacao() {
    modalAprovarAberto.value = false;
    cotacaoParaAprovar.value = null;
    cenarioParaAprovar.value = null;
}

function finalizarAprovacaoCotacao() {
    fecharAprovacaoCotacao();
    fecharDetalheSimulacao();
}

function recusarCotacao(cotacao) {
    router.post(cotacao.url_recusar, {}, { preserveScroll: true });
}

function pedirExclusao(orcamento, ehServico = false) {
    orcamentoParaExcluir.value = orcamento;
    exclusaoEhServico.value = ehServico;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    orcamentoParaExcluir.value = null;
    exclusaoEhServico.value = false;
}

function confirmarExclusao() {
    if (!orcamentoParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(orcamentoParaExcluir.value.url_excluir, {
        data: exclusaoEhServico.value ? {} : { ano: props.ano, mes: props.mes },
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            orcamentoParaExcluir.value = null;
            exclusaoEhServico.value = false;
        },
    });
}

const mensagemExclusao = computed(() => {
    if (exclusaoEhServico.value) {
        const nome = orcamentoParaExcluir.value?.descricao;

        return nome
            ? `Deseja excluir o orçamento "${nome}"? Nenhum lançamento será criado ou removido.`
            : 'Deseja excluir este orçamento por serviço? Esta ação não pode ser desfeita.';
    }

    const nome = orcamentoParaExcluir.value?.categoria_nome;

    return nome
        ? `Deseja excluir o orçamento de "${nome}"? O histórico de lançamentos permanece intacto.`
        : 'Deseja excluir este orçamento? Esta ação não pode ser desfeita.';
});
</script>

<template>
    <Head :title="tituloPagina" />

    <AutenticadoLayout :titulo="ehPorServico ? 'Orçamentos por serviço' : 'Orçamentos por categoria'">
        <div class="space-y-6 p-6 lg:p-8">
            <CabecalhoSecao
                tamanho="pagina"
                :titulo="ehPorServico ? 'Orçamentos por serviço' : 'Orçamentos por categoria'"
                :subtitulo="ehPorServico
                    ? 'Avalie cotações para saber se convém assumir compromissos com elas.'
                    : `Acompanhe quanto já usou de cada limite em ${rotuloPeriodo.toLowerCase()}.`"
            >
                <template #acoes>
                    <BotaoAdicionarInline
                        :rotulo="ehPorServico ? 'Nova cotação' : 'Novo orçamento'"
                        @click="abrirCriar"
                    />
                </template>
            </CabecalhoSecao>

            <template v-if="ehPorServico">
                <div v-if="orcamentosServico.length" class="border-b border-ng-line pb-4">
                    <GrupoSegmentado
                        v-model="filtroCotacao"
                        :opcoes="opcoesFiltroCotacao"
                        rotulo="Filtrar cotações por status"
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <CardCotacaoServico
                        v-for="cotacao in cotacoesFiltradas"
                        :key="cotacao.id"
                        :descricao="cotacao.descricao"
                        :subtitulo="montarSubtituloDaCotacao(cotacao)"
                        :status-rotulo="cotacao.status_rotulo"
                        :status-variante="varianteStatusCotacao(cotacao.status)"
                        :valor="cotacao.valor"
                        :modalidade="montarModalidadeDaCotacao(cotacao)"
                        :forma="montarFormaDaCotacao(cotacao)"
                        :em-analise="cotacao.status === STATUS_EM_ANALISE"
                        :resumo-compromisso="cotacao.resumo_compromisso ?? ''"
                        :compromisso-viavel="cotacao.pode_assumir_compromisso"
                        :resumo-aprovacao="montarResumoDaAprovacao(cotacao)"
                        :observacao="cotacao.observacao ?? ''"
                        @editar="abrirFormularioCotacao(cotacao)"
                        @excluir="pedirExclusao(cotacao, true)"
                        @detalhar="abrirDetalheSimulacao(cotacao)"
                        @recusar="recusarCotacao(cotacao)"
                        @aprovar="abrirAprovacaoCotacao(cotacao)"
                    />

                    <CardAdicionarPlaceholder
                        class="min-h-[18rem]"
                        titulo="Nova cotação"
                        descricao="Simule o impacto antes de assumir o compromisso"
                        icone="request_quote"
                        @click="abrirCriar"
                    />
                </div>

                <p
                    v-if="orcamentosServico.length && cotacoesFiltradas.length === 0"
                    class="text-center text-sm text-ng-ink-muted"
                >
                    Nenhuma cotação neste status.
                </p>
            </template>

            <template v-else>
                <div class="border-b border-ng-line pb-4">
                    <NavegadorPeriodo
                        :rotulo="rotuloPeriodo"
                        rotulo-anterior="Mês anterior"
                        rotulo-proximo="Próximo mês"
                        @anterior="abrirMesAdjacente(-1)"
                        @proximo="abrirMesAdjacente(1)"
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <CardMetaFinanceira
                        v-for="card in cardsDosOrcamentos"
                        :key="card.orcamento.id"
                        v-bind="card.propriedades"
                        :rotulos="ROTULOS_CARD_ORCAMENTO"
                        @editar="abrirEditarOrcamento(card.orcamento)"
                        @excluir="pedirExclusao(card.orcamento, false)"
                    />

                    <CardAdicionarPlaceholder
                        class="min-h-[18rem]"
                        titulo="Novo orçamento"
                        descricao="Defina um limite mensal para uma categoria"
                        @click="abrirCriar"
                    />
                </div>
            </template>
        </div>

        <FormularioModal
            :aberto="modalFormularioAberto"
            :orcamento="orcamentoEmEdicao"
            :categorias="categorias"
            :ano="ano"
            :mes="mes"
            @fechar="fecharFormulario"
        />

        <FormularioServicoModal
            :aberto="modalServicoAberto"
            :orcamento="orcamentoServicoEmEdicao"
            :categorias="categorias"
            :contas-bancarias="contasBancarias"
            :cartoes-credito="cartoesCredito"
            :formas-pagamento="formasPagamento"
            :modalidades-pagamento="modalidadesPagamento"
            @fechar="fecharFormularioServico"
        />

        <DetalheSimulacaoModal
            :aberto="modalDetalheAberto"
            :cotacao="cotacaoDetalhe"
            :contas-bancarias="contasBancarias"
            @fechar="fecharDetalheSimulacao"
            @aprovar="aprovarCotacaoDoDetalhe"
        />

        <AprovarCotacaoModal
            :aberto="modalAprovarAberto"
            :cotacao="cotacaoParaAprovar"
            :cenario="cenarioParaAprovar"
            :contas-bancarias="contasBancarias"
            :cartoes-credito="cartoesCredito"
            :formas-pagamento="formasPagamento"
            :modalidades-pagamento="modalidadesPagamento"
            @fechar="fecharAprovacaoCotacao"
            @aprovada="finalizarAprovacaoCotacao"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir orçamento"
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
