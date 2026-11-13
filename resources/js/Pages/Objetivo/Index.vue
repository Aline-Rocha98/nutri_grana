<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import BotaoAdicionarInline from '@/Components/Ui/BotaoAdicionarInline.vue';
import CabecalhoSecao from '@/Components/Ui/CabecalhoSecao.vue';
import CardAdicionarPlaceholder from '@/Components/Ui/CardAdicionarPlaceholder.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';
import CardMetaFinanceira from '@/Components/Ui/CardMetaFinanceira.vue';
import FormularioModal from '@/Pages/Objetivo/FormularioModal.vue';
import AporteModal from '@/Pages/Objetivo/AporteModal.vue';
import { varianteSituacaoObjetivo } from '@/Helpers/badge';
import {
    GRUPO_OBJETIVO_ATRASADO,
    GRUPO_OBJETIVO_CONCLUIDO,
    GRUPO_OBJETIVO_EM_DIA,
    grupoDaSituacaoObjetivo,
} from '@/Helpers/objetivo';

const CAMPOS_DO_APORTE = ['tipo', 'valor', 'data_aporte', 'id_conta_bancaria', 'observacao'];

const ROTULOS_CARD_OBJETIVO = {
    editar: 'Editar objetivo',
    excluir: 'Excluir objetivo',
    historico: 'Aportes realizados',
};

const props = defineProps({
    objetivos: {
        type: Array,
        default: () => [],
    },
    contasBancarias: {
        type: Array,
        default: () => [],
    },
});

const pagina = usePage();
const filtroSituacao = ref(null);
const modalFormularioAberto = ref(false);
const objetivoEmEdicao = ref(null);
const modalAporteAberto = ref(false);
const objetivoParaAporte = ref(null);
const modalExclusaoAberto = ref(false);
const objetivoParaExcluir = ref(null);
const excluindo = ref(false);

watch(
    () => pagina.props.errors,
    (erros) => {
        if (!erros || Object.keys(erros).length === 0) {
            return;
        }

        const ehErroDoAporte = CAMPOS_DO_APORTE.some((campo) => Object.prototype.hasOwnProperty.call(erros, campo));

        if (ehErroDoAporte) {
            modalAporteAberto.value = true;
            return;
        }

        modalFormularioAberto.value = true;
    },
    { deep: true, immediate: true },
);

function contarObjetivosDoGrupo(grupo) {
    return props.objetivos.filter((objetivo) => grupoDaSituacaoObjetivo(objetivo.situacao_ritmo) === grupo).length;
}

const opcoesFiltroSituacao = computed(() => [
    { valor: null, rotulo: 'Todas as metas', contador: props.objetivos.length },
    { valor: GRUPO_OBJETIVO_EM_DIA, rotulo: 'Em dia', contador: contarObjetivosDoGrupo(GRUPO_OBJETIVO_EM_DIA) },
    { valor: GRUPO_OBJETIVO_ATRASADO, rotulo: 'Atrasadas', contador: contarObjetivosDoGrupo(GRUPO_OBJETIVO_ATRASADO) },
    { valor: GRUPO_OBJETIVO_CONCLUIDO, rotulo: 'Concluídas', contador: contarObjetivosDoGrupo(GRUPO_OBJETIVO_CONCLUIDO) },
]);

const objetivosFiltrados = computed(() => {
    if (!filtroSituacao.value) {
        return props.objetivos;
    }

    return props.objetivos.filter(
        (objetivo) => grupoDaSituacaoObjetivo(objetivo.situacao_ritmo) === filtroSituacao.value,
    );
});

function montarLinhasDeAportes(objetivo) {
    return (objetivo.aportes ?? []).map((aporte) => ({
        chave: aporte.id,
        descricao: aporte.tipo_rotulo,
        subtitulo: [aporte.data_aporte_formatada, aporte.conta_bancaria_nome].filter(Boolean).join(' · '),
        subtituloMono: true,
        valor: aporte.valor,
        icone: 'savings',
        positivo: true,
    }));
}

function montarMetricasDoObjetivo(objetivo) {
    return [
        { rotulo: 'Sugerido / mês', valor: objetivo.deposito_mensal_sugerido, destacado: true },
        { rotulo: 'Esperado hoje', valor: objetivo.valor_esperado_hoje },
    ];
}

function objetivoEstaConcluido(objetivo) {
    return grupoDaSituacaoObjetivo(objetivo.situacao_ritmo) === GRUPO_OBJETIVO_CONCLUIDO;
}

function abrirCriar() {
    objetivoEmEdicao.value = null;
    modalFormularioAberto.value = true;
}

function abrirEditar(objetivo) {
    objetivoEmEdicao.value = objetivo;
    modalFormularioAberto.value = true;
}

