<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import CampoAreaTexto from '@/Components/Formulario/CampoAreaTexto.vue';
import CampoMoeda from '@/Components/Formulario/CampoMoeda.vue';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import LinhaAlternancia from '@/Components/Formulario/LinhaAlternancia.vue';
import LinhaCampoNumero from '@/Components/Formulario/LinhaCampoNumero.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';
import ListaParcelasPrevistas from '@/Components/Ui/ListaParcelasPrevistas.vue';
import LancamentoVisual from '@/Components/Lancamento/LancamentoVisual.vue';
import TerminoRecorrencia from '@/Components/Lancamento/TerminoRecorrencia.vue';
import { varianteSituacaoLancamento } from '@/Helpers/badge';
import { moedaParaNumero } from '@/Helpers/mascaraMoeda';
import { descreverParcelaPrincipal, montarParcelasPrevistas } from '@/Helpers/parcelamento';
import {
    dataIsoEhValida,
    formatarDataIsoParaBrasileira,
    formatarMesAbreviado,
    ultimoDiaDoMes,
} from '@/Helpers/periodo';
import {
    MINIMO_OCORRENCIAS,
    TERMINO_ATE_MES,
    TERMINO_POR_QUANTIDADE,
    TERMINO_SEM_TERMINO,
    limitarQuantidadeOcorrencias,
} from '@/Helpers/recorrencia';

const FORMA_CONTA_BANCARIA = 'conta_bancaria';
const FORMA_CARTAO_CREDITO = 'cartao_credito';
const PREFIXO_ORIGEM_CONTA = 'conta';
const PREFIXO_ORIGEM_CARTAO = 'cartao';
const SITUACOES_EFETIVADAS = ['pago', 'recebido'];
const MAXIMO_PARCELAS = 48;

const OPCOES_TIPO = [
    { valor: 'despesa', rotulo: 'Despesa (Saída)', icone: 'north_east', variante: 'red' },
    { valor: 'receita', rotulo: 'Receita (Entrada)', icone: 'south_west', variante: 'brand' },
];

const OPCOES_MODO_REPETICAO = [
    { valor: 'recorrente', rotulo: 'Recorrente', icone: 'autorenew' },
    { valor: 'parcelado', rotulo: 'Parcelado', icone: 'repeat' },
];

const props = defineProps({
    aberto: { type: Boolean, default: false },
    lancamento: { type: Object, default: null },
    ano: { type: Number, required: true },
    mes: { type: Number, required: true },
    contasBancarias: { type: Array, default: () => [] },
    cartoesCredito: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
    situacoes: { type: Array, default: () => [] },
    frequencias: { type: Array, default: () => [] },
    urlCriar: { type: String, required: true },
});

const emit = defineEmits(['fechar']);

const editando = computed(() => Boolean(props.lancamento));
const modalOrcamentoAberto = ref(false);
const mensagemOrcamento = ref('');
const situacaoEfetivadaOriginal = ref('pago');
const situacaoNaoEfetivadaOriginal = ref('pendente');
const repeticaoAtiva = ref(false);
const modoRepeticao = ref('recorrente');
const modoTerminoRecorrencia = ref(TERMINO_SEM_TERMINO);
const quantidadeOcorrencias = ref(MINIMO_OCORRENCIAS);
const mesFinalRecorrencia = ref('');

function dataPadraoDoMes() {
    const hoje = new Date();
    if (hoje.getFullYear() === props.ano && hoje.getMonth() + 1 === props.mes) {
        return hoje.toISOString().slice(0, 10);
    }

    return `${props.ano}-${String(props.mes).padStart(2, '0')}-01`;
}

const formulario = useForm({
    descricao: '',
    valor: '0,00',
    data_vencimento: dataPadraoDoMes(),
    tipo: 'despesa',
    forma_pagamento: FORMA_CONTA_BANCARIA,
    id_conta_bancaria: null,
    id_cartao_credito: null,
    situacao: 'pendente',
    id_categoria_principal: null,
    id_subcategoria: null,
    observacao: '',
    recorrente: false,
    frequencia_recorrencia: 'mensal',
    total_parcelas: 1,
    confirmar_ultrapassagem_orcamento: false,
});

