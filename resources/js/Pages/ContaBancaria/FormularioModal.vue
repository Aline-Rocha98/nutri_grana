<script setup>
import { computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CampoMoeda from '@/Components/Formulario/CampoMoeda.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import ChipsSugestao from '@/Components/Formulario/ChipsSugestao.vue';
import GradeOpcoes from '@/Components/Formulario/GradeOpcoes.vue';
import LinhaAlternancia from '@/Components/Formulario/LinhaAlternancia.vue';
import SeletorCor from '@/Components/Formulario/SeletorCor.vue';
import ContaBancariaVisual from '@/Components/ContaBancaria/ContaBancariaVisual.vue';
import { iconeTipoContaBancaria } from '@/Helpers/tipoContaBancaria';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
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
    conta: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['fechar']);

const pagina = usePage();
const urlCriar = computed(() => pagina.props.rotas.contasBancariasCriar);
const editando = computed(() => Boolean(props.conta));

const tipoPadrao = computed(() => props.tipos[0]?.valor ?? 'corrente');
const acabamentoPadrao = computed(() => props.acabamentos[0]?.valor ?? 'esmeralda');

const modelosRapidos = computed(() =>
    props.bancosSugeridos.map((banco) => ({
        chave: banco.nome,
        rotulo: banco.nome,
        logo: banco.logo,
        acabamento: banco.acabamento,
    })),
);

const formulario = useForm({
    nome: '',
    saldo_inicial: '0,00',
    tipo: 'corrente',
    acabamento: 'esmeralda',
    padrao_desconto: null,
    exibir_resumo: null,
});

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.nome = props.conta?.nome ?? '';
    formulario.saldo_inicial = props.conta?.saldo_inicial ?? '0,00';
    formulario.tipo = props.conta?.tipo ?? tipoPadrao.value;
    formulario.acabamento = props.conta?.acabamento ?? acabamentoPadrao.value;
    formulario.padrao_desconto = props.conta?.padrao_desconto ?? null;
    formulario.exibir_resumo = props.conta?.exibir_resumo ?? null;
}

watch(
    () => [props.aberto, props.conta],
    () => {
        if (props.aberto) {
            reiniciarFormulario();
        }
    },
);

const corAcabamentoSelecionado = computed(() =>
    props.acabamentos.find((opcao) => opcao.valor === formulario.acabamento)?.cor ?? '#1fa67e',
);

const rotuloTipoSelecionado = computed(() =>
    props.tipos.find((opcao) => opcao.valor === formulario.tipo)?.rotulo ?? '',
);

const logoPreVisualizacao = computed(() => {
    const nomeDigitado = (formulario.nome || '').trim().toLowerCase();
    const modeloCorrespondente = modelosRapidos.value.find((modelo) => modelo.rotulo.toLowerCase() === nomeDigitado);

    return modeloCorrespondente?.logo ?? props.conta?.logo ?? null;
});

function selecionarModeloRapido(modelo) {
    formulario.nome = modelo.rotulo;

    if (modelo.acabamento) {
        formulario.acabamento = modelo.acabamento;
    }
}

function fechar() {
    emit('fechar');
}

function salvarContaBancaria() {
    const opcoes = {
        preserveScroll: true,
        onSuccess: () => fechar(),
    };

    if (editando.value) {
        formulario.put(props.conta.url_atualizar, opcoes);
        return;
    }

    formulario.post(urlCriar.value, opcoes);
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        icone="account_balance"
        :titulo="editando ? 'Editar conta' : 'Adicionar nova conta'"
        subtitulo="Cadastre suas contas para acompanhar saldos e movimentações."
        :texto-confirmar="editando ? 'Salvar alterações' : 'Salvar conta'"
        :processando="formulario.processing"
        @confirmar="salvarContaBancaria"
        @fechar="fechar"
    >
        <div class="space-y-5">
            <ChipsSugestao
                rotulo="Modelos rápidos"
                icone="bolt"
                :itens="modelosRapidos"
                :chave-ativa="formulario.nome"
                @selecionar="selecionarModeloRapido"
            />

            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-ng-ink-subtle">
                        Visualização em tempo real
                    </span>
                    <SeletorCor v-model="formulario.acabamento" rotulo="Acabamento" :opcoes="acabamentos" />
                </div>

                <ContaBancariaVisual
                    :nome="formulario.nome"
                    :tipo="formulario.tipo"
                    :tipo-rotulo="rotuloTipoSelecionado"
                    :logo="logoPreVisualizacao"
                    :saldo-atual="conta?.saldo_atual ?? formulario.saldo_inicial"
                    :saldo-atual-numero="conta?.saldo_atual_numero ?? 0"
                    :saldo-inicial="formulario.saldo_inicial"
                    :cor-acabamento="corAcabamentoSelecionado"
                />
            </div>

            <CampoTexto
                id="conta-nome"
                v-model="formulario.nome"
                rotulo="Descrição"
                dica="Ex: Banco ou apelido"
                obrigatorio
                :maxlength="100"
                :erro="formulario.errors.nome"
            />

            <CampoMoeda
                id="conta-saldo-inicial"
                v-model="formulario.saldo_inicial"
                rotulo="Saldo inicial"
                :obrigatorio="!editando"
                :desabilitado="editando"
                :dica="editando ? 'Não pode ser alterado na edição' : ''"
                :erro="formulario.errors.saldo_inicial"
            />

            <GradeOpcoes
                v-model="formulario.tipo"
                rotulo="Tipo da conta"
                :opcoes="tipos"
                :colunas="3"
                :erro="formulario.errors.tipo"
            >
                <template #icone="{ opcao, ativo }">
                    <span
                        class="material-symbols-outlined text-[22px]"
                        :class="ativo ? 'text-ng-brand' : 'text-ng-ink-muted'"
                    >
                        {{ iconeTipoContaBancaria(opcao.valor) }}
                    </span>
                </template>
            </GradeOpcoes>

            <div class="space-y-3">
                <LinhaAlternancia
                    v-model="formulario.padrao_desconto"
                    icone="account_balance_wallet"
                    titulo="Padrão para ser descontado"
                    descricao="Sugerida automaticamente nos novos lançamentos"
                />
                <LinhaAlternancia
                    v-model="formulario.exibir_resumo"
                    icone="grid_view"
                    titulo="Exibir no resumo"
                    descricao="Mostra o saldo desta conta no painel inicial"
                />
            </div>

            <p v-if="formulario.errors.acabamento" class="text-xs text-red-500">
                {{ formulario.errors.acabamento }}
            </p>
        </div>
    </Modal>
</template>