function fecharFormulario() {
    modalFormularioAberto.value = false;
    objetivoEmEdicao.value = null;
}

function abrirAporte(objetivo) {
    objetivoParaAporte.value = objetivo;
    modalAporteAberto.value = true;
}

function fecharAporte() {
    modalAporteAberto.value = false;
    objetivoParaAporte.value = null;
}

function pedirExclusao(objetivo) {
    objetivoParaExcluir.value = objetivo;
    modalExclusaoAberto.value = true;
}

function fecharExclusao() {
    if (excluindo.value) {
        return;
    }

    modalExclusaoAberto.value = false;
    objetivoParaExcluir.value = null;
}

function confirmarExclusao() {
    if (!objetivoParaExcluir.value || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(objetivoParaExcluir.value.url_excluir, {
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            modalExclusaoAberto.value = false;
            objetivoParaExcluir.value = null;
        },
    });
}

const mensagemExclusao = computed(() => {
    const descricao = objetivoParaExcluir.value?.descricao;
    if (!descricao) {
        return 'Deseja excluir este objetivo? Esta ação não pode ser desfeita.';
    }

    return `Deseja excluir o objetivo "${descricao}"? Os aportes serão removidos. Lançamentos gerados em contas permanecem no histórico.`;
});
</script>

<template>
    <Head title="Objetivos" />

    <AutenticadoLayout titulo="Objetivos">
        <div class="space-y-6 p-6 lg:p-8">
            <CabecalhoSecao
                tamanho="pagina"
                titulo="Objetivos & Metas"
                subtitulo="Acompanhe o progresso das suas metas e quanto guardar por mês."
            >
                <template #acoes>
                    <BotaoAdicionarInline rotulo="Novo Objetivo" @click="abrirCriar" />
                </template>
            </CabecalhoSecao>

            <div v-if="objetivos.length" class="border-b border-ng-line pb-4">
                <GrupoSegmentado
                    v-model="filtroSituacao"
                    :opcoes="opcoesFiltroSituacao"
                    rotulo="Filtrar objetivos por situação"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <CardMetaFinanceira
                    v-for="objetivo in objetivosFiltrados"
                    :key="objetivo.id"
                    :titulo="objetivo.descricao"
                    :subtitulo="`Meta até ${objetivo.data_limite_formatada}`"
                    :badge-texto="objetivo.situacao_ritmo_rotulo"
                    :badge-variante="varianteSituacaoObjetivo(objetivo.situacao_ritmo)"
                    rotulo-progresso="Guardado"
                    :valor-atual="objetivo.valor_guardado"
                    :valor-meta="objetivo.valor_meta"
                    :percentual="objetivo.percentual_atual"
                    :legenda-esquerda="`${objetivo.percentual_atual}% concluído`"
                    :legenda-direita="`Falta: R$ ${objetivo.valor_faltante}`"
                    :metricas="montarMetricasDoObjetivo(objetivo)"
                    :historico="montarLinhasDeAportes(objetivo)"
                    :destacado="objetivoEstaConcluido(objetivo)"
                    :rotulos="ROTULOS_CARD_OBJETIVO"
                    @editar="abrirEditar(objetivo)"
                    @excluir="pedirExclusao(objetivo)"
                >
                    <button
                        v-if="!objetivoEstaConcluido(objetivo)"
                        type="button"
                        class="ng-btn-suave w-full"
                        @click="abrirAporte(objetivo)"
                    >
                        <span class="material-symbols-outlined text-[18px]">savings</span>
                        Aportar neste objetivo
                    </button>
                </CardMetaFinanceira>

                <CardAdicionarPlaceholder
                    class="min-h-[18rem]"
                    titulo="Novo Objetivo"
                    descricao="Defina a meta e a data limite"
                    @click="abrirCriar"
                />
            </div>

            <p
                v-if="objetivos.length && objetivosFiltrados.length === 0"
                class="text-center text-sm text-ng-ink-muted"
            >
                Nenhum objetivo nesta situação.
            </p>
        </div>

        <FormularioModal
            :aberto="modalFormularioAberto"
            :objetivo="objetivoEmEdicao"
            @fechar="fecharFormulario"
        />

        <AporteModal
            :aberto="modalAporteAberto"
            :objetivo="objetivoParaAporte"
            :contas-bancarias="contasBancarias"
            @fechar="fecharAporte"
        />

        <ModalNotificacao
            :aberto="modalExclusaoAberto"
            titulo="Excluir objetivo"
            :mensagem="mensagemExclusao"
            texto-confirmar="Excluir"
            texto-cancelar="Cancelar"
            perigo
            :processando="excluindo"
            @confirmar="confirmarExclusao"
            @cancelar="fecharExclusao"
        />
    </AutenticadoLayout>
</template>
