<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import BarraDistribuicaoSegmentada from '@/Components/Ui/BarraDistribuicaoSegmentada.vue';
import CabecalhoSecao from '@/Components/Ui/CabecalhoSecao.vue';
import CardAdicionarPlaceholder from '@/Components/Ui/CardAdicionarPlaceholder.vue';
import ListaInformacoes from '@/Components/Ui/ListaInformacoes.vue';
import FluxoCapacidadeFinanceira from '@/Components/Renda/FluxoCapacidadeFinanceira.vue';
import RendaResumo from '@/Components/Renda/RendaResumo.vue';
import LinhaItemPlanoFinanceiro from '@/Components/PlanoFinanceiro/LinhaItemPlanoFinanceiro.vue';
import FormularioModal from '@/Pages/Renda/FormularioModal.vue';
import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';
import { distribuirValoresDoPlano, montarSegmentosDistribuicao } from '@/Helpers/planoFinanceiro';

const props = defineProps({
    rendas: {
        type: Array,
        default: () => [],
    },
    capacidadeFinanceiraGeral: {
        type: Object,
        default: () => ({ renda: 0, total_destinado: 0, capacidade: 0, percentual_destinado: 0 }),
    },
    contasBancarias: {
        type: Array,
        default: () => [],
    },
    planosFinanceiros: {
        type: Array,
        default: () => [],
    },
    frequencias: {
        type: Array,
        default: () => [],
    },
    urlCriar: {
        type: String,
        required: true,
    },
    urlPlanosFinanceiros: {
        type: String,
        required: true,
    },
});

const pagina = usePage();
const modalAberto = ref(false);
const rendaEmEdicao = ref(null);
const modalExclusaoAberto = ref(false);
const rendaParaExcluir = ref(null);
const excluindo = ref(false);
const idRendaEmFoco = ref(null);

watch(
    () => pagina.props.errors,
    (erros) => {
        if (erros && Object.keys(erros).length > 0) {
            modalAberto.value = true;
        }
    },
    { deep: true, immediate: true },
);

const rendaEmFoco = computed(() =>
    props.rendas.find((renda) => renda.id === idRendaEmFoco.value)
    ?? props.rendas[0]
    ?? null,
);

const resumoCapacidadeGeral = computed(() =>
    `Capacidade financeira: R$ ${formatarNumeroParaMoeda(props.capacidadeFinanceiraGeral.capacidade ?? 0)}`
    + ` de R$ ${formatarNumeroParaMoeda(props.capacidadeFinanceiraGeral.renda ?? 0)} esperados`,
);

const itensPlanoDaRendaEmFoco = computed(() => (rendaEmFoco.value
    ? distribuirValoresDoPlano(
        rendaEmFoco.value.itens_plano_financeiro ?? [],
        Number(rendaEmFoco.value.valor_esperado_numero ?? 0),
    )
    : []));

const segmentosPlanoDaRendaEmFoco = computed(() => montarSegmentosDistribuicao(
    rendaEmFoco.value?.itens_plano_financeiro ?? [],
));

const tituloPlanoAplicado = computed(() => {
    const nomePlano = rendaEmFoco.value?.plano_financeiro_nome;

    return nomePlano ? `Plano aplicado: ${nomePlano}` : 'Plano aplicado';
});

const itensInformacoesRenda = computed(() => {
    const renda = rendaEmFoco.value;

    if (!renda) {
        return [];
    }

    const capacidade = renda.capacidade_financeira ?? {};

    return [
        {
            chave: 'frequencia',
            rotulo: 'Frequência',
            valor: renda.frequencia_rotulo ?? '-',
        },
        {
            chave: 'dia',
            rotulo: 'Dia do recebimento',
            valor: renda.dia_esperado ? `Dia ${renda.dia_esperado}` : '-',
        },
        {
            chave: 'conta',
            rotulo: 'Conta de recebimento',
            valor: renda.conta_bancaria_nome ?? '-',
        },
        {
            chave: 'plano',
            rotulo: 'Plano financeiro',
            valor: renda.plano_financeiro_nome ?? 'Nenhum',
            separador: true,
        },
        {
            chave: 'valor-esperado',
            rotulo: 'Valor esperado',
            valor: `R$ ${renda.valor_esperado}`,
            classeValor: 'font-mono',
        },
        {
            chave: 'separado',
            rotulo: 'Separado por recebimento',
            valor: `R$ ${formatarNumeroParaMoeda(capacidade.total_destinado ?? 0)}`,
            classeValor: 'font-mono text-sky-500',
        },
        {
            chave: 'capacidade',
            rotulo: 'Capacidade financeira',
            valor: `R$ ${formatarNumeroParaMoeda(capacidade.capacidade ?? renda.valor_esperado_numero ?? 0)}`,
            classeValor: 'font-mono text-ng-brand',
        },
    ];
});

