<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CampoMoeda from '@/Components/Formulario/CampoMoeda.vue';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';
import LinhaAlternancia from '@/Components/Formulario/LinhaAlternancia.vue';
import PainelMetaVisual from '@/Components/Ui/PainelMetaVisual.vue';
import { formatarNumeroParaMoeda, moedaParaNumero } from '@/Helpers/mascaraMoeda';
import { situacaoDoOrcamento } from '@/Helpers/orcamento';
import { nomeDoMes } from '@/Helpers/periodo';

const ATALHOS_VALOR_MENSAL = [
    { rotulo: '500', valor: '500,00' },
    { rotulo: '1k', valor: '1.000,00' },
    { rotulo: '2k', valor: '2.000,00' },
];

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
    ano: {
        type: Number,
        required: true,
    },
    mes: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['fechar']);

const pagina = usePage();
const urlCriar = computed(() => pagina.props.rotas.orcamentosCriar);
const editando = computed(() => Boolean(props.orcamento));
const urlAtualizar = ref('');

const formulario = useForm({
    tipo: 'por_categoria',
    id_categoria: null,
    valor_mensal: '0,00',
    exibir_dashboard: 'N',
    ano: props.ano,
    mes: props.mes,
});

const categoriasDisponiveis = computed(() => props.categorias.filter((categoria) => categoria.arquivada !== 'S'));

const opcoesCategorias = computed(() => categoriasDisponiveis.value.map((categoria) => ({
    valor: categoria.id,
    rotulo: categoria.nome,
})));

const categoriaSelecionada = computed(() => categoriasDisponiveis.value.find(
    (categoria) => categoria.id === formulario.id_categoria,
) ?? null);

const rotuloReferencia = computed(() => `${nomeDoMes(props.mes)} ${props.ano}`);
const valorMensalNumero = computed(() => moedaParaNumero(formulario.valor_mensal));
const gastoNoMes = computed(() => Number(props.orcamento?.gasto_mes_numero ?? 0));
const percentualUsadoPrevisto = computed(() => (valorMensalNumero.value > 0
    ? Math.round((gastoNoMes.value / valorMensalNumero.value) * 1000) / 10
    : 0));
const disponivelPrevisto = computed(() => Math.max(0, valorMensalNumero.value - gastoNoMes.value));

const situacaoPrevista = computed(() => situacaoDoOrcamento({
    ultrapassado: valorMensalNumero.value > 0 && gastoNoMes.value > valorMensalNumero.value,
    percentual: percentualUsadoPrevisto.value,
}));

const itensRodapePreview = computed(() => [
    { rotulo: 'Gasto', valor: `R$ ${formatarNumeroParaMoeda(gastoNoMes.value)}` },
    { rotulo: 'Disponível', valor: `R$ ${formatarNumeroParaMoeda(disponivelPrevisto.value)}` },
    { rotulo: 'Referência', valor: rotuloReferencia.value },
]);

function preencherFormularioDoOrcamento() {
    formulario.clearErrors();
    formulario.tipo = 'por_categoria';
    formulario.id_categoria = props.orcamento?.id_categoria ?? null;
    formulario.valor_mensal = props.orcamento?.valor_mensal ?? '0,00';
    formulario.exibir_dashboard = props.orcamento?.exibir_dashboard ?? 'N';
    formulario.ano = props.ano;
    formulario.mes = props.mes;
    urlAtualizar.value = props.orcamento?.url_atualizar ?? '';
}

watch(
    () => [props.aberto, props.orcamento],
    () => {
        if (props.aberto) {
            preencherFormularioDoOrcamento();
        }
    },
);

function fecharFormulario() {
    emit('fechar');
}

function salvarOrcamentoPorCategoria() {
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
        icone="donut_large"
        :titulo="editando ? 'Editar orçamento' : 'Novo orçamento'"
        subtitulo="Defina um limite mensal para uma categoria de despesa."
        :texto-confirmar="editando ? 'Salvar alterações' : 'Criar orçamento'"
        :processando="formulario.processing"
        @fechar="fecharFormulario"
        @confirmar="salvarOrcamentoPorCategoria"
    >
        <div class="space-y-5">
            <section>
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-ng-ink-subtle">
                    Visualização do orçamento em tempo real
                </p>
                <PainelMetaVisual
                    :titulo="categoriaSelecionada?.nome ?? ''"
                    titulo-padrao="Selecione a categoria"
                    :subtitulo="`Referência ${rotuloReferencia}`"
                    subtitulo-icone="event"
                    :icone="categoriaSelecionada?.icone || 'category'"
                    :cor-icone="categoriaSelecionada?.cor || '#1fa67e'"
                    :badge-texto="valorMensalNumero > 0 ? situacaoPrevista.rotulo : ''"
                    :badge-variante="situacaoPrevista.variante"
                    destaque-rotulo="Limite mensal"
                    :destaque-valor="formulario.valor_mensal"
                    :rotulo-progresso="`${percentualUsadoPrevisto}% usado`"
                    :valor-atual="formatarNumeroParaMoeda(gastoNoMes)"
                    :valor-meta="formulario.valor_mensal"
                    :percentual="percentualUsadoPrevisto"
                    :variante="situacaoPrevista.variante === 'red' ? 'red' : 'brand'"
                    :rodape="itensRodapePreview"
                />
            </section>

            <CampoSelecao
                id="categoria-orcamento"
                v-model="formulario.id_categoria"
                rotulo="Categoria"
                obrigatorio
                :opcoes="opcoesCategorias"
                placeholder="Selecione..."
                :erro="formulario.errors.id_categoria"
            />

            <CampoMoeda
                id="valor-mensal-orcamento"
                v-model="formulario.valor_mensal"
                rotulo="Valor máximo mensal"
                obrigatorio
                :atalhos="ATALHOS_VALOR_MENSAL"
                :erro="formulario.errors.valor_mensal"
            />

            <LinhaAlternancia
                v-model="formulario.exibir_dashboard"
                titulo="Exibir no dashboard principal"
                descricao="Acompanhe o consumo deste limite diretamente na tela inicial."
                icone="dashboard"
                valor-ativo="S"
                valor-inativo="N"
            />
        </div>
    </Modal>
</template>
