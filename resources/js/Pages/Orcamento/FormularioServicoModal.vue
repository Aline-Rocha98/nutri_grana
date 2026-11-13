<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CampoAreaTexto from '@/Components/Formulario/CampoAreaTexto.vue';
import CampoMoeda from '@/Components/Formulario/CampoMoeda.vue';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import CotacaoVisual from '@/Components/Orcamento/CotacaoVisual.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';
import { formatarDataIsoParaBrasileira } from '@/Helpers/periodo';

const FORMA_CONTA_BANCARIA = 'conta_bancaria';
const FORMA_CARTAO_CREDITO = 'cartao_credito';
const MODALIDADE_A_VISTA = 'a_vista';

const ICONE_POR_FORMA = {
    [FORMA_CONTA_BANCARIA]: 'account_balance',
    [FORMA_CARTAO_CREDITO]: 'credit_card',
};

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    orcamento: {
        type: Object,
        default: null,
    },
    categorias: {
        type: Array,
        default: () => [],
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

const emit = defineEmits(['fechar']);

const pagina = usePage();
const urlCriar = computed(() => pagina.props.rotas.orcamentosServicoCriar);
const editando = computed(() => Boolean(props.orcamento));
const urlAtualizar = ref('');

const formulario = useForm({
    descricao: '',
    fornecedor: '',
    valor: '0,00',
    data_orcamento: '',
    data_validade: '',
    observacao: '',
    id_categoria: null,
    id_subcategoria: null,
    modalidade_pagamento: MODALIDADE_A_VISTA,
    forma_pagamento: FORMA_CONTA_BANCARIA,
    id_conta_bancaria: null,
    id_cartao_credito: null,
});

const pagamentoComConta = computed(() => formulario.forma_pagamento === FORMA_CONTA_BANCARIA);

const opcoesFormaPagamento = computed(() => props.formasPagamento.map((opcao) => ({
    valor: opcao.valor,
    rotulo: opcao.valor === FORMA_CONTA_BANCARIA ? 'PIX / conta' : opcao.rotulo,
    icone: ICONE_POR_FORMA[opcao.valor],
})));

const opcoesModalidade = computed(() => props.modalidadesPagamento.map((opcao) => ({
    valor: opcao.valor,
    rotulo: opcao.rotulo,
})));

const opcoesContas = computed(() => props.contasBancarias.map((conta) => ({
    valor: Number(conta.id),
    rotulo: conta.nome,
})));

const opcoesCartoes = computed(() => props.cartoesCredito.map((cartao) => ({
    valor: Number(cartao.id),
    rotulo: cartao.nome,
})));

const contaSelecionada = computed(() => props.contasBancarias.find(
    (conta) => Number(conta.id) === Number(formulario.id_conta_bancaria),
) ?? null);

const cartaoSelecionado = computed(() => props.cartoesCredito.find(
    (cartao) => Number(cartao.id) === Number(formulario.id_cartao_credito),
) ?? null);

const dicaContaSelecionada = computed(() => (contaSelecionada.value
    ? `Saldo disponível nesta conta: R$ ${contaSelecionada.value.saldo_disponivel ?? contaSelecionada.value.saldo_atual}`
    : ''));

const dicaCartaoSelecionado = computed(() => {
    const limite = cartaoSelecionado.value
        ? `Limite disponível: R$ ${cartaoSelecionado.value.limite_disponivel}. `
        : '';

    return `${limite}O limite é usado apenas na validação, não entra como saldo em conta.`;
});

const categoriaSelecionada = computed(() => props.categorias.find(
    (categoria) => categoria.id === formulario.id_categoria,
) ?? null);

const opcoesCategorias = computed(() => props.categorias.map((categoria) => ({
    valor: categoria.id,
    rotulo: categoria.nome,
})));

const subcategoriasDisponiveis = computed(() => categoriaSelecionada.value?.subcategorias
    ?.filter((subcategoria) => subcategoria.arquivada !== 'S') ?? []);

const opcoesSubcategorias = computed(() => subcategoriasDisponiveis.value.map((subcategoria) => ({
    valor: subcategoria.id,
    rotulo: subcategoria.nome,
})));

const modalidadePreview = computed(() => props.modalidadesPagamento.find(
    (opcao) => opcao.valor === formulario.modalidade_pagamento,
)?.rotulo ?? '');

const origemPreview = computed(() => (pagamentoComConta.value
    ? contaSelecionada.value?.nome
    : cartaoSelecionado.value?.nome) ?? '');

const validadePreview = computed(() => (formulario.data_validade
    ? formatarDataIsoParaBrasileira(formulario.data_validade)
    : ''));

function normalizarIdSelecionado(valor) {
    if (valor === null || valor === undefined || valor === '') {
        return null;
    }

    const numero = Number(valor);

    return Number.isNaN(numero) ? null : numero;
}

function contaPadraoDaCotacao() {
    return props.contasBancarias.find((conta) => conta.padrao_desconto === 'S')?.id
        ?? props.contasBancarias[0]?.id
        ?? null;
}

function cartaoPadraoDaCotacao() {
    return props.cartoesCredito.find((cartao) => cartao.padrao === 'S')?.id
        ?? props.cartoesCredito[0]?.id
        ?? null;
}

function dataDeHojeIso() {
    return new Date().toISOString().slice(0, 10);
}

function preencherFormularioDaCotacao() {
    formulario.clearErrors();
    formulario.descricao = props.orcamento?.descricao ?? '';
    formulario.fornecedor = props.orcamento?.fornecedor ?? '';
    formulario.valor = props.orcamento?.valor ?? '0,00';
    formulario.data_orcamento = props.orcamento?.data_orcamento ?? dataDeHojeIso();
    formulario.data_validade = props.orcamento?.data_validade ?? '';
    formulario.observacao = props.orcamento?.observacao ?? '';
    formulario.id_categoria = props.orcamento?.id_categoria ?? null;
    formulario.id_subcategoria = props.orcamento?.id_subcategoria ?? null;
    formulario.modalidade_pagamento = props.orcamento?.modalidade_pagamento ?? MODALIDADE_A_VISTA;
    formulario.forma_pagamento = props.orcamento?.forma_pagamento ?? FORMA_CONTA_BANCARIA;
    formulario.id_conta_bancaria = normalizarIdSelecionado(props.orcamento?.id_conta_bancaria) ?? contaPadraoDaCotacao();
    formulario.id_cartao_credito = normalizarIdSelecionado(props.orcamento?.id_cartao_credito) ?? cartaoPadraoDaCotacao();
    urlAtualizar.value = props.orcamento?.url_atualizar ?? '';
}

watch(
    () => [props.aberto, props.orcamento],
    () => {
        if (props.aberto) {
            preencherFormularioDaCotacao();
        }
    },
);

watch(
    () => formulario.id_categoria,
    () => {
        if (!subcategoriasDisponiveis.value.some((subcategoria) => subcategoria.id === formulario.id_subcategoria)) {
            formulario.id_subcategoria = null;
        }
    },
);

watch(
    () => formulario.forma_pagamento,
    (forma) => {
        if (forma === FORMA_CONTA_BANCARIA && !formulario.id_conta_bancaria) {
            formulario.id_conta_bancaria = contaPadraoDaCotacao();
        }

        if (forma === FORMA_CARTAO_CREDITO && !formulario.id_cartao_credito) {
            formulario.id_cartao_credito = cartaoPadraoDaCotacao();
        }
    },
);

function fecharFormulario() {
    emit('fechar');
}

function salvarCotacaoServico() {
    const opcoes = {
        preserveScroll: true,
        onSuccess: () => fecharFormulario(),
    };

    if (editando.value) {
        formulario.put(urlAtualizar.value, opcoes);
        return;
    }

    formulario.post(urlCriar.value, opcoes);
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        icone="request_quote"
        :titulo="editando ? 'Editar cotação' : 'Nova cotação'"
        subtitulo="Registre a proposta e a forma de pagamento prevista para simular o impacto no fluxo."
        :texto-confirmar="editando ? 'Salvar alterações' : 'Salvar cotação'"
        :processando="formulario.processing"
        @fechar="fecharFormulario"
        @confirmar="salvarCotacaoServico"
    >
        <div class="space-y-5">
            <section>
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-ng-ink-subtle">
                    Visualização da cotação em tempo real
                </p>
                <CotacaoVisual
                    :descricao="formulario.descricao"
                    :fornecedor="formulario.fornecedor"
                    :categoria="categoriaSelecionada?.nome ?? ''"
                    :valor="formulario.valor"
                    :modalidade="modalidadePreview"
                    :validade="validadePreview"
                    :origem="origemPreview"
                />
            </section>

            <CampoTexto
                id="descricao-cotacao"
                v-model="formulario.descricao"
                rotulo="Descrição"
                obrigatorio
                placeholder="Ex.: Pintura do apartamento"
                :maxlength="255"
                :erro="formulario.errors.descricao"
            />

            <CampoTexto
                id="fornecedor-cotacao"
                v-model="formulario.fornecedor"
                rotulo="Fornecedor"
                placeholder="Nome da empresa ou profissional"
                :maxlength="255"
                :erro="formulario.errors.fornecedor"
            />

            <CampoMoeda
                id="valor-cotacao"
                v-model="formulario.valor"
                rotulo="Valor estimado"
                obrigatorio
                :erro="formulario.errors.valor"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <CampoTexto
                    id="data-cotacao"
                    v-model="formulario.data_orcamento"
                    tipo="date"
                    rotulo="Data da cotação"
                    :erro="formulario.errors.data_orcamento"
                />
                <CampoTexto
                    id="validade-cotacao"
                    v-model="formulario.data_validade"
                    tipo="date"
                    rotulo="Validade"
                    :erro="formulario.errors.data_validade"
                />
            </div>

            <section class="space-y-4 rounded-xl border border-ng-line bg-ng-card-muted p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-ng-ink-subtle">Pagamento previsto</p>

                <div>
                    <p class="ng-label mb-1.5">Modalidade</p>
                    <GrupoSegmentado
                        v-model="formulario.modalidade_pagamento"
                        :opcoes="opcoesModalidade"
                        rotulo="Modalidade de pagamento"
                        expandido
                    />
                    <p v-if="formulario.errors.modalidade_pagamento" class="mt-1.5 text-xs text-red-500">
                        {{ formulario.errors.modalidade_pagamento }}
                    </p>
                </div>

                <div>
                    <p class="ng-label mb-1.5">Forma de pagamento</p>
                    <GrupoSegmentado
                        v-model="formulario.forma_pagamento"
                        :opcoes="opcoesFormaPagamento"
                        rotulo="Forma de pagamento"
                        expandido
                    />
                    <p v-if="formulario.errors.forma_pagamento" class="mt-1.5 text-xs text-red-500">
                        {{ formulario.errors.forma_pagamento }}
                    </p>
                </div>

                <CampoSelecao
                    v-if="pagamentoComConta"
                    id="conta-cotacao"
                    v-model="formulario.id_conta_bancaria"
                    rotulo="Conta bancária"
                    :opcoes="opcoesContas"
                    placeholder="Selecione a conta"
                    :dica="dicaContaSelecionada"
                    :erro="formulario.errors.id_conta_bancaria"
                />
                <CampoSelecao
                    v-else
                    id="cartao-cotacao"
                    v-model="formulario.id_cartao_credito"
                    rotulo="Cartão de crédito"
                    :opcoes="opcoesCartoes"
                    placeholder="Selecione o cartão"
                    :dica="dicaCartaoSelecionado"
                    :erro="formulario.errors.id_cartao_credito"
                />
            </section>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <CampoSelecao
                    id="categoria-cotacao"
                    v-model="formulario.id_categoria"
                    rotulo="Categoria"
                    :opcoes="opcoesCategorias"
                    placeholder="Selecione..."
                    placeholder-selecionavel
                    :erro="formulario.errors.id_categoria"
                />
                <CampoSelecao
                    id="subcategoria-cotacao"
                    v-model="formulario.id_subcategoria"
                    rotulo="Subcategoria"
                    :opcoes="opcoesSubcategorias"
                    placeholder="Selecione..."
                    placeholder-selecionavel
                    :desabilitado="!formulario.id_categoria"
                    :erro="formulario.errors.id_subcategoria"
                />
            </div>

            <CampoAreaTexto
                id="observacao-cotacao"
                v-model="formulario.observacao"
                rotulo="Observação"
                placeholder="Condições, detalhes da proposta..."
                :linhas="3"
                :erro="formulario.errors.observacao"
            />
        </div>
    </Modal>
</template>
