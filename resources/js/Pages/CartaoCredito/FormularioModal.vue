<script setup>
import { computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import AvisoInformativo from '@/Components/Formulario/AvisoInformativo.vue';
import CampoMoeda from '@/Components/Formulario/CampoMoeda.vue';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import ChipsSugestao from '@/Components/Formulario/ChipsSugestao.vue';
import GradeOpcoes from '@/Components/Formulario/GradeOpcoes.vue';
import LinhaAlternancia from '@/Components/Formulario/LinhaAlternancia.vue';
import SeletorCor from '@/Components/Formulario/SeletorCor.vue';
import CartaoCreditoVisual from '@/Components/CartaoCredito/CartaoCreditoVisual.vue';
import MarcaBandeiraCartao from '@/Components/CartaoCredito/MarcaBandeiraCartao.vue';
import { calcularDiasAteVencimento, calcularMelhorDiaCompra } from '@/Helpers/cicloFatura';
import { formatarDia, opcoesDiasDoMes } from '@/Helpers/formatarDia';

const BANDEIRA_SEM_SELECAO = 'selecione';

const ATALHOS_LIMITE = [
    { rotulo: '5k', valor: '5.000,00' },
    { rotulo: '10k', valor: '10.000,00' },
    { rotulo: '20k', valor: '20.000,00' },
];

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    bandeiras: {
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
    cartao: {
        type: Object,
        default: null,
    },
    nomeTitularPadrao: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['fechar']);

const pagina = usePage();
const urlCriar = computed(() => pagina.props.rotas.cartoesCreditoCriar);
const editando = computed(() => Boolean(props.cartao));

const opcoesBandeira = computed(() => props.bandeiras.filter((opcao) => opcao.valor !== BANDEIRA_SEM_SELECAO));
const acabamentoPadrao = computed(() => props.acabamentos[0]?.valor ?? 'esmeralda');
const bandeiraPadrao = computed(() => opcoesBandeira.value[0]?.valor ?? 'visa');

const opcoesDias = opcoesDiasDoMes().map((opcao) => ({ ...opcao, rotulo: `Dia ${opcao.rotulo}` }));

const modelosRapidos = computed(() =>
    props.bancosSugeridos.map((banco) => ({
        chave: banco.nome,
        rotulo: banco.nome,
        acabamento: banco.acabamento,
        bandeira: banco.bandeira,
    })),
);

const formulario = useForm({
    nome: '',
    nome_titular: '',
    ultimos_digitos: '',
    limite_total: '0,00',
    dia_fechamento: 1,
    dia_vencimento: 10,
    bandeira: 'visa',
    acabamento: 'esmeralda',
    padrao: null,
});

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.nome = props.cartao?.nome ?? '';
    formulario.nome_titular = props.cartao?.nome_titular ?? props.nomeTitularPadrao;
    formulario.ultimos_digitos = props.cartao?.ultimos_digitos ?? '';
    formulario.limite_total = props.cartao?.limite_total ?? '0,00';
    formulario.dia_fechamento = props.cartao?.dia_fechamento ?? 1;
    formulario.dia_vencimento = props.cartao?.dia_vencimento ?? 10;
    formulario.bandeira = props.cartao?.bandeira && props.cartao.bandeira !== BANDEIRA_SEM_SELECAO
        ? props.cartao.bandeira
        : bandeiraPadrao.value;
    formulario.acabamento = props.cartao?.acabamento ?? acabamentoPadrao.value;
    formulario.padrao = props.cartao?.padrao ?? null;
}

watch(
    () => [props.aberto, props.cartao],
    () => {
        if (props.aberto) {
            reiniciarFormulario();
        }
    },
);

const corAcabamentoSelecionado = computed(() =>
    props.acabamentos.find((opcao) => opcao.valor === formulario.acabamento)?.cor ?? '#1fa67e',
);

const melhorDiaCompra = computed(() => formatarDia(calcularMelhorDiaCompra(formulario.dia_fechamento)));
const diasAteVencimento = computed(() =>
    calcularDiasAteVencimento(formulario.dia_fechamento, formulario.dia_vencimento),
);

