<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import BotaoAdicionarInline from '@/Components/Ui/BotaoAdicionarInline.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';
import CabecalhoSecao from '@/Components/Ui/CabecalhoSecao.vue';
import CampoBusca from '@/Components/Ui/CampoBusca.vue';
import CardIndicador from '@/Components/Ui/CardIndicador.vue';
import AvisoInformativo from '@/Components/Formulario/AvisoInformativo.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';
import ListaLancamentos from '@/Components/Ui/ListaLancamentos.vue';
import NavegadorPeriodo from '@/Components/Ui/NavegadorPeriodo.vue';
import Paginacao from '@/Components/Ui/Paginacao.vue';
import FormularioModal from '@/Pages/Lancamento/FormularioModal.vue';
import ConfirmarReceitaModal from '@/Pages/Lancamento/ConfirmarReceitaModal.vue';
import { varianteSituacaoLancamento } from '@/Helpers/badge';
import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';
import { navegarMes, nomeDoMes } from '@/Helpers/periodo';
import { estiloDaVariante } from '@/Helpers/varianteCor';

const ATRASO_BUSCA_EM_MS = 400;
const CAMPOS_SOMENTE_FILTRO = ['status', 'busca'];

const ICONE_PADRAO_POR_TIPO = {
    receita: { icone: 'attach_money', variante: 'brand' },
    despesa: { icone: 'shopping_bag', variante: 'red' },
};

const OPCOES_STATUS = [
    { valor: null, rotulo: 'Todos os status' },
    { valor: 'efetivados', rotulo: 'Efetivados' },
    { valor: 'pendentes', rotulo: 'Pendentes' },
];

const props = defineProps({
    ano: { type: Number, required: true },
    mes: { type: Number, required: true },
    lancamentos: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
    totais: { type: Object, default: () => ({}) },
    contadoresPorTipo: { type: Object, default: () => ({ todos: 0, receitas: 0, despesas: 0 }) },
    filtros: { type: Object, default: () => ({}) },
    contasBancarias: { type: Array, default: () => [] },
    cartoesCredito: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
    situacoes: { type: Array, default: () => [] },
    frequencias: { type: Array, default: () => [] },
    urlCriar: { type: String, required: true },
    urlBase: { type: String, required: true },
});

const pagina = usePage();
const modalAberto = ref(false);
const lancamentoEmEdicao = ref(null);
const modalConfirmacaoAberto = ref(false);
const lancamentoParaConfirmar = ref(null);
const modalExclusaoAberto = ref(false);
const lancamentoParaExcluir = ref(null);
const excluindo = ref(false);
const termoBusca = ref(props.filtros.busca ?? '');
let temporizadorBusca = null;

const rotuloPeriodo = computed(() => `${nomeDoMes(props.mes)} ${props.ano}`);
const lista = computed(() => props.lancamentos?.data ?? []);
const links = computed(() => props.lancamentos?.links ?? []);
const possuiFiltrosAtivos = computed(() => Boolean(props.filtros.tipo || props.filtros.status || props.filtros.busca));

watch(
    () => pagina.props.errors,
    (erros) => {
        const camposComErro = Object.keys(erros ?? {});

        if (camposComErro.length === 0 || camposComErro.every((campo) => CAMPOS_SOMENTE_FILTRO.includes(campo))) {
            return;
        }

        if (erros.valor_recebido || erros.data_recebimento) {
            modalConfirmacaoAberto.value = true;
            return;
        }

        modalAberto.value = true;
    },
    { deep: true, immediate: true },
);

watch(termoBusca, (termo) => {
    clearTimeout(temporizadorBusca);
    temporizadorBusca = setTimeout(() => aplicarFiltros({ busca: termo }), ATRASO_BUSCA_EM_MS);
});

onBeforeUnmount(() => clearTimeout(temporizadorBusca));

const opcoesTipo = computed(() => [
    { valor: null, rotulo: 'Todos', contador: props.contadoresPorTipo.todos },
    { valor: 'receita', rotulo: 'Receitas', contador: props.contadoresPorTipo.receitas },
    { valor: 'despesa', rotulo: 'Despesas', contador: props.contadoresPorTipo.despesas, variante: 'red' },
]);

const saldoPositivo = computed(() => Number(props.totais.saldo ?? 0) >= 0);

