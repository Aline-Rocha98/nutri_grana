<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import AvisoInformativo from '@/Components/Formulario/AvisoInformativo.vue';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';
import LinhaCampoNumero from '@/Components/Formulario/LinhaCampoNumero.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';

const FORMA_CONTA_BANCARIA = 'conta_bancaria';
const FORMA_CARTAO_CREDITO = 'cartao_credito';
const MODALIDADE_A_VISTA = 'a_vista';
const MODALIDADE_PARCELADO = 'parcelado';
const MINIMO_PARCELAS = 2;
const MAXIMO_PARCELAS = 48;

const ICONE_POR_FORMA = {
    [FORMA_CONTA_BANCARIA]: 'account_balance',
    [FORMA_CARTAO_CREDITO]: 'credit_card',
};

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    cotacao: {
        type: Object,
        default: null,
    },
    cenario: {
        type: Object,
        default: null,
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

const emit = defineEmits(['fechar', 'aprovada']);

const formulario = useForm({
    modalidade_pagamento: MODALIDADE_A_VISTA,
    total_parcelas: 1,
    forma_pagamento: FORMA_CONTA_BANCARIA,
    id_conta_bancaria: null,
    id_cartao_credito: null,
});

const pagamentoComConta = computed(() => formulario.forma_pagamento === FORMA_CONTA_BANCARIA);
const pagamentoParcelado = computed(() => formulario.modalidade_pagamento === MODALIDADE_PARCELADO);

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
    valor: conta.id,
    rotulo: `${conta.nome} — Disponível: R$ ${conta.saldo_disponivel ?? conta.saldo_atual}`,
})));

const opcoesCartoes = computed(() => props.cartoesCredito.map((cartao) => ({
    valor: cartao.id,
    rotulo: `${cartao.nome} — Limite disp.: R$ ${cartao.limite_disponivel}`,
})));

const resumoDaCotacao = computed(() => {
    if (!props.cotacao) {
        return '';
    }

    return props.cenario
        ? `${props.cotacao.descricao} · ${props.cenario.rotulo}`
        : props.cotacao.descricao;
});

function contaPadraoParaAprovacao() {
    return props.contasBancarias.find((conta) => conta.padrao_desconto === 'S')?.id
        ?? props.contasBancarias[0]?.id
        ?? null;
}

function cartaoPadraoParaAprovacao() {
    return props.cartoesCredito.find((cartao) => cartao.padrao === 'S')?.id
        ?? props.cartoesCredito[0]?.id
        ?? null;
}

function cenarioEscolhidoParaAprovacao() {
    const cenarios = props.cotacao?.cenarios ?? [];

    return props.cenario
        ?? cenarios.find((cenario) => cenario.recomendado)
        ?? cenarios[0]
        ?? null;
}

function preencherFormularioDeAprovacao() {
    const cenarioEscolhido = cenarioEscolhidoParaAprovacao();
    const ehParcelado = cenarioEscolhido
        ? cenarioEscolhido.modalidade_pagamento === MODALIDADE_PARCELADO
        : props.cotacao?.modalidade_pagamento === MODALIDADE_PARCELADO;

    formulario.clearErrors();
    formulario.modalidade_pagamento = ehParcelado ? MODALIDADE_PARCELADO : MODALIDADE_A_VISTA;
    formulario.total_parcelas = ehParcelado ? (cenarioEscolhido?.total_parcelas ?? MINIMO_PARCELAS) : 1;
    formulario.forma_pagamento = props.cotacao?.forma_pagamento ?? FORMA_CONTA_BANCARIA;
    formulario.id_conta_bancaria = props.cotacao?.id_conta_bancaria ?? contaPadraoParaAprovacao();
    formulario.id_cartao_credito = props.cotacao?.id_cartao_credito ?? cartaoPadraoParaAprovacao();
}

watch(
    () => [props.aberto, props.cotacao, props.cenario],
    () => {
        if (props.aberto) {
            preencherFormularioDeAprovacao();
        }
    },
);

watch(
    () => formulario.modalidade_pagamento,
    (modalidade) => {
        if (modalidade === MODALIDADE_PARCELADO && formulario.total_parcelas < MINIMO_PARCELAS) {
            formulario.total_parcelas = MINIMO_PARCELAS;
        }

        if (modalidade === MODALIDADE_A_VISTA) {
            formulario.total_parcelas = 1;
        }
    },
);

function fecharAprovacao() {
    emit('fechar');
}

function confirmarAprovacaoCotacao() {
    if (!props.cotacao?.url_aprovar) {
        return;
    }

    formulario.post(props.cotacao.url_aprovar, {
        preserveScroll: true,
        onSuccess: () => emit('aprovada'),
    });
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="md"
        icone="task_alt"
        titulo="Aprovar cotação"
        subtitulo="Escolha como o compromisso será pago."
        texto-confirmar="Confirmar aprovação"
        :processando="formulario.processing"
        @fechar="fecharAprovacao"
        @confirmar="confirmarAprovacaoCotacao"
    >
        <div class="space-y-5">
            <AvisoInformativo
                v-if="cotacao"
                :rotulo="resumoDaCotacao"
                :valor="`R$ ${cotacao.valor}`"
            />

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
                id="aprovacao-conta"
                v-model="formulario.id_conta_bancaria"
                rotulo="Conta bancária"
                :opcoes="opcoesContas"
                placeholder="Selecione a conta"
                :erro="formulario.errors.id_conta_bancaria"
            />
            <CampoSelecao
                v-else
                id="aprovacao-cartao"
                v-model="formulario.id_cartao_credito"
                rotulo="Cartão de crédito"
                :opcoes="opcoesCartoes"
                placeholder="Selecione o cartão"
                dica="O limite é verificado na aprovação"
                :erro="formulario.errors.id_cartao_credito"
            />

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

            <LinhaCampoNumero
                v-if="pagamentoParcelado"
                id="aprovacao-parcelas"
                v-model="formulario.total_parcelas"
                rotulo="Número de parcelas"
                :dica="`Entre ${MINIMO_PARCELAS} e ${MAXIMO_PARCELAS} parcelas`"
                :minimo="MINIMO_PARCELAS"
                :maximo="MAXIMO_PARCELAS"
                :erro="formulario.errors.total_parcelas"
            />
        </div>
    </Modal>
</template>
