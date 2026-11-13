<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import BadgeTotalDistribuicao from '@/Components/Ui/BadgeTotalDistribuicao.vue';
import BarraDistribuicaoSegmentada from '@/Components/Ui/BarraDistribuicaoSegmentada.vue';
import ItemDistribuicaoPlanoFinanceiro from '@/Components/PlanoFinanceiro/ItemDistribuicaoPlanoFinanceiro.vue';
import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';
import {
    PERCENTUAL_TOTAL_DO_PLANO,
    converterItemPlanoParaRascunho,
    converterRascunhoParaItemCalculavel,
    criarRascunhoItemPlano,
    escolherProximaCorLivre,
    montarDadosEnvioItemPlano,
    montarSegmentosDistribuicao,
    somarPercentualDoPlano,
    somarValorFixoDoPlano,
} from '@/Helpers/planoFinanceiro';

const PREFIXO_ERRO_ITENS = 'itens.';
const LIMITE_ITENS_DO_PLANO = 20;

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    plano: {
        type: Object,
        default: null,
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

const emit = defineEmits(['fechar']);

const editando = computed(() => Boolean(props.plano));

const formulario = useForm({
    nome: '',
    descricao: '',
    itens: [],
});

const opcoesContasBancarias = computed(() => props.contasBancarias.map((conta) => ({
    valor: conta.id,
    rotulo: conta.nome,
})));

const opcoesObjetivos = computed(() => props.objetivos.map((objetivo) => ({
    valor: objetivo.id,
    rotulo: objetivo.descricao,
})));

const opcoesAlocacoes = computed(() => props.alocacoes.map((alocacao) => ({
    valor: alocacao.id,
    rotulo: alocacao.nome,
})));

const mapaCoresHexadecimais = computed(() => Object.fromEntries(
    props.coresItem.map((opcao) => [opcao.valor, opcao.cor]),
));

const itensCalculaveis = computed(() => formulario.itens.map(
    (rascunho) => converterRascunhoParaItemCalculavel(rascunho, mapaCoresHexadecimais.value),
));

const percentualTotal = computed(() => somarPercentualDoPlano(itensCalculaveis.value));
const valorFixoTotal = computed(() => somarValorFixoDoPlano(itensCalculaveis.value));
const segmentosDistribuicao = computed(() => montarSegmentosDistribuicao(itensCalculaveis.value));
const percentualExcedeLimite = computed(() => percentualTotal.value > PERCENTUAL_TOTAL_DO_PLANO);
const atingiuLimiteDeItens = computed(() => formulario.itens.length >= LIMITE_ITENS_DO_PLANO);

const desabilitarSalvar = computed(() => !formulario.nome.trim()
    || formulario.itens.length === 0
    || percentualExcedeLimite.value);

const errosItensPorIndice = computed(() => Object.entries(formulario.errors)
    .filter(([campo]) => campo.startsWith(PREFIXO_ERRO_ITENS))
    .reduce((errosPorIndice, [campo, mensagem]) => {
        const [indice, ...partesCampo] = campo.slice(PREFIXO_ERRO_ITENS.length).split('.');
        errosPorIndice[indice] = { ...(errosPorIndice[indice] ?? {}), [partesCampo.join('.')]: mensagem };

        return errosPorIndice;
    }, {}));

function contaPadraoParaDestino() {
    return props.contasBancarias[0]?.id ?? null;
}

function criarItemComProximaCor() {
    const coresEmUso = formulario.itens.map((item) => item.cor);

    return criarRascunhoItemPlano({
        cor: escolherProximaCorLivre(props.coresItem, coresEmUso),
        idContaBancariaDestinoPadrao: contaPadraoParaDestino(),
    });
}

function reiniciarFormularioPlanoFinanceiro() {
    formulario.clearErrors();
    formulario.nome = props.plano?.nome ?? '';
    formulario.descricao = props.plano?.descricao ?? '';
    formulario.itens = props.plano
        ? props.plano.itens.map(converterItemPlanoParaRascunho)
        : [];

    if (!props.plano) {
        formulario.itens.push(criarItemComProximaCor());
    }
}

watch(
    () => [props.aberto, props.plano],
    () => {
        if (props.aberto) {
            reiniciarFormularioPlanoFinanceiro();
        }
    },
    { immediate: true },
);

function adicionarItemNoPlano() {
    if (atingiuLimiteDeItens.value) {
        return;
    }

    formulario.itens.push(criarItemComProximaCor());
}

function removerItemDoPlano(indice) {
    formulario.itens.splice(indice, 1);
    formulario.clearErrors();
}

function fecharFormularioPlanoFinanceiro() {
    emit('fechar');
}

function montarDadosEnvioPlanoFinanceiro(dados) {
    return {
        nome: dados.nome,
        descricao: dados.descricao || null,
        itens: dados.itens.map(montarDadosEnvioItemPlano),
    };
}

function salvarPlanoFinanceiro() {
    if (desabilitarSalvar.value) {
        return;
    }

    const opcoes = {
        preserveScroll: true,
        onSuccess: () => fecharFormularioPlanoFinanceiro(),
    };

    const requisicao = formulario.transform(montarDadosEnvioPlanoFinanceiro);

    if (editando.value) {
        requisicao.put(props.plano.url_atualizar, opcoes);
        return;
    }

    requisicao.post(props.urlCriar, opcoes);
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="2xl"
        icone="donut_large"
        :titulo="editando ? 'Editar plano financeiro' : 'Novo plano financeiro'"
        subtitulo="Defina como 100% da sua renda será distribuída."
        :texto-confirmar="editando ? 'Salvar alterações' : 'Salvar plano'"
        :processando="formulario.processing"
        :desabilitar-confirmar="desabilitarSalvar"
        @fechar="fecharFormularioPlanoFinanceiro"
        @confirmar="salvarPlanoFinanceiro"
    >
        <div class="space-y-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <CampoTexto
                    id="plano-financeiro-nome"
                    v-model="formulario.nome"
                    rotulo="Nome do plano"
                    placeholder="Ex.: Essencial"
                    maxlength="120"
                    obrigatorio
                    :erro="formulario.errors.nome"
                />
                <CampoTexto
                    id="plano-financeiro-descricao"
                    v-model="formulario.descricao"
                    rotulo="Descrição curta"
                    placeholder="Ex.: Regra 50/30/20"
                    maxlength="255"
                    :erro="formulario.errors.descricao"
                />
            </div>

            <section class="ng-card-muted space-y-3 p-4">
                <header class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-ng-ink-muted">Distribuição total</p>
                    <BadgeTotalDistribuicao :percentual="percentualTotal" :percentual-meta="PERCENTUAL_TOTAL_DO_PLANO" />
                </header>
                <BarraDistribuicaoSegmentada :segmentos="segmentosDistribuicao" altura="h-2.5" />
                <p v-if="valorFixoTotal > 0" class="font-mono text-[11px] text-ng-ink-subtle">
                    + R$ {{ formatarNumeroParaMoeda(valorFixoTotal) }} em valores fixos por recebimento
                </p>
            </section>

            <section class="space-y-3">
                <header class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-ng-ink">
                        Categorias de distribuição ({{ formulario.itens.length }})
                    </p>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-ng-brand transition hover:text-ng-brand-hover disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="atingiuLimiteDeItens"
                        @click="adicionarItemNoPlano"
                    >
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        Adicionar categoria
                    </button>
                </header>

                <ItemDistribuicaoPlanoFinanceiro
                    v-for="(item, indice) in formulario.itens"
                    :key="item.chave"
                    v-model:nome="item.nome"
                    v-model:descricao="item.descricao"
                    v-model:cor="item.cor"
                    v-model:tipo-calculo="item.tipo_calculo"
                    v-model:valor-percentual="item.valor_percentual"
                    v-model:valor-fixo="item.valor_fixo"
                    v-model:separar-do-saldo="item.separar_do_saldo"
                    v-model:id-conta-bancaria-destino="item.id_conta_bancaria_destino"
                    v-model:id-objetivo="item.id_objetivo"
                    v-model:id-alocacao="item.id_alocacao"
                    :id-prefixo="`plano-${item.chave}`"
                    :cores="coresItem"
                    :tipos-calculo="tiposCalculo"
                    :opcoes-contas-bancarias="opcoesContasBancarias"
                    :opcoes-objetivos="opcoesObjetivos"
                    :opcoes-alocacoes="opcoesAlocacoes"
                    :url-alocacoes="urlAlocacoes"
                    :erros="errosItensPorIndice[indice] ?? {}"
                    :pode-remover="formulario.itens.length > 1"
                    @remover="removerItemDoPlano(indice)"
                />

                <p v-if="formulario.errors.itens" class="text-xs text-red-500">{{ formulario.errors.itens }}</p>
                <p v-else-if="percentualExcedeLimite" class="text-xs text-red-500">
                    A soma dos percentuais não pode ultrapassar 100%.
                </p>
            </section>
        </div>
    </Modal>
</template>