watch(
    () => formulario.errors.confirmar_ultrapassagem_orcamento,
    (mensagem) => {
        if (!mensagem) {
            return;
        }

        mensagemOrcamento.value = mensagem;
        modalOrcamentoAberto.value = true;
    },
);

const categoriasPai = computed(() => {
    const tipoCategoria = formulario.tipo === 'receita' ? 'entrada' : 'saida';

    return props.categorias.filter((categoria) => categoria.tipo === tipoCategoria && categoria.arquivada !== 'S');
});

const categoriaPrincipalSelecionada = computed(() =>
    categoriasPai.value.find((categoria) => Number(categoria.id) === Number(formulario.id_categoria_principal)) ?? null,
);

const subcategorias = computed(() =>
    (categoriaPrincipalSelecionada.value?.subcategorias ?? []).filter((subcategoria) => subcategoria.arquivada !== 'S'),
);

const opcoesCategoria = computed(() =>
    categoriasPai.value.map((categoria) => ({ valor: categoria.id, rotulo: categoria.nome })),
);

const opcoesSubcategoria = computed(() =>
    subcategorias.value.map((subcategoria) => ({ valor: subcategoria.id, rotulo: subcategoria.nome })),
);

const nomeCategoriaSelecionada = computed(() => {
    const subcategoria = subcategorias.value.find((item) => Number(item.id) === Number(formulario.id_subcategoria));

    return subcategoria?.nome ?? categoriaPrincipalSelecionada.value?.nome ?? '';
});

const opcoesOrigem = computed(() => [
    ...props.contasBancarias.map((conta) => ({
        valor: `${PREFIXO_ORIGEM_CONTA}:${conta.id}`,
        rotulo: conta.nome,
        grupo: 'Contas bancárias',
    })),
    ...props.cartoesCredito.map((cartao) => ({
        valor: `${PREFIXO_ORIGEM_CARTAO}:${cartao.id}`,
        rotulo: cartao.nome,
        grupo: 'Cartões de crédito',
    })),
]);

const origemSelecionada = computed({
    get() {
        if (formulario.forma_pagamento === FORMA_CARTAO_CREDITO && formulario.id_cartao_credito) {
            return `${PREFIXO_ORIGEM_CARTAO}:${formulario.id_cartao_credito}`;
        }

        if (formulario.id_conta_bancaria) {
            return `${PREFIXO_ORIGEM_CONTA}:${formulario.id_conta_bancaria}`;
        }

        return null;
    },
    set(valor) {
        selecionarOrigemLancamento(valor);
    },
});

const nomeOrigemSelecionada = computed(() =>
    opcoesOrigem.value.find((opcao) => opcao.valor === origemSelecionada.value)?.rotulo ?? '',
);

const erroOrigem = computed(() =>
    formulario.errors.id_conta_bancaria || formulario.errors.id_cartao_credito || formulario.errors.forma_pagamento || '',
);

const lancamentoEfetivado = computed({
    get() {
        return SITUACOES_EFETIVADAS.includes(formulario.situacao);
    },
    set(efetivado) {
        formulario.situacao = efetivado ? situacaoEfetivadaOriginal.value : situacaoNaoEfetivadaOriginal.value;
    },
});

const rotuloSituacaoAtual = computed(() =>
    props.situacoes.find((opcao) => opcao.valor === formulario.situacao)?.rotulo ?? '',
);

const mostrarRepeticao = computed(() => !editando.value || repeticaoAtiva.value);

const criandoParcelado = computed(
    () => !editando.value && repeticaoAtiva.value && modoRepeticao.value === 'parcelado',
);

const criandoRecorrente = computed(
    () => !editando.value && repeticaoAtiva.value && modoRepeticao.value === 'recorrente',
);

const parcelasPrevistas = computed(() => {
    const valorTotal = moedaParaNumero(formulario.valor);

    if (!criandoParcelado.value || valorTotal <= 0) {
        return [];
    }

    const quantidadeParcelas = Math.min(MAXIMO_PARCELAS, Math.max(2, Number(formulario.total_parcelas) || 2));
    const dataPrimeiraParcela = dataIsoEhValida(formulario.data_vencimento) ? formulario.data_vencimento : null;

    return montarParcelasPrevistas(valorTotal, quantidadeParcelas, dataPrimeiraParcela);
});

