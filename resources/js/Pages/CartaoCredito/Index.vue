<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import CabecalhoSecao from '@/Components/Ui/CabecalhoSecao.vue';
import CardAdicionarPlaceholder from '@/Components/Ui/CardAdicionarPlaceholder.vue';
import ListaInformacoes from '@/Components/Ui/ListaInformacoes.vue';
import ListaLancamentos from '@/Components/Ui/ListaLancamentos.vue';
import CartaoCreditoResumo from '@/Components/CartaoCredito/CartaoCreditoResumo.vue';
import CartaoCreditoVisual from '@/Components/CartaoCredito/CartaoCreditoVisual.vue';
import FormularioModal from '@/Pages/CartaoCredito/FormularioModal.vue';
import { varianteSituacaoFatura } from '@/Helpers/badge';
import { calcularMelhorDiaCompra } from '@/Helpers/cicloFatura';
import { formatarDia } from '@/Helpers/formatarDia';

const props = defineProps({
    cartoesCredito: {
        type: Array,
        default: () => [],
    },
    lancamentosRecentesPorCartao: {
        type: Object,
        default: () => ({}),
    },
    bandeiras: {
        type: Array,
        default: () => [],
    },
    acabamentos: {
        type: Array,
        default: () => [],
    },
    bancosSugeridos: {
        type: Array,
        default: () => [],
    },
});

const pagina = usePage();
const modalAberto = ref(false);
const cartaoEmEdicao = ref(null);
const modalExclusaoAberto = ref(false);
const cartaoParaExcluir = ref(null);
const excluindo = ref(false);
const idCartaoEmFoco = ref(null);
const exibirTodosCartoes = ref(false);
const arquivadosVisiveis = ref(false);

watch(
    () => pagina.props.errors,
    (erros) => {
        if (erros && Object.keys(erros).length > 0) {
            modalAberto.value = true;
        }
    },
    { deep: true, immediate: true },
);

const nomeUsuarioLogado = computed(() => pagina.props.auth?.usuario?.nome ?? '');
const cartoesAtivos = computed(() => props.cartoesCredito.filter((cartao) => cartao.arquivada !== 'S'));
const cartoesArquivados = computed(() => props.cartoesCredito.filter((cartao) => cartao.arquivada === 'S'));

const cartaoEmFoco = computed(() =>
    cartoesAtivos.value.find((cartao) => cartao.id === idCartaoEmFoco.value)
    ?? cartoesAtivos.value.find((cartao) => cartao.padrao === 'S')
    ?? cartoesAtivos.value[0]
    ?? null,
);

const nomesCartoesPorId = computed(() =>
    Object.fromEntries(props.cartoesCredito.map((cartao) => [cartao.id, cartao.nome])),
);

const lancamentosDaVisao = computed(() => {
    if (exibirTodosCartoes.value) {
        return cartoesAtivos.value
            .flatMap((cartao) => props.lancamentosRecentesPorCartao[cartao.id] ?? [])
            .sort((primeiro, segundo) => (segundo.data_vencimento ?? '').localeCompare(primeiro.data_vencimento ?? ''));
    }

    if (!cartaoEmFoco.value) {
        return [];
    }

    return props.lancamentosRecentesPorCartao[cartaoEmFoco.value.id] ?? [];
});

const lancamentosExibidos = computed(() =>
    lancamentosDaVisao.value.map((lancamento) => ({
        chave: lancamento.id,
        descricao: lancamento.descricao,
        subtitulo: [
            lancamento.categoria_nome,
            exibirTodosCartoes.value ? nomesCartoesPorId.value[lancamento.id_cartao_credito] : null,
            lancamento.data_vencimento_formatada,
        ].filter(Boolean).join(' · '),
        valor: lancamento.valor,
        icone: lancamento.categoria_icone ?? undefined,
        cor: lancamento.categoria_cor ?? undefined,
        etiqueta: lancamento.eh_parcelado ? `${lancamento.parcela_atual}/${lancamento.total_parcelas}` : '',
        badgeTexto: `Fatura ${lancamento.situacao_fatura_rotulo}`,
        badgeVariante: varianteSituacaoFatura(lancamento.situacao_fatura),
        negativo: true,
    })),
);

const tituloLancamentos = computed(() => {
    if (exibirTodosCartoes.value || !cartaoEmFoco.value) {
        return 'Lançamentos Recentes (Todos os cartões)';
    }

    return `Lançamentos Recentes (${cartaoEmFoco.value.nome})`;
});

