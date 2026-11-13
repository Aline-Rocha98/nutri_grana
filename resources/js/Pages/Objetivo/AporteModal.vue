<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CampoMoeda from '@/Components/Formulario/CampoMoeda.vue';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import ChipsSugestao from '@/Components/Formulario/ChipsSugestao.vue';
import ProgressoMeta from '@/Components/Ui/ProgressoMeta.vue';
import { formatarNumeroParaMoeda, moedaParaNumero } from '@/Helpers/mascaraMoeda';
import { calcularPercentualDaMeta } from '@/Helpers/objetivo';

const TIPO_APORTE_MANUAL = 'manual';
const TIPO_APORTE_CONTA_BANCARIA = 'conta_bancaria';
const VALORES_INCREMENTO_APORTE = [250, 500, 1000];

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    objetivo: {
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
    valor: '0,00',
    data_aporte: '',
    id_conta_bancaria: null,
    observacao: '',
});

const valorGuardadoAtual = computed(() => Number(props.objetivo?.valor_guardado_numero ?? 0));
const valorMeta = computed(() => Number(props.objetivo?.valor_meta_numero ?? 0));
const valorDoAporte = computed(() => moedaParaNumero(formulario.valor));
const valorGuardadoAposAporte = computed(() => valorGuardadoAtual.value + valorDoAporte.value);
const valorFaltanteAposAporte = computed(() => Math.max(0, valorMeta.value - valorGuardadoAposAporte.value));
const percentualAtual = computed(() => Number(props.objetivo?.percentual_atual ?? 0));
const percentualAposAporte = computed(() => calcularPercentualDaMeta(valorGuardadoAposAporte.value, valorMeta.value));
const depositoSugerido = computed(() => Number(props.objetivo?.deposito_mensal_sugerido_numero ?? 0));

const atalhosValor = computed(() => (depositoSugerido.value > 0
    ? [{ rotulo: `Sugerido: R$ ${formatarNumeroParaMoeda(depositoSugerido.value)}`, valor: formatarNumeroParaMoeda(depositoSugerido.value) }]
    : []));

const chipsIncremento = computed(() => {
    const valores = [...VALORES_INCREMENTO_APORTE];

    if (depositoSugerido.value > 0 && !valores.includes(depositoSugerido.value)) {
        valores.push(depositoSugerido.value);
    }

    return valores.map((valor) => ({
        chave: valor,
        valor,
        rotulo: `+R$ ${formatarNumeroParaMoeda(valor)}`,
    }));
});

const opcoesContas = computed(() => props.contasBancarias.map((conta) => ({
    valor: conta.id,
    rotulo: `${conta.nome} — Disponível: R$ ${conta.saldo_disponivel ?? conta.saldo_atual}`,
})));

const tituloDoModal = computed(() => (props.objetivo
    ? `Aportar em "${props.objetivo.descricao}"`
    : 'Registrar aporte'));

const subtituloDoModal = computed(() => (formulario.id_conta_bancaria
    ? 'Adicione saldo à sua meta financeira com débito em conta.'
    : 'Adicione saldo à sua meta financeira sem movimentar contas.'));

const erroDaConta = computed(() => formulario.errors.id_conta_bancaria || formulario.errors.tipo || '');

function obterDataDeHoje() {
    const hoje = new Date();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');

    return `${hoje.getFullYear()}-${mes}-${dia}`;
}

function contaPadraoParaAporte() {
    const contaPadrao = props.contasBancarias.find((conta) => conta.padrao_desconto === 'S');

    return (contaPadrao ?? props.contasBancarias[0])?.id ?? null;
}

function reiniciarFormularioAporte() {
    formulario.clearErrors();
    formulario.valor = '0,00';
    formulario.data_aporte = obterDataDeHoje();
    formulario.id_conta_bancaria = contaPadraoParaAporte();
    formulario.observacao = '';
}

watch(
    () => [props.aberto, props.objetivo],
    () => {
        if (props.aberto) {
            reiniciarFormularioAporte();
        }
    },
);

function somarIncrementoAoAporte(chip) {
    formulario.valor = formatarNumeroParaMoeda(valorDoAporte.value + chip.valor);
}

function fecharAporte() {
    emit('fechar');
}

function montarDadosEnvio(dados) {
    return {
        ...dados,
        tipo: dados.id_conta_bancaria ? TIPO_APORTE_CONTA_BANCARIA : TIPO_APORTE_MANUAL,
    };
}

function confirmarAporte() {
    if (!props.objetivo?.url_aportar) {
        return;
    }

    formulario.transform(montarDadosEnvio).post(props.objetivo.url_aportar, {
        preserveScroll: true,
        onSuccess: () => fecharAporte(),
    });
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        icone="savings"
        :titulo="tituloDoModal"
        :subtitulo="subtituloDoModal"
        texto-confirmar="Confirmar aporte"
        :processando="formulario.processing"
        @fechar="fecharAporte"
        @confirmar="confirmarAporte"
    >
        <div class="space-y-5">
            <div class="ng-card-muted p-4">
                <ProgressoMeta
                    rotulo="Progresso atual:"
                    :valor-atual="objetivo?.valor_guardado ?? '0,00'"
                    :valor-meta="objetivo?.valor_meta ?? '0,00'"
                    :percentual="percentualAtual"
                    :percentual-projetado="percentualAposAporte"
                >
                    <template #legenda-esquerda>
                        <span class="font-medium text-ng-brand">
                            Após este aporte: R$ {{ formatarNumeroParaMoeda(valorGuardadoAposAporte) }} ({{ percentualAposAporte }}%)
                        </span>
                    </template>
                    <template #legenda-direita>
                        Faltarão R$ {{ formatarNumeroParaMoeda(valorFaltanteAposAporte) }}
                    </template>
                </ProgressoMeta>
            </div>

            <div class="space-y-2">
                <CampoMoeda
                    id="valor-aporte"
                    v-model="formulario.valor"
                    rotulo="Valor do aporte"
                    obrigatorio
                    :atalhos="atalhosValor"
                    :erro="formulario.errors.valor"
                />
                <ChipsSugestao :itens="chipsIncremento" @selecionar="somarIncrementoAoAporte" />
            </div>

            <CampoSelecao
                id="conta-aporte"
                v-model="formulario.id_conta_bancaria"
                rotulo="Debitar da conta"
                :opcoes="opcoesContas"
                placeholder="Sem débito em conta (aporte manual)"
                placeholder-selecionavel
                :dica="contasBancarias.length === 0 ? 'Nenhuma conta ativa cadastrada' : ''"
                :erro="erroDaConta"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-[10rem_1fr]">
                <CampoTexto
                    id="data-aporte"
                    v-model="formulario.data_aporte"
                    tipo="date"
                    rotulo="Data"
                    obrigatorio
                    :erro="formulario.errors.data_aporte"
                />
                <CampoTexto
                    id="observacao-aporte"
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