const descricaoValorParcelado = computed(() => descreverParcelaPrincipal(parcelasPrevistas.value));

function descreverTerminoDaRecorrencia() {
    if (editando.value) {
        return props.lancamento?.recorrencia_ate ? `até ${formatarMesAbreviado(props.lancamento.recorrencia_ate)}` : '';
    }

    if (modoTerminoRecorrencia.value === TERMINO_POR_QUANTIDADE) {
        return `${limitarQuantidadeOcorrencias(quantidadeOcorrencias.value)}x`;
    }

    if (modoTerminoRecorrencia.value === TERMINO_ATE_MES && mesFinalRecorrencia.value) {
        return `até ${formatarMesAbreviado(mesFinalRecorrencia.value)}`;
    }

    return '';
}

const etiquetaRepeticao = computed(() => {
    if (!repeticaoAtiva.value) {
        return '';
    }

    if (modoRepeticao.value === 'recorrente') {
        const rotuloFrequencia = props.frequencias
            .find((opcao) => opcao.valor === formulario.frequencia_recorrencia)?.rotulo ?? '';

        return [rotuloFrequencia, descreverTerminoDaRecorrencia()].filter(Boolean).join(' · ');
    }

    if (editando.value && props.lancamento?.parcela_atual) {
        return `${props.lancamento.parcela_atual}/${props.lancamento.total_parcelas}`;
    }

    return descricaoValorParcelado.value || `${formulario.total_parcelas}x`;
});

const dicaParcelas = computed(() => {
    if (editando.value && props.lancamento?.parcela_atual) {
        return `Parcela ${props.lancamento.parcela_atual} de ${props.lancamento.total_parcelas}`;
    }

    return 'A 1ª parcela cai no mês da data e as demais nos meses seguintes';
});

const atalhosValor = computed(() => (props.lancamento?.valor_previsto
    ? [{ rotulo: 'sugerir', valor: props.lancamento.valor_previsto }]
    : []));

const varianteDoTipo = computed(() => (formulario.tipo === 'receita' ? 'brand' : 'red'));
const iconeDoTipo = computed(() => (formulario.tipo === 'receita' ? 'south_west' : 'north_east'));

function selecionarOrigemLancamento(valor) {
    const [prefixo, id] = String(valor ?? '').split(':');

    if (prefixo === PREFIXO_ORIGEM_CARTAO) {
        formulario.forma_pagamento = FORMA_CARTAO_CREDITO;
        formulario.id_cartao_credito = Number(id);
        formulario.id_conta_bancaria = null;
        return;
    }

    formulario.forma_pagamento = FORMA_CONTA_BANCARIA;
    formulario.id_conta_bancaria = id ? Number(id) : null;
    formulario.id_cartao_credito = null;
}

function origemPadraoParaNovoLancamento() {
    const contaPadrao = props.contasBancarias.find((conta) => conta.padrao_desconto === 'S') ?? props.contasBancarias[0];

    if (contaPadrao) {
        return `${PREFIXO_ORIGEM_CONTA}:${contaPadrao.id}`;
    }

    const cartaoPadrao = props.cartoesCredito.find((cartao) => cartao.padrao === 'S') ?? props.cartoesCredito[0];

    return cartaoPadrao ? `${PREFIXO_ORIGEM_CARTAO}:${cartaoPadrao.id}` : null;
}

function registrarSituacoesOriginais(situacao) {
    const estaEfetivada = SITUACOES_EFETIVADAS.includes(situacao);

    situacaoEfetivadaOriginal.value = estaEfetivada ? situacao : 'pago';
    situacaoNaoEfetivadaOriginal.value = estaEfetivada ? 'pendente' : situacao;
}

