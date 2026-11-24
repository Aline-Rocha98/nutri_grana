<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import BotaoAdicionarInline from '@/Components/Ui/BotaoAdicionarInline.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';
import CabecalhoSecao from '@/Components/Ui/CabecalhoSecao.vue';
import CardIndicador from '@/Components/Ui/CardIndicador.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';
import ListaLancamentos from '@/Components/Ui/ListaLancamentos.vue';
import NavegadorPeriodo from '@/Components/Ui/NavegadorPeriodo.vue';
import CardResumoMembro from '@/Components/EspacoCompartilhado/CardResumoMembro.vue';
import ConviteMembroModal from '@/Components/EspacoCompartilhado/ConviteMembroModal.vue';
import ExclusaoLancamentoCompartilhadoModal from '@/Components/EspacoCompartilhado/ExclusaoLancamentoCompartilhadoModal.vue';
import FluxoBaixaLancamentoCompartilhado from '@/Components/EspacoCompartilhado/FluxoBaixaLancamentoCompartilhado.vue';
import FormularioEspacoCompartilhadoModal from '@/Components/EspacoCompartilhado/FormularioEspacoCompartilhadoModal.vue';
import FormularioLancamentoCompartilhadoModal from '@/Components/EspacoCompartilhado/FormularioLancamentoCompartilhadoModal.vue';
import HistoricoAtividadesEspaco from '@/Components/EspacoCompartilhado/HistoricoAtividadesEspaco.vue';
import LinhaLancamentoCompartilhado from '@/Components/EspacoCompartilhado/LinhaLancamentoCompartilhado.vue';
import ListaMembrosEspaco from '@/Components/EspacoCompartilhado/ListaMembrosEspaco.vue';
import { navegarMes, nomeDoMes } from '@/Helpers/periodo';

const ABA_LANCAMENTOS = 'lancamentos';
const ABA_MEMBROS = 'membros';
const ABA_HISTORICO = 'historico';

const OPCOES_STATUS = [
    { valor: null, rotulo: 'Todos' },
    { valor: 'pendente', rotulo: 'Pendentes', variante: 'amber' },
    { valor: 'pago', rotulo: 'Pagos' },
];

const props = defineProps({
    espaco: { type: Object, required: true },
    ano: { type: Number, required: true },
    mes: { type: Number, required: true },
    filtros: { type: Object, default: () => ({}) },
    lancamentos: { type: Array, default: () => [] },
    resumo: { type: Object, default: () => ({}) },
    membros: { type: Array, default: () => [] },
    usuarioEhAdministrador: { type: Boolean, default: false },
    convitesPendentes: { type: Array, default: () => [] },
    historico: { type: Array, default: undefined },
    dadosLancamentoPessoal: { type: Object, default: undefined },
    frequencias: { type: Array, default: () => [] },
    urls: { type: Object, required: true },
});

const abaAtiva = ref(ABA_LANCAMENTOS);
const fluxoBaixa = ref(null);
const formularioLancamentoAberto = ref(false);
const lancamentoEmEdicao = ref(null);
const lancamentoParaExcluir = ref(null);
const modalConviteAberto = ref(false);
const modalRenomearAberto = ref(false);
const membroParaRemover = ref(null);
const confirmacaoSairAberta = ref(false);
const confirmacaoArquivarAberta = ref(false);
const processandoAcaoDoEspaco = ref(false);

const rotuloPeriodo = computed(() => `${nomeDoMes(props.mes)} ${props.ano}`);

const opcoesAba = computed(() => [
    { valor: ABA_LANCAMENTOS, rotulo: 'Lançamentos', icone: 'receipt_long', contador: props.lancamentos.length },
    { valor: ABA_MEMBROS, rotulo: 'Membros', icone: 'group', contador: props.membros.length },
    { valor: ABA_HISTORICO, rotulo: 'Histórico', icone: 'history' },
]);

const historicoCarregado = computed(() => (Array.isArray(props.historico) ? props.historico : null));

const lancamentosExibidos = computed(() => props.lancamentos.map((lancamento) => ({
    chave: lancamento.id,
    original: lancamento,
})));

const mensagemListaVazia = computed(() => (props.filtros.status
    ? 'Nenhum lançamento com este status no período.'
    : `Nenhum lançamento compartilhado em ${rotuloPeriodo.value}.`));

const mensagemRemoverMembro = computed(() => (membroParaRemover.value
    ? `${membroParaRemover.value.nome} deixará de ver os lançamentos deste espaço. Os lançamentos sob responsabilidade dele continuam no histórico.`
    : ''));

const mensagemSairDoEspaco = computed(
    () => `Você deixará de ver os lançamentos de "${props.espaco.nome}". Para voltar, será preciso um novo convite.`,
);

const mensagemArquivarEspaco = computed(
    () => `"${props.espaco.nome}" ficará oculto para todos os membros. Os lançamentos continuam guardados.`,
);

