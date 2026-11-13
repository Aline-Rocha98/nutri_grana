<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import CampoAreaTexto from '@/Components/Formulario/CampoAreaTexto.vue';
import CampoMoeda from '@/Components/Formulario/CampoMoeda.vue';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import SecaoRecolhivel from '@/Components/Ui/SecaoRecolhivel.vue';
import SeletorPlanoFinanceiro from '@/Components/PlanoFinanceiro/SeletorPlanoFinanceiro.vue';
import { moedaParaNumero } from '@/Helpers/mascaraMoeda';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    renda: {
        type: Object,
        default: null,
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
        default: '',
    },
});

const emit = defineEmits(['fechar']);

const editando = computed(() => Boolean(props.renda));
const modalConfirmacaoAberto = ref(false);
const secaoPlanoFinanceiroAberta = ref(false);

const formulario = useForm({
    descricao: '',
    valor_esperado: '0,00',
    id_conta_bancaria: null,
    frequencia: props.frequencias[0]?.valor ?? 'mensal',
    dia_esperado: 1,
    observacao: '',
    id_plano_financeiro: null,
});

const opcoesContas = computed(() => props.contasBancarias.map((conta) => ({
    valor: conta.id,
    rotulo: conta.nome,
})));

const opcoesFrequencias = computed(() => props.frequencias.map((frequencia) => ({
    valor: frequencia.valor,
    rotulo: frequencia.rotulo,
})));

const valorRendaDigitado = computed(() => moedaParaNumero(formulario.valor_esperado));

const planoFinanceiroPadrao = computed(() => props.planosFinanceiros.find((plano) => plano.padrao) ?? null);

const quantidadePlanosAplicados = computed(() => (formulario.id_plano_financeiro ? 1 : 0));

const mensagemConfirmacao = computed(() => {
    if (editando.value) {
        return 'Ao salvar, os lançamentos previstos vinculados a esta renda serão atualizados. Deseja confirmar?';
    }

    return 'Ao confirmar, será criado automaticamente um lançamento de receita na tela de Lançamentos. Deseja continuar?';
});

function reiniciarFormularioRenda() {
    formulario.clearErrors();
    formulario.descricao = props.renda?.descricao ?? '';
    formulario.valor_esperado = props.renda?.valor_esperado ?? '0,00';
    formulario.id_conta_bancaria = props.renda?.id_conta_bancaria
        ?? props.contasBancarias[0]?.id
        ?? null;
    formulario.frequencia = props.renda?.frequencia
        ?? (props.frequencias[0]?.valor ?? 'mensal');
    formulario.dia_esperado = props.renda?.dia_esperado ?? 1;
    formulario.observacao = props.renda?.observacao ?? '';
    formulario.id_plano_financeiro = editando.value
        ? (props.renda.id_plano_financeiro ?? null)
        : (planoFinanceiroPadrao.value?.id ?? null);
    secaoPlanoFinanceiroAberta.value = Boolean(formulario.id_plano_financeiro);
    modalConfirmacaoAberto.value = false;
}

watch(
    () => [props.aberto, props.renda],
    () => {
        if (props.aberto) {
            reiniciarFormularioRenda();
        }
    },
);

watch(
    () => formulario.errors.id_plano_financeiro,
    (erro) => {
        if (erro) {
            secaoPlanoFinanceiroAberta.value = true;
        }
    },
);

function fecharFormularioRenda() {
    modalConfirmacaoAberto.value = false;
    emit('fechar');
}

function pedirConfirmacaoRenda() {
    modalConfirmacaoAberto.value = true;
}

function cancelarConfirmacaoRenda() {
    if (formulario.processing) {
        return;
    }

    modalConfirmacaoAberto.value = false;
}

function salvarRenda() {
    const opcoes = {
        preserveScroll: true,
        onSuccess: () => fecharFormularioRenda(),
        onFinish: () => {
            modalConfirmacaoAberto.value = false;
        },
    };

    if (editando.value) {
        formulario.put(props.renda.url_atualizar, opcoes);
        return;
    }

    formulario.post(props.urlCriar, opcoes);
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        icone="payments"
        :titulo="editando ? 'Editar renda' : 'Nova renda'"
        subtitulo="Cadastre suas rendas, como salários, freelances e outras receitas."
        :texto-confirmar="editando ? 'Salvar alterações' : 'Salvar renda'"
        :processando="formulario.processing"
        @fechar="fecharFormularioRenda"
        @confirmar="pedirConfirmacaoRenda"
    >
        <div class="space-y-5">
            <CampoTexto
                id="renda-descricao"
                v-model="formulario.descricao"
                rotulo="Descrição"
                placeholder="Ex.: Salário"
                maxlength="255"
                obrigatorio
                :erro="formulario.errors.descricao"
            />

            <CampoMoeda
                id="renda-valor-esperado"
                v-model="formulario.valor_esperado"
                rotulo="Valor esperado"
                dica="Use o valor médio se ele variar"
                obrigatorio
                :erro="formulario.errors.valor_esperado"
            />

            <CampoSelecao
                id="renda-conta-bancaria"
                v-model="formulario.id_conta_bancaria"
                rotulo="Conta bancária de recebimento"
                :opcoes="opcoesContas"
                obrigatorio
                :erro="formulario.errors.id_conta_bancaria"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <CampoSelecao
                    id="renda-frequencia"
                    v-model="formulario.frequencia"
                    rotulo="Frequência"
                    :opcoes="opcoesFrequencias"
                    obrigatorio
                    :erro="formulario.errors.frequencia"
                />

                <CampoTexto
                    id="renda-dia-esperado"
                    :model-value="formulario.dia_esperado"
                    tipo="number"
                    inputmode="numeric"
                    rotulo="Dia do recebimento"
                    obrigatorio
                    mono
                    :erro="formulario.errors.dia_esperado"
                    @update:model-value="formulario.dia_esperado = Number($event) || ''"
                />
            </div>

            <CampoAreaTexto
                id="renda-observacao"
                v-model="formulario.observacao"
                rotulo="Observações"
                :linhas="2"
                maxlength="2000"
                :erro="formulario.errors.observacao"
            />

            <SecaoRecolhivel
                v-model:aberto="secaoPlanoFinanceiroAberta"
                titulo="Plano financeiro"
                descricao="Escolha como esta renda será distribuída a cada recebimento."
                icone="donut_large"
                :contador="quantidadePlanosAplicados"
                :destacar-erro="Boolean(formulario.errors.id_plano_financeiro)"
            >
                <SeletorPlanoFinanceiro
                    id="renda-plano-financeiro"
                    v-model="formulario.id_plano_financeiro"
                    :planos="planosFinanceiros"
                    :valor-renda="valorRendaDigitado"
                    :url-planos-financeiros="urlPlanosFinanceiros"
                    :erro="formulario.errors.id_plano_financeiro"
                />
            </SecaoRecolhivel>
        </div>
    </Modal>

    <ModalNotificacao
        :aberto="modalConfirmacaoAberto"
        :titulo="editando ? 'Atualizar renda?' : 'Confirmar cadastro?'"
        :mensagem="mensagemConfirmacao"
        texto-confirmar="Confirmar"
        texto-cancelar="Cancelar"
        :processando="formulario.processing"
        @confirmar="salvarRenda"
        @cancelar="cancelarConfirmacaoRenda"
    />
</template>