function selecionarModeloRapido(modelo) {
    formulario.nome = modelo.rotulo;

    if (modelo.acabamento) {
        formulario.acabamento = modelo.acabamento;
    }

    if (modelo.bandeira) {
        formulario.bandeira = modelo.bandeira;
    }
}

function fechar() {
    emit('fechar');
}

function salvarCartaoCredito() {
    const opcoes = {
        preserveScroll: true,
        onSuccess: () => fechar(),
    };

    if (editando.value) {
        formulario.put(props.cartao.url_atualizar, opcoes);
        return;
    }

    formulario.post(urlCriar.value, opcoes);
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        icone="credit_card"
        :titulo="editando ? 'Editar cartão' : 'Adicionar novo cartão'"
        subtitulo="Cadastre seus dados para controle em tempo real de faturas e limites."
        :texto-confirmar="editando ? 'Salvar alterações' : 'Salvar cartão'"
        :processando="formulario.processing"
        @confirmar="salvarCartaoCredito"
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

                <CartaoCreditoVisual
                    :nome="formulario.nome"
                    :nome-titular="formulario.nome_titular"
                    :ultimos-digitos="formulario.ultimos_digitos"
                    :bandeira="formulario.bandeira"
                    :limite="formulario.limite_total"
                    :dia-fechamento="formatarDia(formulario.dia_fechamento)"
                    :dia-vencimento="formatarDia(formulario.dia_vencimento)"
                    :cor-acabamento="corAcabamentoSelecionado"
                />
            </div>

            <CampoTexto
                id="cartao-nome"
                v-model="formulario.nome"
                rotulo="Identificação do cartão"
                dica="Ex: Banco ou apelido"
                obrigatorio
                :maxlength="100"
                :erro="formulario.errors.nome"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <CampoTexto
                    id="cartao-nome-titular"
                    v-model="formulario.nome_titular"
                    class="sm:col-span-2"
                    rotulo="Nome do titular"
                    obrigatorio
                    :maxlength="100"
                    :erro="formulario.errors.nome_titular"
                />
                <CampoTexto
                    id="cartao-ultimos-digitos"
                    v-model="formulario.ultimos_digitos"
                    rotulo="Últimos dígitos"
                    placeholder="0000"
                    inputmode="numeric"
                    :maxlength="4"
                    alinhamento="centro"
                    mono
                    somente-digitos
                    :erro="formulario.errors.ultimos_digitos"
                />
            </div>

            <CampoMoeda
                id="cartao-limite-total"
                v-model="formulario.limite_total"
                rotulo="Limite total do cartão"
                obrigatorio
                :atalhos="ATALHOS_LIMITE"
                :erro="formulario.errors.limite_total"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <CampoSelecao
                    id="cartao-dia-fechamento"
                    v-model="formulario.dia_fechamento"
                    rotulo="Dia de fechamento"
                    :opcoes="opcoesDias"
                    :erro="formulario.errors.dia_fechamento"
                />
                <CampoSelecao
                    id="cartao-dia-vencimento"
                    v-model="formulario.dia_vencimento"
                    rotulo="Dia de vencimento"
                    :opcoes="opcoesDias"
                    :erro="formulario.errors.dia_vencimento"
                />
            </div>

            <AvisoInformativo
                rotulo="Melhor dia de compra:"
                :valor="`Dia ${melhorDiaCompra}`"
                :complemento="`${diasAteVencimento} dias até o vencimento`"
            />

            <GradeOpcoes
                v-model="formulario.bandeira"
                rotulo="Bandeira do cartão"
                :opcoes="opcoesBandeira"
                carrossel
                :erro="formulario.errors.bandeira"
            >
                <template #icone="{ opcao }">
                    <span class="flex h-6 items-center">
                        <MarcaBandeiraCartao :bandeira="opcao.valor" :rotulo="opcao.rotulo" />
                    </span>
                </template>
            </GradeOpcoes>

            <LinhaAlternancia
                v-model="formulario.padrao"
                icone="credit_card"
                titulo="Definir como cartão padrão"
                descricao="Priorizado para faturas e novos lançamentos no painel"
            />

            <p v-if="formulario.errors.acabamento" class="text-xs text-red-500">
                {{ formulario.errors.acabamento }}
            </p>
        </div>
    </Modal>
</template>
