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

const ERROR_BAG_RESGATAR = 'resgatarAlocacao';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    alocacao: {
        type: Object,
        default: null,
    },
    contasBancarias: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['fechar']);

const formulario = useForm({
    id_conta_bancaria_saida: null,
    id_conta_bancaria_destino: null,
    valor: '0,00',
    data_resgate: '',
    observacao: '',
});

const opcoesContasComSaldo = computed(() => montarOpcoesContasComSaldo(props.alocacao));

const opcoesContasDestino = computed(() => props.contasBancarias.map((conta) => ({
    valor: conta.id,
    rotulo: conta.nome,
})));

const saldoNaContaSaida = computed(() => buscarSaldoDaAlocacaoNaConta(props.alocacao, formulario.id_conta_bancaria_saida));
const saldoAposResgate = computed(() => Math.max(0, saldoNaContaSaida.value - moedaParaNumero(formulario.valor)));
const atalhosValor = computed(() => montarAtalhoValorTotal('Resgatar tudo', saldoNaContaSaida.value));

const resgateParaOutraConta = computed(() => formulario.id_conta_bancaria_saida
    && formulario.id_conta_bancaria_destino
    && String(formulario.id_conta_bancaria_saida) !== String(formulario.id_conta_bancaria_destino));

const subtituloResgate = computed(() => (resgateParaOutraConta.value
    ? 'O valor sai da conta de origem e entra como saldo disponível na conta de destino.'
    : 'O valor volta para o saldo disponível da própria conta.'));

const desabilitarConfirmar = computed(() => !formulario.id_conta_bancaria_saida
    || !formulario.id_conta_bancaria_destino
    || moedaParaNumero(formulario.valor) <= 0);

function contaDestinoPadrao(idContaSaida) {
    const contaSaidaAtiva = props.contasBancarias.some((conta) => String(conta.id) === String(idContaSaida));

    return contaSaidaAtiva ? idContaSaida : (props.contasBancarias[0]?.id ?? null);
}

function reiniciarFormularioResgate() {
    const idContaSaida = opcoesContasComSaldo.value[0]?.valor ?? null;

    formulario.clearErrors();
    formulario.id_conta_bancaria_saida = idContaSaida;
    formulario.id_conta_bancaria_destino = contaDestinoPadrao(idContaSaida);
    formulario.valor = '0,00';
    formulario.data_resgate = obterDataDeHojeIso();
    formulario.observacao = '';
}

watch(
    () => [props.aberto, props.alocacao],
    () => {
        if (props.aberto) {
            reiniciarFormularioResgate();
        }
    },
);

function fecharResgate() {
    emit('fechar');
}

function confirmarResgateDaAlocacao() {
    if (!props.alocacao?.url_resgatar || desabilitarConfirmar.value) {
        return;
    }

    formulario.post(props.alocacao.url_resgatar, {
        errorBag: ERROR_BAG_RESGATAR,
        preserveScroll: true,
        onSuccess: () => fecharResgate(),
    });
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        icone="south_west"
        :titulo="alocacao ? `Resgatar de “${alocacao.nome}”` : 'Resgatar alocação'"
        :subtitulo="subtituloResgate"
        texto-confirmar="Confirmar resgate"
        :processando="formulario.processing"
        :desabilitar-confirmar="desabilitarConfirmar"
        @fechar="fecharResgate"
        @confirmar="confirmarResgateDaAlocacao"
    >
        <div class="space-y-5">
            <AvisoInformativo
                variante="sky"
                rotulo="Saldo nesta conta:"
                :valor="`R$ ${formatarNumeroParaMoeda(saldoNaContaSaida)}`"
                :complemento="`após o resgate: R$ ${formatarNumeroParaMoeda(saldoAposResgate)}`"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <CampoSelecao
                    id="resgate-conta-saida"
                    v-model="formulario.id_conta_bancaria_saida"
                    rotulo="Sair da conta"
                    obrigatorio
                    placeholder="Selecione a conta"
                    :opcoes="opcoesContasComSaldo"
                    :erro="formulario.errors.id_conta_bancaria_saida"
                />
                <CampoSelecao
                    id="resgate-conta-destino"
                    v-model="formulario.id_conta_bancaria_destino"
                    rotulo="Enviar para a conta"
                    obrigatorio
                    placeholder="Selecione a conta"
                    :opcoes="opcoesContasDestino"
                    :erro="formulario.errors.id_conta_bancaria_destino"
                />
            </div>

            <CampoMoeda
                id="resgate-valor"
                v-model="formulario.valor"
                rotulo="Valor do resgate"
                obrigatorio
                :atalhos="atalhosValor"
                :erro="formulario.errors.valor"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-[10rem_1fr]">
                <CampoTexto
                    id="resgate-data"
                    v-model="formulario.data_resgate"
                    tipo="date"
                    rotulo="Data"
                    obrigatorio
                    :erro="formulario.errors.data_resgate"
                />
                <CampoTexto
                    id="resgate-observacao"
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