const itensInformacoesConta = computed(() => {
    const cartao = cartaoEmFoco.value;

    if (!cartao) {
        return [];
    }

    return [
        {
            chave: 'status',
            rotulo: 'Status da Conta',
            valor: 'Ativo e liberado',
            indicador: 'bg-ng-brand',
            classeValor: 'text-xs text-ng-brand',
            separador: true,
        },
        {
            chave: 'melhor-dia',
            rotulo: 'Melhor dia de compra',
            valor: `Dia ${formatarDia(calcularMelhorDiaCompra(cartao.dia_fechamento))}`,
        },
        {
            chave: 'vencimento',
            rotulo: 'Vencimento da fatura',
            valor: `Dia ${cartao.dia_vencimento_formatado}`,
        },
        {
            chave: 'limite-total',
            rotulo: 'Limite total',
            valor: `R$ ${cartao.limite_total}`,
            classeValor: 'font-mono text-ng-brand',
        },
    ];
});

function selecionarCartaoEmFoco(cartao) {
    idCartaoEmFoco.value = cartao.id;
    exibirTodosCartoes.value = false;
}

function alternarExibicaoTodosCartoes() {
    exibirTodosCartoes.value = !exibirTodosCartoes.value;
}

function abrirCriar() {
    cartaoEmEdicao.value = null;
    modalAberto.value = true;
}

function abrirEditar(cartao) {
    cartaoEmEdicao.value = cartao;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    cartaoEmEdicao.value = null;
}

function definirCartaoPadrao(cartao) {
    router.patch(cartao.url_definir_padrao, {}, {
        preserveScroll: true,
    });
}

function alternarArquivada(cartao) {
    router.patch(cartao.url_arquivar, {
        arquivada: cartao.arquivada === 'S' ? 'N' : 'S',
    }, {
        preserveScroll: true,
    });
}

function pedirExclusao(cartao) {
    if (!cartao.pode_excluir) {
        return;
    }

    cartaoParaExcluir.value = cartao;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    cartaoParaExcluir.value = null;
}

function confirmarExclusao() {
    if (!cartaoParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(cartaoParaExcluir.value.url_excluir, {
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            cartaoParaExcluir.value = null;
        },
    });
}

const mensagemExclusao = computed(() => {
    const nome = cartaoParaExcluir.value?.nome;
    if (!nome) {
        return 'Deseja excluir este cartão de crédito? Esta ação não pode ser desfeita.';
    }

    return `Deseja excluir o cartão "${nome}"? Esta ação não pode ser desfeita.`;
});
</script>