const lancamentosExibidos = computed(() =>
    lista.value.map((lancamento) => {
        const iconePadrao = ICONE_PADRAO_POR_TIPO[lancamento.tipo] ?? ICONE_PADRAO_POR_TIPO.despesa;

        return {
            chave: lancamento.id,
            original: lancamento,
            descricao: lancamento.descricao,
            subtitulo: [
                lancamento.data_vencimento_formatada,
                lancamento.categoria_nome,
                lancamento.conta_bancaria_nome ?? lancamento.cartao_credito_nome,
            ].filter(Boolean).join(' · '),
            subtituloMono: true,
            valor: lancamento.valor,
            legendaValor: lancamento.esta_efetivado ? 'Efetivado' : 'Previsto',
            icone: lancamento.categoria_icone ?? iconePadrao.icone,
            cor: lancamento.categoria_cor ?? estiloDaVariante(iconePadrao.variante).hex,
            ...montarEtiquetaDoLancamento(lancamento),
            badgeTexto: lancamento.situacao_rotulo ?? '',
            badgeVariante: varianteSituacaoLancamento(lancamento.situacao),
            badgeIcone: lancamento.esta_efetivado ? 'check_circle' : 'schedule',
            badgeNaDescricao: true,
            positivo: lancamento.tipo === 'receita',
            negativo: lancamento.tipo === 'despesa',
        };
    }),
);

function montarEtiquetaDoLancamento(lancamento) {
    if (lancamento.eh_parcelado) {
        return { etiqueta: `${lancamento.parcela_atual}/${lancamento.total_parcelas}`, etiquetaIcone: 'repeat' };
    }

    if (lancamento.eh_recorrente) {
        return { etiqueta: 'Recorrente', etiquetaIcone: 'autorenew' };
    }

    return { etiqueta: '', etiquetaIcone: '' };
}

function montarParametrosDeFiltro(alteracoes = {}) {
    const filtrosAtuais = {
        tipo: props.filtros.tipo ?? null,
        status: props.filtros.status ?? null,
        busca: termoBusca.value || null,
        ...alteracoes,
    };

    return Object.fromEntries(Object.entries(filtrosAtuais).filter(([, valor]) => valor !== null && valor !== ''));
}