function preencherComLancamentoExistente(lancamento) {
    formulario.descricao = lancamento.descricao ?? '';
    formulario.valor = lancamento.valor ?? '0,00';
    formulario.data_vencimento = lancamento.data_vencimento ?? dataPadraoDoMes();
    formulario.tipo = lancamento.tipo ?? 'despesa';
    formulario.forma_pagamento = lancamento.forma_pagamento ?? FORMA_CONTA_BANCARIA;
    formulario.id_conta_bancaria = lancamento.id_conta_bancaria ?? null;
    formulario.id_cartao_credito = lancamento.id_cartao_credito ?? null;
    formulario.situacao = lancamento.situacao ?? 'pendente';
    formulario.id_categoria_principal = lancamento.id_categoria_principal ?? null;
    formulario.id_subcategoria = lancamento.id_subcategoria ?? null;
    formulario.observacao = lancamento.observacao ?? '';
    formulario.recorrente = Boolean(lancamento.eh_recorrente);
    formulario.frequencia_recorrencia = lancamento.frequencia_recorrencia ?? 'mensal';
    formulario.total_parcelas = lancamento.total_parcelas > 1 ? lancamento.total_parcelas : 1;
}

function preencherNovoLancamento() {
    formulario.reset();
    formulario.data_vencimento = dataPadraoDoMes();
    selecionarOrigemLancamento(origemPadraoParaNovoLancamento());
}

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.confirmar_ultrapassagem_orcamento = false;
    modalOrcamentoAberto.value = false;
    mensagemOrcamento.value = '';

    if (props.lancamento) {
        preencherComLancamentoExistente(props.lancamento);
    } else {
        preencherNovoLancamento();
    }

    registrarSituacoesOriginais(formulario.situacao);
    repeticaoAtiva.value = formulario.recorrente || Number(formulario.total_parcelas) > 1;
    modoRepeticao.value = formulario.recorrente || !repeticaoAtiva.value ? 'recorrente' : 'parcelado';
    reiniciarTerminoRecorrencia();
}

function reiniciarTerminoRecorrencia() {
    modoTerminoRecorrencia.value = TERMINO_SEM_TERMINO;
    quantidadeOcorrencias.value = MINIMO_OCORRENCIAS;
    mesFinalRecorrencia.value = '';
}

function montarDadosTerminoRecorrencia() {
    if (modoTerminoRecorrencia.value === TERMINO_POR_QUANTIDADE) {
        return { quantidade_ocorrencias: limitarQuantidadeOcorrencias(quantidadeOcorrencias.value) };
    }

    if (modoTerminoRecorrencia.value === TERMINO_ATE_MES) {
        return { recorrencia_ate: ultimoDiaDoMes(mesFinalRecorrencia.value) };
    }

    return {};
}

watch(
    () => [props.aberto, props.lancamento],
    () => {
        if (props.aberto) {
            reiniciarFormulario();
        }
    },
);

watch(
    () => formulario.tipo,
    () => {
        if (!props.aberto || categoriaPrincipalSelecionada.value) {
            return;
        }

        formulario.id_categoria_principal = null;
        formulario.id_subcategoria = null;
    },
);

watch(
    () => formulario.id_categoria_principal,
    () => {
        const subcategoriaValida = subcategorias.value.some((item) => Number(item.id) === Number(formulario.id_subcategoria));

        if (!subcategoriaValida) {
            formulario.id_subcategoria = null;
        }
    },
);

watch([repeticaoAtiva, modoRepeticao], sincronizarRepeticaoNoFormulario);

function sincronizarRepeticaoNoFormulario() {
    if (editando.value) {
        return;
    }

    if (!repeticaoAtiva.value) {
        formulario.recorrente = false;
        formulario.total_parcelas = 1;
        return;
    }

    if (modoRepeticao.value === 'recorrente') {
        formulario.recorrente = true;
        formulario.total_parcelas = 1;
        return;
    }

    formulario.recorrente = false;
    formulario.total_parcelas = Math.max(2, Number(formulario.total_parcelas) || 2);
}

function fechar() {
    emit('fechar');
}

function montarDadosEnvio() {
    const dados = { ...formulario.data() };

    dados.id_categoria = dados.id_subcategoria || dados.id_categoria_principal || null;
    delete dados.id_categoria_principal;
    delete dados.id_subcategoria;

    if (dados.recorrente) {
        Object.assign(dados, montarDadosTerminoRecorrencia());
    } else {
        delete dados.frequencia_recorrencia;
    }

    if (Number(dados.total_parcelas) <= 1) {
        dados.total_parcelas = 1;
    }

    if (editando.value) {
        delete dados.recorrente;
        delete dados.frequencia_recorrencia;
        delete dados.total_parcelas;
        delete dados.quantidade_ocorrencias;
        delete dados.recorrencia_ate;
    }

    return dados;
}