<template>
    <Head title="Cartões de crédito" />

    <AutenticadoLayout titulo="Cartões de crédito">
        <div class="space-y-8 p-6 lg:p-8">
            <section class="space-y-4">
                <CabecalhoSecao
                    titulo="Meus Cartões de Crédito"
                    subtitulo="Clique em um cartão para inspecionar faturas ou use o botão de edição."
                >
                    <template #acoes>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-ng-brand transition hover:text-ng-brand-hover"
                            @click="abrirCriar"
                        >
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            Adicionar Cartão
                        </button>
                    </template>
                </CabecalhoSecao>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <CartaoCreditoResumo
                        v-for="cartao in cartoesAtivos"
                        :key="cartao.id"
                        :nome="cartao.nome"
                        :ultimos-digitos="cartao.ultimos_digitos ?? ''"
                        :nome-titular="cartao.nome_titular ?? ''"
                        :bandeira="cartao.bandeira"
                        :bandeira-rotulo="cartao.bandeira_rotulo"
                        :logo="cartao.logo"
                        :limite-usado="cartao.limite_usado"
                        :limite-total="cartao.limite_total"
                        :limite-disponivel="cartao.limite_disponivel"
                        :percentual-utilizado="cartao.percentual_utilizado"
                        :dia-fechamento="cartao.dia_fechamento_formatado"
                        :dia-vencimento="cartao.dia_vencimento_formatado"
                        :padrao="cartao.padrao === 'S'"
                        :selecionado="cartaoEmFoco?.id === cartao.id"
                        :pode-excluir="cartao.pode_excluir"
                        @selecionar="selecionarCartaoEmFoco(cartao)"
                        @editar="abrirEditar(cartao)"
                        @arquivar="alternarArquivada(cartao)"
                        @excluir="pedirExclusao(cartao)"
                        @definir-padrao="definirCartaoPadrao(cartao)"
                    />

                    <CardAdicionarPlaceholder
                        titulo="Novo Cartão"
                        descricao="Adicione limites e datas"
                        @click="abrirCriar"
                    />
                </div>
            </section>

            <section v-if="cartaoEmFoco" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-4">
                    <CabecalhoSecao titulo="Cartão em Foco">
                        <template #acoes>
                            <button
                                type="button"
                                class="text-xs font-semibold text-ng-brand transition hover:text-ng-brand-hover"
                                @click="abrirEditar(cartaoEmFoco)"
                            >
                                Editar Dados
                            </button>
                        </template>
                    </CabecalhoSecao>

                    <CartaoCreditoVisual
                        :nome="cartaoEmFoco.nome"
                        :nome-titular="cartaoEmFoco.nome_titular ?? ''"
                        :ultimos-digitos="cartaoEmFoco.ultimos_digitos ?? ''"
                        :bandeira="cartaoEmFoco.bandeira"
                        :limite="cartaoEmFoco.limite_total"
                        :dia-fechamento="cartaoEmFoco.dia_fechamento_formatado"
                        :dia-vencimento="cartaoEmFoco.dia_vencimento_formatado"
                        :cor-acabamento="cartaoEmFoco.acabamento_cor"
                    />

                    <div class="ng-card p-5">
                        <ListaInformacoes :itens="itensInformacoesConta" />
                    </div>
                </div>

                <div class="space-y-4 lg:col-span-2">
                    <CabecalhoSecao
                        :titulo="tituloLancamentos"
                        subtitulo="Histórico de compras e débitos na fatura atual."
                    >
                        <template #acoes>
                            <a
                                v-if="!exibirTodosCartoes"
                                :href="cartaoEmFoco.url_faturas"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium text-ng-ink-muted transition hover:text-ng-ink"
                            >
                                Ver faturas
                            </a>
                            <button
                                type="button"
                                class="rounded-lg border border-ng-line-strong bg-ng-card-muted px-3 py-1.5 text-xs font-medium text-ng-ink-secondary transition hover:border-ng-brand hover:text-ng-ink"
                                @click="alternarExibicaoTodosCartoes"
                            >
                                {{ exibirTodosCartoes ? 'Somente cartão em foco' : 'Todos os cartões' }}
                            </button>
                        </template>
                    </CabecalhoSecao>

                    <ListaLancamentos
                        :itens="lancamentosExibidos"
                        mensagem-vazia="Nenhum lançamento em aberto para exibir."
                    />
                </div>
            </section>

            <section v-if="cartoesArquivados.length > 0" class="space-y-4">
                <CabecalhoSecao
                    titulo="Arquivados"
                    :subtitulo="`${cartoesArquivados.length} cartão(ões) fora de uso.`"
                >
                    <template #acoes>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-ng-ink-muted transition hover:text-ng-ink"
                            @click="arquivadosVisiveis = !arquivadosVisiveis"
                        >
                            {{ arquivadosVisiveis ? 'Ocultar' : 'Mostrar' }}
                            <span class="material-symbols-outlined text-[18px]">
                                {{ arquivadosVisiveis ? 'expand_less' : 'expand_more' }}
                            </span>
                        </button>
                    </template>
                </CabecalhoSecao>

                <div v-show="arquivadosVisiveis" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <CartaoCreditoResumo
                        v-for="cartao in cartoesArquivados"
                        :key="cartao.id"
                        :nome="cartao.nome"
                        :ultimos-digitos="cartao.ultimos_digitos ?? ''"
                        :nome-titular="cartao.nome_titular ?? ''"
                        :bandeira="cartao.bandeira"
                        :bandeira-rotulo="cartao.bandeira_rotulo"
                        :logo="cartao.logo"
                        :limite-usado="cartao.limite_usado"
                        :limite-total="cartao.limite_total"
                        :limite-disponivel="cartao.limite_disponivel"
                        :percentual-utilizado="cartao.percentual_utilizado"
                        :dia-fechamento="cartao.dia_fechamento_formatado"
                        :dia-vencimento="cartao.dia_vencimento_formatado"
                        :pode-excluir="cartao.pode_excluir"
                        arquivado
                        @selecionar="abrirEditar(cartao)"
                        @editar="abrirEditar(cartao)"
                        @arquivar="alternarArquivada(cartao)"
                        @excluir="pedirExclusao(cartao)"
                    />
                </div>
            </section>
        </div>

        <FormularioModal
            :aberto="modalAberto"
            :bandeiras="bandeiras"
            :acabamentos="acabamentos"
            :bancos-sugeridos="bancosSugeridos"
            :cartao="cartaoEmEdicao"
            :nome-titular-padrao="nomeUsuarioLogado"
            @fechar="fecharModal"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir cartão"
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