function selecionarRendaEmFoco(renda) {
    idRendaEmFoco.value = renda.id;
}

function abrirCriar() {
    rendaEmEdicao.value = null;
    modalAberto.value = true;
}

function abrirEditar(renda) {
    rendaEmEdicao.value = renda;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    rendaEmEdicao.value = null;
}

function pedirExclusao(renda) {
    rendaParaExcluir.value = renda;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    rendaParaExcluir.value = null;
}

function confirmarExclusao() {
    if (!rendaParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(rendaParaExcluir.value.url_excluir, {
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            rendaParaExcluir.value = null;
        },
    });
}

const mensagemExclusao = computed(() => {
    const nome = rendaParaExcluir.value?.descricao;
    if (!nome) {
        return 'Deseja excluir esta renda? Os lançamentos previstos serão cancelados.';
    }

    return `Deseja excluir a renda "${nome}"? Os lançamentos previstos serão cancelados.`;
});
</script>

<template>
    <Head title="Rendas" />

    <AutenticadoLayout titulo="Rendas">
        <div class="space-y-8 p-6 lg:p-8">
            <div class="ng-alert ng-alert--warning" role="status">
                <span class="ng-alert__icon" aria-hidden="true">
                    <span class="material-symbols-outlined">priority_high</span>
                </span>
                <p class="min-w-0">
                    <span class="font-semibold">Atenção:</span>
                    Como o valor de algumas rendas pode mudar como Salário, cadastre o valor médio de recebimento para que seja usado como previsão na sua saúde financeira.
                </p>
            </div>

            <section class="space-y-4">
                <CabecalhoSecao titulo="Minhas Rendas" :subtitulo="resumoCapacidadeGeral">
                    <template #acoes>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-ng-brand transition hover:text-ng-brand-hover"
                            @click="abrirCriar"
                        >
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            Adicionar Renda
                        </button>
                    </template>
                </CabecalhoSecao>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <RendaResumo
                        v-for="renda in rendas"
                        :key="renda.id"
                        :descricao="renda.descricao"
                        :frequencia-rotulo="renda.frequencia_rotulo ?? ''"
                        :dia-esperado="renda.dia_esperado"
                        :conta-bancaria-nome="renda.conta_bancaria_nome ?? ''"
                        :valor-esperado="renda.valor_esperado"
                        :valor-capacidade="Number(renda.capacidade_financeira?.capacidade ?? renda.valor_esperado_numero ?? 0)"
                        :valor-destinado="Number(renda.capacidade_financeira?.total_destinado ?? 0)"
                        :plano-financeiro-nome="renda.plano_financeiro_nome ?? ''"
                        :selecionado="rendaEmFoco?.id === renda.id"
                        @selecionar="selecionarRendaEmFoco(renda)"
                        @editar="abrirEditar(renda)"
                        @excluir="pedirExclusao(renda)"
                    />

                    <CardAdicionarPlaceholder
                        titulo="Nova Renda"
                        descricao="Salário, freelance ou outra receita"
                        icone="payments"
                        @click="abrirCriar"
                    />
                </div>
            </section>

            <section v-if="rendaEmFoco" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-4">
                    <CabecalhoSecao titulo="Renda em Foco">
                        <template #acoes>
                            <button
                                type="button"
                                class="text-xs font-semibold text-ng-brand transition hover:text-ng-brand-hover"
                                @click="abrirEditar(rendaEmFoco)"
                            >
                                Editar Dados
                            </button>
                        </template>
                    </CabecalhoSecao>

                    <div class="ng-card p-5">
                        <ListaInformacoes :itens="itensInformacoesRenda" />
                    </div>
                </div>

                <div class="space-y-4 lg:col-span-2">
                    <CabecalhoSecao
                        :titulo="tituloPlanoAplicado"
                        subtitulo="Os itens marcados como guardados são separados quando o recebimento desta renda é confirmado."
                    >
                        <template #acoes>
                            <Link
                                :href="urlPlanosFinanceiros"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-ng-brand transition hover:text-ng-brand-hover"
                            >
                                <span class="material-symbols-outlined text-[16px]">tune</span>
                                Gerenciar planos
                            </Link>
                        </template>
                    </CabecalhoSecao>

                    <div class="ng-card overflow-hidden rounded-2xl">
                        <template v-if="itensPlanoDaRendaEmFoco.length">
                            <div class="px-4 pb-1 pt-4">
                                <BarraDistribuicaoSegmentada :segmentos="segmentosPlanoDaRendaEmFoco" />
                            </div>
                            <div class="divide-y divide-ng-line">
                                <LinhaItemPlanoFinanceiro
                                    v-for="item in itensPlanoDaRendaEmFoco"
                                    :key="item.id"
                                    :nome="item.nome"
                                    :regra="item.regra_formatada"
                                    :valor-calculado="item.valor_calculado"
                                    :separar-do-saldo="item.separar_do_saldo"
                                    :conta-destino="item.conta_bancaria_destino_nome ?? ''"
                                    :objetivo="item.objetivo_descricao ?? ''"
                                    :cor="item.cor_hexadecimal"
                                />
                            </div>
                        </template>
                        <p v-else class="px-5 py-8 text-center text-sm text-ng-ink-muted">
                            Nenhum plano financeiro nesta renda. Use <strong>Editar Dados</strong> para escolher como distribuí-la.
                        </p>
                    </div>

                    <div v-if="rendaEmFoco.capacidade_financeira" class="ng-card space-y-3 rounded-2xl p-5">
                        <p class="text-xs font-medium text-ng-ink-muted">Capacidade financeira desta renda</p>
                        <FluxoCapacidadeFinanceira
                            :valor-renda="Number(rendaEmFoco.capacidade_financeira.renda ?? 0)"
                            :valor-destinado="Number(rendaEmFoco.capacidade_financeira.total_destinado ?? 0)"
                            :valor-capacidade="Number(rendaEmFoco.capacidade_financeira.capacidade ?? 0)"
                            :percentual-destinado="Number(rendaEmFoco.capacidade_financeira.percentual_destinado ?? 0)"
                        />
                    </div>
                </div>
            </section>

            <section v-if="rendas.length > 1" class="space-y-4">
                <CabecalhoSecao
                    titulo="Capacidade financeira geral"
                    subtitulo="Soma das rendas esperadas, menos o que os planos financeiros separam do saldo, é o que sobra para lançamentos, objetivos e orçamentos."
                />
                <div class="ng-card rounded-2xl p-5">
                    <FluxoCapacidadeFinanceira
                        :valor-renda="Number(capacidadeFinanceiraGeral.renda ?? 0)"
                        :valor-destinado="Number(capacidadeFinanceiraGeral.total_destinado ?? 0)"
                        :valor-capacidade="Number(capacidadeFinanceiraGeral.capacidade ?? 0)"
                        :percentual-destinado="Number(capacidadeFinanceiraGeral.percentual_destinado ?? 0)"
                    />
                </div>
            </section>
        </div>

        <FormularioModal
            :aberto="modalAberto"
            :renda="rendaEmEdicao"
            :contas-bancarias="contasBancarias"
            :planos-financeiros="planosFinanceiros"
            :frequencias="frequencias"
            :url-criar="urlCriar"
            :url-planos-financeiros="urlPlanosFinanceiros"
            @fechar="fecharModal"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir renda"
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
