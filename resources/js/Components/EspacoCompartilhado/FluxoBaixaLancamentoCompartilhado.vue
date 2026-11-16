<script setup>
import { computed } from 'vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import FormularioLancamentoModal from '@/Components/Lancamento/FormularioLancamentoModal.vue';
import AvisoNaoResponsavelModal from '@/Components/EspacoCompartilhado/AvisoNaoResponsavelModal.vue';
import ModalAssumirLancamentoCompartilhado from '@/Components/EspacoCompartilhado/ModalAssumirLancamentoCompartilhado.vue';
import { useBaixaLancamentoCompartilhado } from '@/Composables/useBaixaLancamentoCompartilhado';

const props = defineProps({
    nomeEspaco: {
        type: String,
        required: true,
    },
    ano: {
        type: Number,
        required: true,
    },
    mes: {
        type: Number,
        required: true,
    },
});

const {
    lancamentoSelecionado,
    confirmacaoBaixaAberta,
    formularioPessoalAberto,
    avisoNaoResponsavelAberto,
    confirmacaoAssumirAberta,
    confirmacaoEstornoAberta,
    carregandoDadosPessoais,
    processandoAcao,
    dadosLancamentoPessoal,
    erroCategoriaAssumir,
    valoresIniciaisDoLancamentoPessoal,
    dadosExtrasDaBaixa,
    iniciarBaixaDoLancamento,
    confirmarRegistroNoControlePessoal,
    pedirConfirmacaoParaAssumir,
    confirmarAssumirLancamento,
    iniciarEstornoDaBaixa,
    confirmarEstornoDaBaixa,
    cancelarFluxo,
} = useBaixaLancamentoCompartilhado(() => props.nomeEspaco);

const descricaoSelecionada = computed(() => lancamentoSelecionado.value?.descricao ?? 'este lançamento');

const mensagemConfirmacaoBaixa = computed(() => [
    `"${descricaoSelecionada.value}" será marcado como pago no espaço compartilhado.`,
    'Na próxima tela informe conta ou cartão e a data do pagamento. O valor já conta no seu planejamento enquanto estiver sob sua responsabilidade.',
].join('\n\n'));

const mensagemConfirmacaoAssumir = computed(() => {
    const nomeResponsavel = lancamentoSelecionado.value?.responsavel?.nome ?? 'outro membro';

    return `Você passará a ser responsável por "${descricaoSelecionada.value}", que hoje pertence a ${nomeResponsavel}. Escolha uma das suas categorias para o planejamento. A alteração fica registrada no histórico do espaço.`;
});

const mensagemConfirmacaoEstorno = computed(() => [
    `"${descricaoSelecionada.value}" voltará a ficar pendente no espaço compartilhado.`,
    'Os dados de pagamento (conta/cartão) serão limpos neste lançamento.',
].join('\n\n'));

const urlDaBaixa = computed(() => lancamentoSelecionado.value?.url_baixa ?? '');

defineExpose({ iniciarBaixaDoLancamento, iniciarEstornoDaBaixa });
</script>

<template>
    <ModalNotificacao
        :aberto="confirmacaoBaixaAberta"
        titulo="Marcar como pago"
        :mensagem="mensagemConfirmacaoBaixa"
        texto-confirmar="Continuar"
        texto-cancelar="Cancelar"
        :processando="carregandoDadosPessoais"
        @confirmar="confirmarRegistroNoControlePessoal"
        @cancelar="cancelarFluxo"
    />

    <AvisoNaoResponsavelModal
        :aberto="avisoNaoResponsavelAberto"
        :lancamento="lancamentoSelecionado"
        @assumir="pedirConfirmacaoParaAssumir"
        @fechar="cancelarFluxo"
    />

    <ModalAssumirLancamentoCompartilhado
        :aberto="confirmacaoAssumirAberta"
        :mensagem="mensagemConfirmacaoAssumir"
        :categorias="dadosLancamentoPessoal?.categorias ?? []"
        :processando="processandoAcao || carregandoDadosPessoais"
        :erro-categoria="erroCategoriaAssumir"
        @confirmar="confirmarAssumirLancamento"
        @cancelar="cancelarFluxo"
    />

    <ModalNotificacao
        :aberto="confirmacaoEstornoAberta"
        titulo="Estornar baixa"
        :mensagem="mensagemConfirmacaoEstorno"
        texto-confirmar="Estornar"
        texto-cancelar="Cancelar"
        perigo
        :processando="processandoAcao"
        @confirmar="confirmarEstornoDaBaixa"
        @cancelar="cancelarFluxo"
    />

    <FormularioLancamentoModal
        :aberto="formularioPessoalAberto"
        :ano="ano"
        :mes="mes"
        :contas-bancarias="dadosLancamentoPessoal?.contasBancarias ?? []"
        :cartoes-credito="dadosLancamentoPessoal?.cartoesCredito ?? []"
        :categorias="dadosLancamentoPessoal?.categorias ?? []"
        :situacoes="dadosLancamentoPessoal?.situacoes ?? []"
        :url-envio="urlDaBaixa"
        :valores-iniciais="valoresIniciaisDoLancamentoPessoal"
        :campos-bloqueados="['tipo', 'valor']"
        :dados-extras-envio="dadosExtrasDaBaixa"
        ocultar-repeticao
        titulo="Confirmar pagamento"
        subtitulo="Informe como você pagou este lançamento compartilhado."
        texto-confirmar="Confirmar pagamento"
        @fechar="cancelarFluxo"
    />
</template>