function visitarPeriodo(ano, mes, status = props.filtros.status) {
    router.get(`${props.urls.base}/${ano}/${mes}`, status ? { status } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function abrirMesAdjacente(deslocamento) {
    const periodo = navegarMes(props.ano, props.mes, deslocamento);
    visitarPeriodo(periodo.ano, periodo.mes);
}

function filtrarPorStatus(status) {
    visitarPeriodo(props.ano, props.mes, status);
}

function selecionarAba(aba) {
    abaAtiva.value = aba;

    if (aba === ABA_HISTORICO) {
        router.reload({ only: ['historico'] });
    }
}

function abrirNovoLancamento() {
    lancamentoEmEdicao.value = null;
    formularioLancamentoAberto.value = true;
}

function abrirEdicaoDoLancamento(lancamento) {
    lancamentoEmEdicao.value = lancamento;
    formularioLancamentoAberto.value = true;
}

function fecharFormularioLancamento() {
    formularioLancamentoAberto.value = false;
    lancamentoEmEdicao.value = null;
}

function darBaixaNoLancamento(lancamento) {
    fluxoBaixa.value?.iniciarBaixaDoLancamento(lancamento);
}

function estornarBaixaDoLancamento(lancamento) {
    fluxoBaixa.value?.iniciarEstornoDaBaixa(lancamento);
}

function executarAcaoDoEspaco(metodo, url, aoFinalizar) {
    if (processandoAcaoDoEspaco.value) {
        return;
    }

    processandoAcaoDoEspaco.value = true;

    router.visit(url, {
        method: metodo,
        preserveScroll: true,
        onFinish: () => {
            processandoAcaoDoEspaco.value = false;
            aoFinalizar();
        },
    });
}

function confirmarRemocaoDoMembro() {
    if (!membroParaRemover.value) {
        return;
    }

    executarAcaoDoEspaco('delete', membroParaRemover.value.url_remover, () => {
        membroParaRemover.value = null;
    });
}

function confirmarSaidaDoEspaco() {
    executarAcaoDoEspaco('post', props.espaco.url_sair, () => {
        confirmacaoSairAberta.value = false;
    });
}

function confirmarArquivamentoDoEspaco() {
    executarAcaoDoEspaco('patch', props.espaco.url_arquivar, () => {
        confirmacaoArquivarAberta.value = false;
    });
}
</script>

<template>
    <Head :title="`${espaco.nome} · ${rotuloPeriodo}`" />

    <AutenticadoLayout titulo="Compartilhados">
        <div class="space-y-6 p-6 lg:p-8">
            <CabecalhoSecao
                tamanho="pagina"
                icone="group"
                :titulo="espaco.nome"
                subtitulo="Somente lançamentos deste espaço aparecem aqui. Seus dados pessoais continuam privados."
            >
                <template #acoes>
                    <BotaoAdicionarInline
                        v-if="usuarioEhAdministrador"
                        rotulo="Convidar"
                        icone="person_add"
                        @click="modalConviteAberto = true"
                    />
                    <BotaoAdicionarInline rotulo="Novo lançamento" @click="abrirNovoLancamento" />
                    <BotaoIcone
                        v-if="usuarioEhAdministrador"
                        icone="edit"
                        rotulo="Renomear espaço"
                        @click="modalRenomearAberto = true"
                    />
                    <BotaoIcone
                        v-if="usuarioEhAdministrador"
                        icone="archive"
                        rotulo="Arquivar espaço"
                        @click="confirmacaoArquivarAberta = true"
                    />
                    <BotaoIcone icone="logout" rotulo="Sair do espaço" variante="perigo" @click="confirmacaoSairAberta = true" />
                </template>
            </CabecalhoSecao>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <CardIndicador
                    rotulo="Total do mês"
                    :valor="resumo.total"
                    :descricao="`${resumo.quantidade_total ?? 0} lançamento(s)`"
                    icone="receipt_long"
                    variante="zinc"
                />
                <CardIndicador
                    rotulo="Pago"
                    :valor="resumo.pago"
                    :descricao="`${resumo.quantidade_pagos ?? 0} pago(s)`"
                    :percentual="resumo.percentual_pago ?? 0"
                    rotulo-percentual="quitado"
                    icone="check_circle"
                    destacado
                />
                <CardIndicador
                    rotulo="A vencer"
                    :valor="resumo.a_vencer"
                    :descricao="`${resumo.quantidade_a_vencer ?? 0} pendente(s)`"
                    icone="schedule"
                    variante="amber"
                />
                <CardIndicador
                    rotulo="Vencido"
                    :valor="resumo.vencido"
                    :descricao="`${resumo.quantidade_vencidos ?? 0} em atraso`"
                    icone="error"
                    variante="red"
                />
            </div>

            <div v-if="resumo.por_membro?.length" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <CardResumoMembro
                    v-for="resumoDoMembro in resumo.por_membro"
                    :key="resumoDoMembro.id_usuario ?? 'sem-responsavel'"
                    :resumo="resumoDoMembro"
                />
            </div>

            <section class="ng-card overflow-hidden rounded-2xl">
                <div class="space-y-4 border-b border-ng-line p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <GrupoSegmentado
                            :model-value="abaAtiva"
                            :opcoes="opcoesAba"
                            rotulo="Seções do espaço"
                            @update:model-value="selecionarAba"
                        />
                        <NavegadorPeriodo
                            v-if="abaAtiva === 'lancamentos'"
                            :rotulo="rotuloPeriodo"
                            rotulo-anterior="Mês anterior"
                            rotulo-proximo="Próximo mês"
                            @anterior="abrirMesAdjacente(-1)"
                            @proximo="abrirMesAdjacente(1)"
                        />
                    </div>

                    <GrupoSegmentado
                        v-if="abaAtiva === 'lancamentos'"
                        :model-value="filtros.status"
                        :opcoes="OPCOES_STATUS"
                        rotulo="Filtrar por status"
                        separado
                        @update:model-value="filtrarPorStatus"
                    />
                </div>

                <ListaLancamentos
                    v-if="abaAtiva === 'lancamentos'"
                    :itens="lancamentosExibidos"
                    :mensagem-vazia="mensagemListaVazia"
                    sem-moldura
                >
                    <template #item="{ item }">
                        <LinhaLancamentoCompartilhado :lancamento="item.original">
                            <template #acoes>
                                <BotaoIcone
                                    v-if="item.original.status === 'pendente'"
                                    icone="check_circle"
                                    rotulo="Marcar como pago"
                                    @click="darBaixaNoLancamento(item.original)"
                                />
                                <BotaoIcone
                                    v-if="item.original.esta_pago && item.original.eh_responsavel_usuario_atual"
                                    icone="undo"
                                    rotulo="Estornar baixa"
                                    @click="estornarBaixaDoLancamento(item.original)"
                                />
                                <BotaoIcone
                                    v-if="item.original.pode_editar && item.original.status === 'pendente'"
                                    icone="edit"
                                    rotulo="Editar"
                                    @click="abrirEdicaoDoLancamento(item.original)"
                                />
                                <BotaoIcone
                                    v-if="item.original.pode_excluir && !item.original.esta_pago"
                                    icone="delete"
                                    rotulo="Excluir"
                                    variante="perigo"
                                    @click="lancamentoParaExcluir = item.original"
                                />
                            </template>
                        </LinhaLancamentoCompartilhado>
                    </template>
                </ListaLancamentos>

                <ListaMembrosEspaco
                    v-else-if="abaAtiva === 'membros'"
                    :membros="membros"
                    :pode-gerenciar="usuarioEhAdministrador"
                    @remover="membroParaRemover = $event"
                />

                <HistoricoAtividadesEspaco v-else :atividades="historicoCarregado" />
            </section>
        </div>

        <FormularioLancamentoCompartilhadoModal
            :aberto="formularioLancamentoAberto"
            :lancamento="lancamentoEmEdicao"
            :membros="membros"
            :frequencias="frequencias"
            :ano="ano"
            :mes="mes"
            :url-criar="urls.criarLancamento"
            @fechar="fecharFormularioLancamento"
        />

        <ExclusaoLancamentoCompartilhadoModal
            :aberto="Boolean(lancamentoParaExcluir)"
            :lancamento="lancamentoParaExcluir"
            @fechar="lancamentoParaExcluir = null"
        />

        <FluxoBaixaLancamentoCompartilhado ref="fluxoBaixa" :nome-espaco="espaco.nome" :ano="ano" :mes="mes" />

        <ConviteMembroModal
            v-if="usuarioEhAdministrador"
            :aberto="modalConviteAberto"
            :url-enviar-convite="urls.enviarConvite"
            :convites-pendentes="convitesPendentes"
            @fechar="modalConviteAberto = false"
        />

        <FormularioEspacoCompartilhadoModal
            :aberto="modalRenomearAberto"
            :espaco="espaco"
            @fechar="modalRenomearAberto = false"
        />

        <ModalNotificacao
            :aberto="Boolean(membroParaRemover)"
            titulo="Remover membro"
            :mensagem="mensagemRemoverMembro"
            texto-confirmar="Remover"
            perigo
            :processando="processandoAcaoDoEspaco"
            @confirmar="confirmarRemocaoDoMembro"
            @cancelar="membroParaRemover = null"
        />

        <ModalNotificacao
            :aberto="confirmacaoSairAberta"
            titulo="Sair do espaço"
            :mensagem="mensagemSairDoEspaco"
            texto-confirmar="Sair"
            perigo
            :processando="processandoAcaoDoEspaco"
            @confirmar="confirmarSaidaDoEspaco"
            @cancelar="confirmacaoSairAberta = false"
        />

        <ModalNotificacao
            :aberto="confirmacaoArquivarAberta"
            titulo="Arquivar espaço"
            :mensagem="mensagemArquivarEspaco"
            texto-confirmar="Arquivar"
            perigo
            :processando="processandoAcaoDoEspaco"
            @confirmar="confirmarArquivamentoDoEspaco"
            @cancelar="confirmacaoArquivarAberta = false"
        />
    </AutenticadoLayout>
</template>
