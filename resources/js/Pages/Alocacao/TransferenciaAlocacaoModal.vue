<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import AvisoInformativo from '@/Components/Formulario/AvisoInformativo.vue';
import CampoMoeda from '@/Components/Formulario/CampoMoeda.vue';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import { formatarNumeroParaMoeda, moedaParaNumero } from '@/Helpers/mascaraMoeda';
import {
    buscarSaldoDaAlocacaoNaConta,
    montarAtalhoValorTotal,
    montarOpcoesContasComSaldo,
    obterDataDeHojeIso,
} from '@/Helpers/alocacao';

const ERROR_BAG_TRANSFERIR = 'transferirAlocacao';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    alocacao: {
        type: Object,
        default: null,
    },
    alocacoes: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['fechar']);

const formulario = useForm({
    id_conta_bancaria: null,
    id_alocacao_destino: null,
    valor: '0,00',
    data_transferencia: '',
    observacao: '',
});

const opcoesContasComSaldo = computed(() => montarOpcoesContasComSaldo(props.alocacao));

const opcoesAlocacoesDestino = computed(() => props.alocacoes
    .filter((alocacaoDestino) => alocacaoDestino.id !== props.alocacao?.id)
    .map((alocacaoDestino) => ({ valor: alocacaoDestino.id, rotulo: alocacaoDestino.nome })));

const saldoNaContaSelecionada = computed(() => buscarSaldoDaAlocacaoNaConta(props.alocacao, formulario.id_conta_bancaria));
const saldoAposTransferencia = computed(() => Math.max(0, saldoNaContaSelecionada.value - moedaParaNumero(formulario.valor)));
const atalhosValor = computed(() => montarAtalhoValorTotal('Transferir tudo', saldoNaContaSelecionada.value));

const desabilitarConfirmar = computed(() => !formulario.id_conta_bancaria
    || !formulario.id_alocacao_destino
    || moedaParaNumero(formulario.valor) <= 0);

function reiniciarFormularioTransferencia() {
    formulario.clearErrors();
    formulario.id_conta_bancaria = opcoesContasComSaldo.value[0]?.valor ?? null;
    formulario.id_alocacao_destino = null;
    formulario.valor = '0,00';
    formulario.data_transferencia = obterDataDeHojeIso();
    formulario.observacao = '';
}

watch(
    () => [props.aberto, props.alocacao],
    () => {
        if (props.aberto) {
            reiniciarFormularioTransferencia();
        }
    },
);

function fecharTransferencia() {
    emit('fechar');
}

function confirmarTransferenciaEntreAlocacoes() {
    if (!props.alocacao?.url_transferir || desabilitarConfirmar.value) {
        return;
    }

    formulario.post(props.alocacao.url_transferir, {
        errorBag: ERROR_BAG_TRANSFERIR,
        preserveScroll: true,
        onSuccess: () => fecharTransferencia(),
    });
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        icone="swap_horiz"
        :titulo="alocacao ? `Transferir de “${alocacao.nome}”` : 'Transferir entre alocações'"
        subtitulo="O valor continua na mesma conta, só muda de alocação."
        texto-confirmar="Confirmar transferência"
        :processando="formulario.processing"
        :desabilitar-confirmar="desabilitarConfirmar"
        @fechar="fecharTransferencia"
        @confirmar="confirmarTransferenciaEntreAlocacoes"
    >
        <div class="space-y-5">
            <AvisoInformativo
                variante="sky"
                rotulo="Saldo nesta conta:"
                :valor="`R$ ${formatarNumeroParaMoeda(saldoNaContaSelecionada)}`"
                :complemento="`após a transferência: R$ ${formatarNumeroParaMoeda(saldoAposTransferencia)}`"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <CampoSelecao
                    id="transferencia-conta"
                    v-model="formulario.id_conta_bancaria"
                    rotulo="Conta onde está guardado"
                    obrigatorio
                    placeholder="Selecione a conta"
                    :opcoes="opcoesContasComSaldo"
                    :erro="formulario.errors.id_conta_bancaria"
                />
                <CampoSelecao
                    id="transferencia-alocacao-destino"
                    v-model="formulario.id_alocacao_destino"
                    rotulo="Alocação de destino"
                    obrigatorio
                    placeholder="Selecione a alocação"
                    :opcoes="opcoesAlocacoesDestino"
                    :erro="formulario.errors.id_alocacao_destino"
                />
            </div>

            <CampoMoeda
                id="transferencia-valor"
                v-model="formulario.valor"
                rotulo="Valor"
                obrigatorio
                :atalhos="atalhosValor"
                :erro="formulario.errors.valor"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-[10rem_1fr]">
                <CampoTexto
                    id="transferencia-data"
                    v-model="formulario.data_transferencia"
                    tipo="date"
                    rotulo="Data"
                    obrigatorio
                    :erro="formulario.errors.data_transferencia"
                />
                <CampoTexto
                    id="transferencia-observacao"
                    v-model="formulario.observacao"
                    rotulo="Observação"
                    placeholder="Opcional"
                    maxlength="255"
                    :erro="formulario.errors.observacao"
                />
            </div>
        </div>
    </Modal>
</template>