function visitarPeriodo(ano, mes, alteracoesDeFiltro = {}) {
    router.get(`${props.urlBase}/${ano}/${mes}`, montarParametrosDeFiltro(alteracoesDeFiltro), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function aplicarFiltros(alteracoes) {
    visitarPeriodo(props.ano, props.mes, alteracoes);
}

function filtrarPorTipo(tipo) {
    aplicarFiltros({ tipo });
}

function filtrarPorStatus(status) {
    aplicarFiltros({ status });
}

function abrirMesAdjacente(deslocamento) {
    const periodo = navegarMes(props.ano, props.mes, deslocamento);
    visitarPeriodo(periodo.ano, periodo.mes);
}

function irParaPagina(url) {
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}

function abrirCriar() {
    lancamentoEmEdicao.value = null;
    modalAberto.value = true;
}

function ehReceitaPrevistaDeRenda(lancamento) {
    return lancamento.eh_renda && lancamento.situacao === 'previsto';
}

function abrirEditar(lancamento) {
    if (ehReceitaPrevistaDeRenda(lancamento)) {
        lancamentoParaConfirmar.value = lancamento;
        modalConfirmacaoAberto.value = true;
        return;
    }

    lancamentoEmEdicao.value = lancamento;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    lancamentoEmEdicao.value = null;
}

function fecharConfirmacao() {
    modalConfirmacaoAberto.value = false;
    lancamentoParaConfirmar.value = null;
}

function podeAlternarSituacao(lancamento) {
    return !lancamento.eh_renda || ehReceitaPrevistaDeRenda(lancamento);
}

function rotuloAlternarSituacao(lancamento) {
    if (lancamento.eh_renda) {
        return 'Confirmar receita';
    }

    return lancamento.situacao === 'pago' ? 'Marcar como pendente' : 'Marcar como pago';
}

function alternarSituacaoLancamento(lancamento) {
    if (lancamento.eh_renda) {
        abrirEditar(lancamento);
        return;
    }

    const situacao = lancamento.situacao === 'pago' ? 'pendente' : 'pago';
    router.patch(lancamento.url_situacao, { situacao }, { preserveScroll: true });
}

function pedirExclusao(lancamento) {
    lancamentoParaExcluir.value = lancamento;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    lancamentoParaExcluir.value = null;
}

function confirmarExclusao() {
    if (!lancamentoParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(lancamentoParaExcluir.value.url_excluir, {
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            lancamentoParaExcluir.value = null;
        },
    });
}

const mensagemExclusao = computed(() => {
    const nome = lancamentoParaExcluir.value?.descricao;

    return nome ? `Deseja excluir "${nome}"?` : 'Deseja excluir este lançamento?';
});

const mensagemListaVazia = computed(() => (possuiFiltrosAtivos.value
    ? 'Nenhum lançamento encontrado com os filtros aplicados.'
    : `Nenhum lançamento em ${rotuloPeriodo.value}.`));
</script>

<template>
    <Head :title="`Lançamentos · ${rotuloPeriodo}`" />

    <AutenticadoLayout titulo="Lançamentos">
        <div class="space-y-6 p-6 lg:p-8">
            <CabecalhoSecao
                tamanho="pagina"
                titulo="Lançamentos Financeiros"
                subtitulo="Acompanhe fluxo de caixa, receitas previstas e despesas mensais."
            >
                <template #acoes>
                    <BotaoAdicionarInline rotulo="Novo Lançamento" @click="abrirCriar" />
                </template>
            </CabecalhoSecao>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <CardIndicador
                    rotulo="Receitas do Mês"
                    sinal="+"
                    :valor="formatarNumeroParaMoeda(totais.receitas)"
                    :descricao="`${totais.quantidade_receitas ?? 0} entrada(s) registrada(s)`"
                    icone="south_west"
                />
                <CardIndicador
                    rotulo="Despesas do Mês"
                    sinal="-"
                    variante="red"
                    :valor="formatarNumeroParaMoeda(totais.despesas)"
                    :descricao="`${totais.quantidade_despesas ?? 0} saída(s) registrada(s)`"
                    icone="north_east"
                />
                <CardIndicador
                    rotulo="Saldo Líquido"
                    :sinal="saldoPositivo ? '+' : '-'"
                    :variante="saldoPositivo ? 'brand' : 'red'"
                    :valor="formatarNumeroParaMoeda(Math.abs(totais.saldo ?? 0))"
                    :percentual="totais.percentual_poupado ?? 0"
                    rotulo-percentual="poupado"
                    icone="trending_up"
                    destacado
                />
            </div>

            <AvisoInformativo
                v-if="Number(totais.destinado_mes ?? 0) > 0"
                variante="sky"
                rotulo="Separado no mês (guardado pelo plano financeiro das rendas):"
                :valor="`R$ ${formatarNumeroParaMoeda(totais.destinado_mes)}`"
                :complemento="`saldo livre: R$ ${formatarNumeroParaMoeda(totais.saldo_livre ?? 0)}`"
            />

            <section class="ng-card overflow-hidden rounded-2xl">
                <div class="space-y-4 border-b border-ng-line p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <NavegadorPeriodo
                            :rotulo="rotuloPeriodo"
                            rotulo-anterior="Mês anterior"
                            rotulo-proximo="Próximo mês"
                            @anterior="abrirMesAdjacente(-1)"
                            @proximo="abrirMesAdjacente(1)"
                        />
                        <GrupoSegmentado
                            :model-value="filtros.tipo"
                            :opcoes="opcoesTipo"
                            rotulo="Filtrar por tipo"
                            @update:model-value="filtrarPorTipo"
                        />
                    </div>

                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                        <CampoBusca
                            v-model="termoBusca"
                            class="flex-1"
                            placeholder="Buscar por descrição, banco ou categoria..."
                        />
                        <GrupoSegmentado
                            :model-value="filtros.status"
                            :opcoes="OPCOES_STATUS"
                            rotulo="Filtrar por status"
                            separado
                            @update:model-value="filtrarPorStatus"
                        />
                    </div>
                </div>

                <ListaLancamentos :itens="lancamentosExibidos" :mensagem-vazia="mensagemListaVazia" sem-moldura>
                    <template #acoes="{ item }">
                        <BotaoIcone
                            v-if="podeAlternarSituacao(item.original)"
                            :icone="item.original.situacao === 'pago' ? 'undo' : 'check_circle'"
                            :rotulo="rotuloAlternarSituacao(item.original)"
                            @click="alternarSituacaoLancamento(item.original)"
                        />
                        <BotaoIcone icone="edit" rotulo="Editar" @click="abrirEditar(item.original)" />
                        <BotaoIcone icone="delete" rotulo="Excluir" variante="perigo" @click="pedirExclusao(item.original)" />
                    </template>
                </ListaLancamentos>

                <div v-if="links.length > 3" class="border-t border-ng-line px-4 py-3">
                    <Paginacao :links="links" @navegar="irParaPagina" />
                </div>
            </section>
        </div>

        <FormularioModal
            :aberto="modalAberto"
            :lancamento="lancamentoEmEdicao"
            :ano="ano"
            :mes="mes"
            :contas-bancarias="contasBancarias"
            :cartoes-credito="cartoesCredito"
            :categorias="categorias"
            :situacoes="situacoes"
            :frequencias="frequencias"
            :url-criar="urlCriar"
            @fechar="fecharModal"
        />

        <ConfirmarReceitaModal
            :aberto="modalConfirmacaoAberto"
            :lancamento="lancamentoParaConfirmar"
            @fechar="fecharConfirmacao"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir lançamento"
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