function terminoDaRecorrenciaEstaIncompleto() {
    return criandoRecorrente.value
        && modoTerminoRecorrencia.value === TERMINO_ATE_MES
        && !ultimoDiaDoMes(mesFinalRecorrencia.value);
}

function enviarLancamento() {
    if (terminoDaRecorrenciaEstaIncompleto()) {
        formulario.setError('recorrencia_ate', 'Escolha o mês do último lançamento.');
        return;
    }

    const dados = montarDadosEnvio();
    const opcoes = {
        preserveScroll: true,
        onSuccess: () => {
            modalOrcamentoAberto.value = false;
            fechar();
        },
    };

    if (editando.value) {
        formulario.transform(() => dados).put(props.lancamento.url_atualizar, opcoes);
        return;
    }

    formulario.transform(() => dados).post(props.urlCriar, opcoes);
}

function salvarLancamento() {
    formulario.confirmar_ultrapassagem_orcamento = false;
    enviarLancamento();
}

function cancelarUltrapassagemOrcamento() {
    modalOrcamentoAberto.value = false;
    formulario.confirmar_ultrapassagem_orcamento = false;
    formulario.clearErrors('confirmar_ultrapassagem_orcamento');
}

function confirmarUltrapassagemOrcamento() {
    formulario.confirmar_ultrapassagem_orcamento = true;
    enviarLancamento();
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        :icone="iconeDoTipo"
        :variante-icone="varianteDoTipo"
        :titulo="editando ? 'Editar lançamento' : 'Novo lançamento'"
        subtitulo="Controle receitas e despesas vinculadas a contas bancárias e cartões."
        :texto-confirmar="editando ? 'Salvar alterações' : 'Salvar lançamento'"
        :processando="formulario.processing"
        @confirmar="salvarLancamento"
        @fechar="fechar"
    >
        <div class="space-y-5">
            <div class="space-y-2">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-ng-ink-subtle">Resumo</span>
                <LancamentoVisual
                    :tipo="formulario.tipo"
                    :descricao="formulario.descricao"
                    :valor="formulario.valor"
                    :data="formatarDataIsoParaBrasileira(formulario.data_vencimento)"
                    :categoria="nomeCategoriaSelecionada"
                    :etiqueta="etiquetaRepeticao"
                    :etiqueta-icone="modoRepeticao === 'recorrente' ? 'autorenew' : 'repeat'"
                    :situacao-rotulo="rotuloSituacaoAtual"
                    :situacao-variante="varianteSituacaoLancamento(formulario.situacao)"
                    :situacao-icone="lancamentoEfetivado ? 'check_circle' : 'schedule'"
                    :origem="nomeOrigemSelecionada"
                />
            </div>

            <GrupoSegmentado v-model="formulario.tipo" :opcoes="OPCOES_TIPO" rotulo="Tipo do lançamento" expandido />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <CampoTexto
                    id="lancamento-descricao"
                    v-model="formulario.descricao"
                    class="sm:col-span-2"
                    rotulo="Descrição do lançamento"
                    obrigatorio
                    :maxlength="255"
                    :erro="formulario.errors.descricao"
                />
                <CampoMoeda
                    id="lancamento-valor"
                    v-model="formulario.valor"
                    :rotulo="criandoParcelado ? 'Valor total' : 'Valor'"
                    obrigatorio
                    :variante="varianteDoTipo"
                    :atalhos="atalhosValor"
                    :erro="formulario.errors.valor"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <CampoSelecao
                    id="lancamento-categoria"
                    v-model="formulario.id_categoria_principal"
                    rotulo="Categoria"
                    placeholder="Selecione"
                    placeholder-selecionavel
                    :opcoes="opcoesCategoria"
                    :erro="formulario.errors.id_categoria"
                />
                <CampoSelecao
                    id="lancamento-subcategoria"
                    v-model="formulario.id_subcategoria"
                    rotulo="Subcategoria"
                    placeholder="Selecione"
                    placeholder-selecionavel
                    :opcoes="opcoesSubcategoria"
                    :desabilitado="!formulario.id_categoria_principal"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <CampoSelecao
                    id="lancamento-origem"
                    v-model="origemSelecionada"
                    rotulo="Conta ou cartão vinculado"
                    placeholder="Selecione"
                    obrigatorio
                    :opcoes="opcoesOrigem"
                    :erro="erroOrigem"
                />
                <CampoTexto
                    id="lancamento-data"
                    v-model="formulario.data_vencimento"
                    tipo="date"
                    rotulo="Data do lançamento"
                    obrigatorio
                    :erro="formulario.errors.data_vencimento"
                />
            </div>

            <div class="space-y-3">
                <LinhaAlternancia
                    v-model="lancamentoEfetivado"
                    icone="check"
                    titulo="Lançamento já foi pago"
                    :valor-ativo="true"
                    :valor-inativo="false"
                />

                <template v-if="mostrarRepeticao">
                    <LinhaAlternancia
                        v-model="repeticaoAtiva"
                        icone="autorenew"
                        titulo="Lançamento recorrente ou parcelado"
                        descricao="Repete periodicamente ou possui parcelas"
                        :valor-ativo="true"
                        :valor-inativo="false"
                        :desabilitado="editando"
                    />

                    <template v-if="repeticaoAtiva">
                        <GrupoSegmentado
                            v-if="!editando"
                            v-model="modoRepeticao"
                            :opcoes="OPCOES_MODO_REPETICAO"
                            rotulo="Tipo de repetição"
                            expandido
                        />

                        <template v-if="modoRepeticao === 'recorrente'">
                            <CampoSelecao
                                id="lancamento-frequencia"
                                v-model="formulario.frequencia_recorrencia"
                                rotulo="Frequência"
                                :opcoes="frequencias"
                                :desabilitado="editando"
                                :erro="formulario.errors.frequencia_recorrencia"
                            />

                            <TerminoRecorrencia
                                v-if="criandoRecorrente"
                                id="lancamento-termino-recorrencia"
                                v-model:modo="modoTerminoRecorrencia"
                                v-model:quantidade="quantidadeOcorrencias"
                                v-model:mes-final="mesFinalRecorrencia"
                                :data-inicial="formulario.data_vencimento"
                                :frequencia="formulario.frequencia_recorrencia"
                                :erro="formulario.errors.quantidade_ocorrencias || formulario.errors.recorrencia_ate"
                            />
                        </template>

                        <template v-else>
                            <LinhaCampoNumero
                                id="lancamento-parcelas"
                                v-model="formulario.total_parcelas"
                                rotulo="Quantidade de parcelas"
                                :dica="dicaParcelas"
                                :minimo="2"
                                :maximo="MAXIMO_PARCELAS"
                                :desabilitado="editando"
                                :erro="formulario.errors.total_parcelas"
                            />

                            <ListaParcelasPrevistas
                                v-if="criandoParcelado"
                                :parcelas="parcelasPrevistas"
                                :variante="varianteDoTipo"
                            />
                        </template>
                    </template>
                </template>
            </div>

            <CampoAreaTexto
                id="lancamento-observacao"
                v-model="formulario.observacao"
                rotulo="Observação"
                :linhas="2"
                :erro="formulario.errors.observacao"
            />

            <p v-if="formulario.errors.situacao" class="text-xs text-red-500">{{ formulario.errors.situacao }}</p>
            <p v-if="formulario.errors.recorrente" class="text-xs text-red-500">{{ formulario.errors.recorrente }}</p>
        </div>
    </Modal>

    <ModalNotificacao
        :aberto="modalOrcamentoAberto"
        titulo="Orçamento ultrapassado"
        :mensagem="mensagemOrcamento"
        texto-confirmar="Confirmar lançamento"
        texto-cancelar="Revisar"
        :processando="formulario.processing"
        @confirmar="confirmarUltrapassagemOrcamento"
        @cancelar="cancelarUltrapassagemOrcamento"
    />
</template>
