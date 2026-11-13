<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CampoMoeda from '@/Components/Formulario/CampoMoeda.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import LinhaAlternancia from '@/Components/Formulario/LinhaAlternancia.vue';
import PainelMetaVisual from '@/Components/Ui/PainelMetaVisual.vue';
import { varianteSituacaoObjetivo } from '@/Helpers/badge';
import { formatarNumeroParaMoeda, moedaParaNumero } from '@/Helpers/mascaraMoeda';
import { calcularDepositoMensalSugerido, calcularPercentualDaMeta } from '@/Helpers/objetivo';
import { formatarDataIsoParaBrasileira } from '@/Helpers/periodo';

const ATALHOS_VALOR_META = [
    { rotulo: '10k', valor: '10.000,00' },
    { rotulo: '25k', valor: '25.000,00' },
    { rotulo: '50k', valor: '50.000,00' },
];

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    objetivo: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['fechar']);

const pagina = usePage();
const urlCriar = computed(() => pagina.props.rotas.objetivosCriar);
const editando = computed(() => Boolean(props.objetivo));
const urlAtualizar = ref('');

const formulario = useForm({
    descricao: '',
    valor_meta: '0,00',
    valor_ja_guardado: '0,00',
    data_limite: '',
    exibir_dashboard: 'N',
});

const valorMetaNumero = computed(() => moedaParaNumero(formulario.valor_meta));

const valorGuardadoNumero = computed(() => (editando.value
    ? Number(props.objetivo?.valor_guardado_numero ?? 0)
    : moedaParaNumero(formulario.valor_ja_guardado)));

const percentualPrevisto = computed(() => calcularPercentualDaMeta(valorGuardadoNumero.value, valorMetaNumero.value));

const valorFaltantePrevisto = computed(() => Math.max(0, valorMetaNumero.value - valorGuardadoNumero.value));

const depositoMensalPrevisto = computed(() => calcularDepositoMensalSugerido(
    valorMetaNumero.value,
    valorGuardadoNumero.value,
    formulario.data_limite,
));

const dataLimiteFormatada = computed(() => formatarDataIsoParaBrasileira(formulario.data_limite));

const itensRodapePreview = computed(() => [
    { rotulo: 'Guardado', valor: `R$ ${formatarNumeroParaMoeda(valorGuardadoNumero.value)}` },
    { rotulo: 'Falta juntar', valor: `R$ ${formatarNumeroParaMoeda(valorFaltantePrevisto.value)}` },
    { rotulo: 'Prazo final', valor: dataLimiteFormatada.value || '—' },
]);

const dicaDepositoSugerido = computed(() => (formulario.data_limite && valorFaltantePrevisto.value > 0
    ? `Depósito sugerido: R$ ${formatarNumeroParaMoeda(depositoMensalPrevisto.value)}/mês`
    : ''));

function preencherFormularioDoObjetivo() {
    formulario.clearErrors();
    formulario.descricao = props.objetivo?.descricao ?? '';
    formulario.valor_meta = props.objetivo?.valor_meta ?? '0,00';
    formulario.valor_ja_guardado = props.objetivo?.valor_guardado ?? '0,00';
    formulario.data_limite = props.objetivo?.data_limite ?? '';
    formulario.exibir_dashboard = props.objetivo?.exibir_dashboard ?? 'N';
    urlAtualizar.value = props.objetivo?.url_atualizar ?? '';
}

watch(
    () => [props.aberto, props.objetivo],
    () => {
        if (props.aberto) {
            preencherFormularioDoObjetivo();
        }
    },
);

function fecharFormulario() {
    emit('fechar');
}

function salvarObjetivo() {
    const opcoes = {
        preserveScroll: true,
        onSuccess: () => fecharFormulario(),
    };

    if (editando.value) {
        formulario
            .transform(({ valor_ja_guardado: _valorJaGuardado, ...dados }) => dados)
            .put(urlAtualizar.value, opcoes);
        return;
    }

    formulario.transform((dados) => dados).post(urlCriar.value, opcoes);
}
</script>

<template>
    <Modal
        :aberto="aberto"
        icone="track_changes"
        :titulo="editando ? 'Editar objetivo' : 'Novo objetivo'"
        subtitulo="Defina a meta financeira e a data limite para juntar o valor."
        :texto-confirmar="editando ? 'Salvar alterações' : 'Criar objetivo'"
        :processando="formulario.processing"
        @fechar="fecharFormulario"
        @confirmar="salvarObjetivo"
    >
        <div class="space-y-5">
            <section>
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-ng-ink-subtle">
                    Visualização da meta em tempo real
                </p>
                <PainelMetaVisual
                    :titulo="formulario.descricao"
                    titulo-padrao="Novo objetivo"
                    :subtitulo="dataLimiteFormatada ? `Meta até ${dataLimiteFormatada}` : ''"
                    subtitulo-icone="event"
                    :badge-texto="objetivo?.situacao_ritmo_rotulo ?? ''"
                    :badge-variante="varianteSituacaoObjetivo(objetivo?.situacao_ritmo)"
                    destaque-rotulo="Aporte sugerido"
                    :destaque-valor="formatarNumeroParaMoeda(depositoMensalPrevisto)"
                    destaque-sufixo="/mês"
                    :rotulo-progresso="`${percentualPrevisto}% concluído`"
                    :valor-atual="formatarNumeroParaMoeda(valorGuardadoNumero)"
                    :valor-meta="formulario.valor_meta"
                    :percentual="percentualPrevisto"
                    :rodape="itensRodapePreview"
                />
            </section>

            <CampoTexto
                id="descricao-objetivo"
                v-model="formulario.descricao"
                rotulo="Descrição"
                obrigatorio
                placeholder="Ex.: Viagem, reserva de emergência..."
                maxlength="255"
                :erro="formulario.errors.descricao"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <CampoMoeda
                    id="valor-meta"
                    v-model="formulario.valor_meta"
                    rotulo="Valor meta"
                    obrigatorio
                    :atalhos="ATALHOS_VALOR_META"
                    :erro="formulario.errors.valor_meta"
                />
                <CampoMoeda
                    id="valor-ja-guardado"
                    v-model="formulario.valor_ja_guardado"
                    :rotulo="editando ? 'Valor já guardado' : 'Valor já guardado (opcional)'"
                    :desabilitado="editando"
                    :erro="formulario.errors.valor_ja_guardado"
                />
            </div>

            <CampoTexto
                id="data-limite"
                v-model="formulario.data_limite"
                tipo="date"
                rotulo="Data limite"
                obrigatorio
                :dica="dicaDepositoSugerido"
                :erro="formulario.errors.data_limite"
            />

            <LinhaAlternancia
                v-model="formulario.exibir_dashboard"
                titulo="Exibir no dashboard principal"
                descricao="Acompanhe o progresso deste objetivo diretamente na tela inicial."
                icone="dashboard"
                valor-ativo="S"
                valor-inativo="N"
            />
        </div>
    </Modal>
</template>
