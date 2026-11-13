<script setup>
import { computed, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import TileMetrica from '@/Components/Ui/TileMetrica.vue';
import ListaCenariosSimulacao from '@/Components/Orcamento/ListaCenariosSimulacao.vue';

const LIMITE_CENARIOS_INICIAL = 6;
const LIMITE_CENARIOS_EXPANDIDO = 12;

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    cotacao: {
        type: Object,
        default: null,
    },
    contasBancarias: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['fechar', 'aprovar']);

const mostrarCenariosExtras = ref(false);

watch(
    () => props.aberto,
    (aberto) => {
        if (aberto) {
            mostrarCenariosExtras.value = false;
        }
    },
);

const ehPagamentoComCartao = computed(() => props.cotacao?.forma_pagamento === 'cartao_credito');
const ehAVista = computed(() => props.cotacao?.modalidade_pagamento === 'a_vista');

const cenariosVisiveis = computed(() => {
    const cenarios = props.cotacao?.cenarios ?? [];

    if (ehAVista.value) {
        return cenarios.slice(0, 1);
    }

    return cenarios.slice(0, mostrarCenariosExtras.value ? LIMITE_CENARIOS_EXPANDIDO : LIMITE_CENARIOS_INICIAL);
});

const possuiCenariosExtras = computed(
    () => !ehAVista.value && (props.cotacao?.cenarios?.length ?? 0) > LIMITE_CENARIOS_INICIAL,
);

const saldoDaContaSelecionada = computed(() => {
    if (!props.cotacao || ehPagamentoComCartao.value) {
        return null;
    }

    if (props.cotacao.saldo_conta_selecionada) {
        return props.cotacao.saldo_conta_selecionada;
    }

    const conta = props.contasBancarias.find(
        (contaBancaria) => Number(contaBancaria.id) === Number(props.cotacao.id_conta_bancaria),
    );

    return conta?.saldo_disponivel ?? conta?.saldo_atual ?? null;
});

const metricasDaSimulacao = computed(() => {
    const cotacao = props.cotacao;

    if (!cotacao) {
        return [];
    }

    return [
        { rotulo: 'Modalidade', valor: cotacao.modalidade_pagamento_rotulo ?? '—', prefixoMoeda: '' },
        {
            rotulo: 'Forma',
            valor: ehPagamentoComCartao.value
                ? `Crédito ${cotacao.cartao_credito_nome ?? ''}`
                : `PIX ${cotacao.conta_bancaria_nome ?? ''}`,
            prefixoMoeda: '',
        },
        ehPagamentoComCartao.value
            ? { rotulo: 'Limite disponível', valor: cotacao.limite_disponivel_cartao ?? '0,00' }
            : { rotulo: 'Saldo disponível', valor: saldoDaContaSelecionada.value ?? '0,00' },
        { rotulo: 'Receita prevista/mês', valor: cotacao.receita_prevista_mensal ?? '0,00' },
    ];
});

function alternarCenariosExtras() {
    mostrarCenariosExtras.value = !mostrarCenariosExtras.value;
}

function fecharDetalhe() {
    emit('fechar');
}

function aprovarComCenario(cenario = null) {
    emit('aprovar', cenario);
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="2xl"
        icone="analytics"
        titulo="Detalhe da simulação"
        :subtitulo="cotacao ? `${cotacao.descricao} · R$ ${cotacao.valor}` : ''"
        texto-cancelar="Fechar"
        texto-confirmar="Aprovar"
        @fechar="fecharDetalhe"
        @confirmar="aprovarComCenario()"
    >
        <div v-if="cotacao" class="space-y-5">
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <TileMetrica
                    v-for="metrica in metricasDaSimulacao"
                    :key="metrica.rotulo"
                    :rotulo="metrica.rotulo"
                    :valor="metrica.valor"
                    :prefixo-moeda="metrica.prefixoMoeda ?? 'R$'"
                />
            </div>

            <section>
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-ng-ink-subtle">
                    Cenários simulados
                </p>
                <ListaCenariosSimulacao :cenarios="cenariosVisiveis" @selecionar="aprovarComCenario" />

                <button
                    v-if="possuiCenariosExtras"
                    type="button"
                    class="mt-3 text-xs font-medium text-ng-brand hover:underline"
                    @click="alternarCenariosExtras"
                >
                    {{ mostrarCenariosExtras ? 'Mostrar menos' : 'Ver mais' }}
                </button>
            </section>
        </div>
    </Modal>
</template>
