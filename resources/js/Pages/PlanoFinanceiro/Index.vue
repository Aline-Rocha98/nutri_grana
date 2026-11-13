<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import CabecalhoSecao from '@/Components/Ui/CabecalhoSecao.vue';
import CardAdicionarPlaceholder from '@/Components/Ui/CardAdicionarPlaceholder.vue';
import CardPlanoFinanceiro from '@/Components/PlanoFinanceiro/CardPlanoFinanceiro.vue';
import FormularioModal from '@/Pages/PlanoFinanceiro/FormularioModal.vue';

const props = defineProps({
    planosFinanceiros: {
        type: Array,
        default: () => [],
    },
    contasBancarias: {
        type: Array,
        default: () => [],
    },
    objetivos: {
        type: Array,
        default: () => [],
    },
    alocacoes: {
        type: Array,
        default: () => [],
    },
    urlAlocacoes: {
        type: String,
        default: '',
    },
    tiposCalculo: {
        type: Array,
        default: () => [],
    },
    coresItem: {
        type: Array,
        default: () => [],
    },
    urlCriar: {
        type: String,
        required: true,
    },
});

const pagina = usePage();
const modalFormularioAberto = ref(false);
const planoEmEdicao = ref(null);

const acaoPendente = ref(null);
const processandoAcao = ref(false);

watch(
    () => pagina.props.errors,
    (erros) => {
        if (erros && Object.keys(erros).length > 0) {
            modalFormularioAberto.value = true;
        }
    },
    { deep: true, immediate: true },
);

const tituloSecaoPlanos = computed(() => `Planos de distribuição (${props.planosFinanceiros.length})`);

function abrirCriarPlanoFinanceiro() {
    planoEmEdicao.value = null;
    modalFormularioAberto.value = true;
}

function abrirEditarPlanoFinanceiro(plano) {
    planoEmEdicao.value = plano;
    modalFormularioAberto.value = true;
}

function fecharFormularioPlanoFinanceiro() {
    modalFormularioAberto.value = false;
    planoEmEdicao.value = null;
}

function montarAcaoExclusao(plano) {
    return {
        titulo: 'Excluir plano financeiro',
        mensagem: plano.quantidade_rendas > 0
            ? `O plano "${plano.nome}" está aplicado em ${plano.quantidade_rendas} renda(s). Troque o plano nessas rendas antes de excluir.`
            : `Deseja excluir o plano "${plano.nome}"? Os valores já guardados continuam nas contas.`,
        textoConfirmar: 'Excluir',
        perigo: true,
        executar: (opcoes) => router.delete(plano.url_excluir, opcoes),
    };
}

function montarAcaoAlternarSituacao(plano) {
    if (plano.esta_ativo) {
        return {
            titulo: 'Inativar plano financeiro',
            mensagem: `Deseja inativar o plano "${plano.nome}"? Ele deixa de aparecer na escolha de plano das rendas.`,
            textoConfirmar: 'Inativar',
            perigo: true,
            executar: (opcoes) => router.patch(plano.url_alternar_situacao, {}, opcoes),
        };
    }

    return {
        titulo: 'Ativar plano financeiro',
        mensagem: `Deseja ativar o plano "${plano.nome}"? Ele volta a aparecer na escolha de plano das rendas.`,
        textoConfirmar: 'Ativar',
        perigo: false,
        executar: (opcoes) => router.patch(plano.url_alternar_situacao, {}, opcoes),
    };
}

function montarAcaoDefinirPadrao(plano) {
    return {
        titulo: 'Definir plano padrão',
        mensagem: `O plano "${plano.nome}" será sugerido automaticamente ao cadastrar novas rendas. Deseja continuar?`,
        textoConfirmar: 'Definir como padrão',
        perigo: false,
        executar: (opcoes) => router.patch(plano.url_definir_padrao, {}, opcoes),
    };
}

function pedirConfirmacaoDaAcao(acao) {
    acaoPendente.value = acao;
}

function cancelarAcaoPendente() {
    if (processandoAcao.value) {
        return;
    }

    acaoPendente.value = null;
}

function confirmarAcaoPendente() {
    if (!acaoPendente.value || processandoAcao.value) {
        return;
    }

    processandoAcao.value = true;

    acaoPendente.value.executar({
        preserveScroll: true,
        onFinish: () => {
            processandoAcao.value = false;
            acaoPendente.value = null;
        },
    });
}
</script>

<template>
    <Head title="Plano financeiro" />

    <AutenticadoLayout titulo="Plano financeiro">
        <div class="space-y-8 p-6 lg:p-8">
            <section class="space-y-4">
                <CabecalhoSecao
                    :titulo="tituloSecaoPlanos"
                    subtitulo="Defina como sua renda será distribuída. O plano é escolhido no cadastro de cada renda."
                >
                    <template #acoes>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-ng-brand transition hover:text-ng-brand-hover"
                            @click="abrirCriarPlanoFinanceiro"
                        >
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            Novo plano
                        </button>
                    </template>
                </CabecalhoSecao>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <CardPlanoFinanceiro
                        v-for="plano in planosFinanceiros"
                        :key="plano.id"
                        :nome="plano.nome"
                        :descricao="plano.descricao ?? ''"
                        :padrao="plano.padrao"
                        :ativo="plano.esta_ativo"
                        :itens="plano.itens"
                        :quantidade-rendas="plano.quantidade_rendas"
                        @editar="abrirEditarPlanoFinanceiro(plano)"
                        @excluir="pedirConfirmacaoDaAcao(montarAcaoExclusao(plano))"
                        @alternar-situacao="pedirConfirmacaoDaAcao(montarAcaoAlternarSituacao(plano))"
                        @definir-padrao="pedirConfirmacaoDaAcao(montarAcaoDefinirPadrao(plano))"
                    />

                    <CardAdicionarPlaceholder
                        titulo="Novo plano"
                        descricao="Ex.: Essencial 50/30/20"
                        icone="donut_large"
                        @click="abrirCriarPlanoFinanceiro"
                    />
                </div>
            </section>
        </div>

        <FormularioModal
            :aberto="modalFormularioAberto"
            :plano="planoEmEdicao"
            :contas-bancarias="contasBancarias"
            :objetivos="objetivos"
            :alocacoes="alocacoes"
            :url-alocacoes="urlAlocacoes"
            :tipos-calculo="tiposCalculo"
            :cores-item="coresItem"
            :url-criar="urlCriar"
            @fechar="fecharFormularioPlanoFinanceiro"
        />

        <ModalNotificacao
            :aberto="Boolean(acaoPendente)"
            :titulo="acaoPendente?.titulo ?? ''"
            :mensagem="acaoPendente?.mensagem ?? ''"
            :texto-confirmar="acaoPendente?.textoConfirmar ?? 'Confirmar'"
            texto-cancelar="Cancelar"
            :perigo="acaoPendente?.perigo ?? false"
            :processando="processandoAcao"
            @confirmar="confirmarAcaoPendente"
            @cancelar="cancelarAcaoPendente"
        />
    </AutenticadoLayout>
</template>
