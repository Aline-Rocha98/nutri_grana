<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import CabecalhoSecao from '@/Components/Ui/CabecalhoSecao.vue';
import CardAdicionarPlaceholder from '@/Components/Ui/CardAdicionarPlaceholder.vue';
import ListaInformacoes from '@/Components/Ui/ListaInformacoes.vue';
import ListaLancamentos from '@/Components/Ui/ListaLancamentos.vue';
import CardSaldoGuardado from '@/Components/ContaBancaria/CardSaldoGuardado.vue';
import ContaBancariaResumo from '@/Components/ContaBancaria/ContaBancariaResumo.vue';
import ContaBancariaVisual from '@/Components/ContaBancaria/ContaBancariaVisual.vue';
import FormularioModal from '@/Pages/ContaBancaria/FormularioModal.vue';
import { varianteSituacaoLancamento } from '@/Helpers/badge';
import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';

const props = defineProps({
    contasBancarias: {
        type: Array,
        default: () => [],
    },
    movimentacoesRecentesPorConta: {
        type: Object,
        default: () => ({}),
    },
    movimentacoesSaldoGuardadoPorConta: {
        type: Object,
        default: () => ({}),
    },
    tipos: {
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
const contaEmEdicao = ref(null);
const modalExclusaoAberto = ref(false);
const contaParaExcluir = ref(null);
const excluindo = ref(false);
const idContaEmFoco = ref(null);
const exibirTodasContas = ref(false);
const arquivadasVisiveis = ref(false);

watch(
    () => pagina.props.errors,
    (erros) => {
        if (erros && Object.keys(erros).length > 0) {
            modalAberto.value = true;
        }
    },
    { deep: true, immediate: true },
);

const contasAtivas = computed(() => props.contasBancarias.filter((conta) => conta.arquivada !== 'S'));
const contasArquivadas = computed(() => props.contasBancarias.filter((conta) => conta.arquivada === 'S'));

const saldoGeralFormatado = computed(() =>
    formatarNumeroParaMoeda(
        contasAtivas.value.reduce((total, conta) => total + Number(conta.saldo_atual_numero ?? 0), 0),
    ),
);

const contaEmFoco = computed(() =>
    contasAtivas.value.find((conta) => conta.id === idContaEmFoco.value)
    ?? contasAtivas.value.find((conta) => conta.padrao_desconto === 'S')
    ?? contasAtivas.value[0]
    ?? null,
);

const nomesContasPorId = computed(() =>
    Object.fromEntries(props.contasBancarias.map((conta) => [conta.id, conta.nome])),
);

const movimentacoesDaVisao = computed(() => {
    if (exibirTodasContas.value) {
        return contasAtivas.value
            .flatMap((conta) => props.movimentacoesRecentesPorConta[conta.id] ?? [])
            .sort((primeira, segunda) => (segunda.data_vencimento ?? '').localeCompare(primeira.data_vencimento ?? ''));
    }

    if (!contaEmFoco.value) {
        return [];
    }

    return props.movimentacoesRecentesPorConta[contaEmFoco.value.id] ?? [];
});

const movimentacoesExibidas = computed(() =>
    movimentacoesDaVisao.value.map((movimentacao) => ({
        chave: movimentacao.id,
        descricao: movimentacao.descricao,
        subtitulo: [
            movimentacao.categoria_nome,
            exibirTodasContas.value ? nomesContasPorId.value[movimentacao.id_conta_bancaria] : null,
            movimentacao.data_vencimento_formatada,
        ].filter(Boolean).join(' · '),
        valor: movimentacao.valor,
        icone: movimentacao.categoria_icone ?? undefined,
        cor: movimentacao.categoria_cor ?? undefined,
        etiqueta: movimentacao.eh_parcelado ? `${movimentacao.parcela_atual}/${movimentacao.total_parcelas}` : '',
        badgeTexto: movimentacao.situacao_rotulo ?? '',
        badgeVariante: varianteSituacaoLancamento(movimentacao.situacao),
        positivo: movimentacao.tipo === 'receita',
        negativo: movimentacao.tipo === 'despesa',
    })),
);

const tituloMovimentacoes = computed(() => {
    if (exibirTodasContas.value || !contaEmFoco.value) {
        return 'Movimentações Recentes (Todas as contas)';
    }

    return `Movimentações Recentes (${contaEmFoco.value.nome})`;
});

const itensInformacoesConta = computed(() => {
    const conta = contaEmFoco.value;

    if (!conta) {
        return [];
    }

    return [
        {
            chave: 'status',
            rotulo: 'Status da Conta',
            valor: 'Ativa',
            indicador: 'bg-ng-brand',
            classeValor: 'text-xs text-ng-brand',
            separador: true,
        },
        {
            chave: 'tipo',
            rotulo: 'Tipo da conta',
            valor: conta.tipo_rotulo,
        },
        {
            chave: 'saldo-inicial',
            rotulo: 'Saldo inicial',
            valor: `R$ ${conta.saldo_inicial}`,
            classeValor: 'font-mono',
        },
        {
            chave: 'saldo-atual',
            rotulo: 'Saldo atual',
            valor: `R$ ${conta.saldo_atual}`,
            classeValor: conta.saldo_atual_numero < 0 ? 'font-mono text-red-500' : 'font-mono text-ng-brand',
        },
        {
            chave: 'saldo-guardado',
            rotulo: 'Saldo guardado',
            valor: `R$ ${conta.saldo_guardado}`,
            classeValor: 'font-mono text-sky-500',
        },
        {
            chave: 'saldo-disponivel',
            rotulo: 'Saldo disponível',
            valor: `R$ ${conta.saldo_disponivel}`,
            classeValor: conta.saldo_disponivel_numero < 0 ? 'font-mono text-red-500' : 'font-mono text-ng-brand',
            separador: true,
        },
        {
            chave: 'padrao-desconto',
            rotulo: 'Padrão para desconto',
            valor: conta.padrao_desconto === 'S' ? 'Sim' : 'Não',
        },
        {
            chave: 'exibir-resumo',
            rotulo: 'Exibir no resumo',
            valor: conta.exibir_resumo === 'S' ? 'Sim' : 'Não',
        },
    ];
});

const movimentacoesSaldoGuardadoExibidas = computed(() => {
    if (!contaEmFoco.value) {
        return [];
    }

    return (props.movimentacoesSaldoGuardadoPorConta[contaEmFoco.value.id] ?? []).map((movimentacao) => ({
        chave: movimentacao.id,
        descricao: movimentacao.descricao,
        subtitulo: [
            movimentacao.tipo_rotulo,
            movimentacao.conta_bancaria_origem_nome ? `de ${movimentacao.conta_bancaria_origem_nome}` : null,
            movimentacao.conta_bancaria_resgate_nome ? `para ${movimentacao.conta_bancaria_resgate_nome}` : null,
            movimentacao.data_movimentacao_formatada,
        ].filter(Boolean).join(' · '),
        valor: movimentacao.valor,
        icone: iconeMovimentacaoSaldoGuardado(movimentacao.tipo),
        cor: '#0ea5e9',
        positivo: movimentacao.eh_separacao,
        negativo: movimentacao.tipo === 'resgate' && movimentacao.id_conta_bancaria === contaEmFoco.value.id,
    }));
});

function iconeMovimentacaoSaldoGuardado(tipo) {
    if (tipo === 'separacao') {
        return 'savings';
    }

    return tipo === 'transferencia' ? 'swap_horiz' : 'south_west';
}

function abrirAlocacoes() {
    if (contaEmFoco.value?.url_alocacoes) {
        router.visit(contaEmFoco.value.url_alocacoes);
    }
}

function selecionarContaEmFoco(conta) {
    idContaEmFoco.value = conta.id;
    exibirTodasContas.value = false;
}

function alternarExibicaoTodasContas() {
    exibirTodasContas.value = !exibirTodasContas.value;
}

function abrirCriar() {
    contaEmEdicao.value = null;
    modalAberto.value = true;
}

function abrirEditar(conta) {
    contaEmEdicao.value = conta;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    contaEmEdicao.value = null;
}

function definirContaPadraoDesconto(conta) {
    router.patch(conta.url_definir_padrao_desconto, {}, {
        preserveScroll: true,
    });
}

function alternarArquivada(conta) {
    router.patch(conta.url_arquivar, {
        arquivada: conta.arquivada === 'S' ? 'N' : 'S',
    }, {
        preserveScroll: true,
    });
}

function podeExcluirConta(conta) {
    return Number(conta.total_lancamentos ?? 0) === 0;
}

function pedirExclusao(conta) {
    if (!podeExcluirConta(conta)) {
        return;
    }

    contaParaExcluir.value = conta;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    contaParaExcluir.value = null;
}

function confirmarExclusao() {
    if (!contaParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(contaParaExcluir.value.url_excluir, {
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            contaParaExcluir.value = null;
        },
    });
}

const mensagemExclusao = computed(() => {
    const nome = contaParaExcluir.value?.nome;
    if (!nome) {
        return 'Deseja excluir esta conta bancária? Esta ação não pode ser desfeita.';
    }

    return `Deseja excluir a conta "${nome}"? Esta ação não pode ser desfeita.`;
});
</script>

<template>
    <Head title="Contas bancárias" />

    <AutenticadoLayout titulo="Contas bancárias">
        <div class="space-y-8 p-6 lg:p-8">
            <section class="space-y-4">
                <CabecalhoSecao
                    titulo="Minhas Contas Bancárias"
                    :subtitulo="`Saldo geral das contas ativas: R$ ${saldoGeralFormatado}`"
                >
                    <template #acoes>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-ng-brand transition hover:text-ng-brand-hover"
                            @click="abrirCriar"
                        >
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            Adicionar Conta
                        </button>
                    </template>
                </CabecalhoSecao>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <ContaBancariaResumo
                        v-for="conta in contasAtivas"
                        :key="conta.id"
                        :nome="conta.nome"
                        :tipo="conta.tipo"
                        :tipo-rotulo="conta.tipo_rotulo"
                        :logo="conta.logo"
                        :saldo-atual="conta.saldo_atual"
                        :saldo-atual-numero="conta.saldo_atual_numero"
                        :saldo-inicial="conta.saldo_inicial"
                        :saldo-guardado="conta.saldo_guardado"
                        :saldo-guardado-numero="conta.saldo_guardado_numero"
                        :exibir-resumo="conta.exibir_resumo === 'S'"
                        :total-lancamentos="conta.total_lancamentos"
                        :padrao="conta.padrao_desconto === 'S'"
                        :selecionado="contaEmFoco?.id === conta.id"
                        :pode-excluir="podeExcluirConta(conta)"
                        @selecionar="selecionarContaEmFoco(conta)"
                        @editar="abrirEditar(conta)"
                        @arquivar="alternarArquivada(conta)"
                        @excluir="pedirExclusao(conta)"
                        @definir-padrao="definirContaPadraoDesconto(conta)"
                    />

                    <CardAdicionarPlaceholder
                        titulo="Nova Conta"
                        descricao="Adicione o saldo inicial e o tipo"
                        icone="account_balance"
                        @click="abrirCriar"
                    />
                </div>
            </section>

            <section v-if="contaEmFoco" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-4">
                    <CabecalhoSecao titulo="Conta em Foco">
                        <template #acoes>
                            <button
                                type="button"
                                class="text-xs font-semibold text-ng-brand transition hover:text-ng-brand-hover"
                                @click="abrirEditar(contaEmFoco)"
                            >
                                Editar Dados
                            </button>
                        </template>
                    </CabecalhoSecao>

                    <ContaBancariaVisual
                        :nome="contaEmFoco.nome"
                        :tipo="contaEmFoco.tipo"
                        :tipo-rotulo="contaEmFoco.tipo_rotulo"
                        :logo="contaEmFoco.logo"
                        :saldo-atual="contaEmFoco.saldo_atual"
                        :saldo-atual-numero="contaEmFoco.saldo_atual_numero"
                        :saldo-inicial="contaEmFoco.saldo_inicial"
                        :cor-acabamento="contaEmFoco.acabamento_cor"
                    />

                    <CardSaldoGuardado
                        :saldo-guardado="contaEmFoco.saldo_guardado"
                        :saldo-guardado-numero="contaEmFoco.saldo_guardado_numero"
                        :saldo-disponivel="contaEmFoco.saldo_disponivel"
                        :saldo-disponivel-numero="contaEmFoco.saldo_disponivel_numero"
                        @acao="abrirAlocacoes"
                    />

                    <div class="ng-card p-5">
                        <ListaInformacoes :itens="itensInformacoesConta" />
                    </div>
                </div>

                <div class="space-y-4 lg:col-span-2">
                    <CabecalhoSecao
                        :titulo="tituloMovimentacoes"
                        subtitulo="Receitas e despesas mais recentes vinculadas à conta."
                    >
                        <template #acoes>
                            <button
                                type="button"
                                class="rounded-lg border border-ng-line-strong bg-ng-card-muted px-3 py-1.5 text-xs font-medium text-ng-ink-secondary transition hover:border-ng-brand hover:text-ng-ink"
                                @click="alternarExibicaoTodasContas"
                            >
                                {{ exibirTodasContas ? 'Somente conta em foco' : 'Todas as contas' }}
                            </button>
                        </template>
                    </CabecalhoSecao>

                    <ListaLancamentos
                        :itens="movimentacoesExibidas"
                        mensagem-vazia="Nenhuma movimentação recente para exibir."
                    />

                    <CabecalhoSecao
                        :titulo="`Saldo guardado (${contaEmFoco.nome})`"
                        subtitulo="Valores separados pelo plano financeiro, transferências entre alocações e resgates."
                    />

                    <ListaLancamentos
                        :itens="movimentacoesSaldoGuardadoExibidas"
                        mensagem-vazia="Nenhum valor guardado nesta conta ainda. Aplique um plano financeiro nas suas rendas."
                    />
                </div>
            </section>

            <section v-if="contasArquivadas.length > 0" class="space-y-4">
                <CabecalhoSecao
                    titulo="Arquivadas"
                    :subtitulo="`${contasArquivadas.length} conta(s) fora de uso.`"
                >
                    <template #acoes>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-ng-ink-muted transition hover:text-ng-ink"
                            @click="arquivadasVisiveis = !arquivadasVisiveis"
                        >
                            {{ arquivadasVisiveis ? 'Ocultar' : 'Mostrar' }}
                            <span class="material-symbols-outlined text-[18px]">
                                {{ arquivadasVisiveis ? 'expand_less' : 'expand_more' }}
                            </span>
                        </button>
                    </template>
                </CabecalhoSecao>

                <div v-show="arquivadasVisiveis" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <ContaBancariaResumo
                        v-for="conta in contasArquivadas"
                        :key="conta.id"
                        :nome="conta.nome"
                        :tipo="conta.tipo"
                        :tipo-rotulo="conta.tipo_rotulo"
                        :logo="conta.logo"
                        :saldo-atual="conta.saldo_atual"
                        :saldo-atual-numero="conta.saldo_atual_numero"
                        :saldo-inicial="conta.saldo_inicial"
                        :exibir-resumo="conta.exibir_resumo === 'S'"
                        :total-lancamentos="conta.total_lancamentos"
                        :pode-excluir="podeExcluirConta(conta)"
                        arquivado
                        @selecionar="abrirEditar(conta)"
                        @editar="abrirEditar(conta)"
                        @arquivar="alternarArquivada(conta)"
                        @excluir="pedirExclusao(conta)"
                    />
                </div>
            </section>
        </div>

        <FormularioModal
            :aberto="modalAberto"
            :tipos="tipos"
            :acabamentos="acabamentos"
            :bancos-sugeridos="bancosSugeridos"
            :conta="contaEmEdicao"
            @fechar="fecharModal"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir conta"
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
