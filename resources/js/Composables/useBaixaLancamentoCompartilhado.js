import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { obterDataDeHojeIso } from '@/Helpers/alocacao';
import { STATUS_LANCAMENTO_COMPARTILHADO_PENDENTE } from '@/Helpers/espacoCompartilhado';

const PROPRIEDADE_DADOS_LANCAMENTO_PESSOAL = 'dadosLancamentoPessoal';

export function useBaixaLancamentoCompartilhado(obterNomeDoEspaco) {
    const pagina = usePage();

    const lancamentoSelecionado = ref(null);
    const confirmacaoBaixaAberta = ref(false);
    const formularioPessoalAberto = ref(false);
    const avisoNaoResponsavelAberto = ref(false);
    const confirmacaoAssumirAberta = ref(false);
    const confirmacaoEstornoAberta = ref(false);
    const carregandoDadosPessoais = ref(false);
    const processandoAcao = ref(false);
    const dadosLancamentoPessoal = ref(null);
    const erroCategoriaAssumir = ref('');

    const valoresIniciaisDoLancamentoPessoal = computed(() => {
        const lancamento = lancamentoSelecionado.value;

        if (!lancamento) {
            return null;
        }

        return {
            tipo: 'despesa',
            valor: lancamento.valor,
            descricao: lancamento.descricao,
            data_vencimento: obterDataDeHojeIso(),
            situacao: 'pago',
            id_categoria_principal: lancamento.id_categoria_principal ?? null,
            id_subcategoria: lancamento.id_subcategoria ?? null,
        };
    });

    const dadosExtrasDaBaixa = computed(() => ({ versao: lancamentoSelecionado.value?.versao ?? null }));

    function iniciarBaixaDoLancamento(lancamento) {
        lancamentoSelecionado.value = lancamento;

        if (!lancamento.eh_responsavel_usuario_atual) {
            avisoNaoResponsavelAberto.value = true;
            return;
        }

        confirmacaoBaixaAberta.value = true;
    }

    function confirmarRegistroNoControlePessoal() {
        if (pagina.props[PROPRIEDADE_DADOS_LANCAMENTO_PESSOAL]) {
            abrirFormularioPessoal();
            return;
        }

        carregandoDadosPessoais.value = true;

        router.reload({
            only: [PROPRIEDADE_DADOS_LANCAMENTO_PESSOAL],
            onSuccess: abrirFormularioPessoal,
            onFinish: () => {
                carregandoDadosPessoais.value = false;
            },
        });
    }

    function abrirFormularioPessoal() {
        dadosLancamentoPessoal.value = pagina.props[PROPRIEDADE_DADOS_LANCAMENTO_PESSOAL] ?? null;
        confirmacaoBaixaAberta.value = false;
        formularioPessoalAberto.value = true;
    }

    function pedirConfirmacaoParaAssumir() {
        avisoNaoResponsavelAberto.value = false;
        erroCategoriaAssumir.value = '';

        if (pagina.props[PROPRIEDADE_DADOS_LANCAMENTO_PESSOAL]) {
            dadosLancamentoPessoal.value = pagina.props[PROPRIEDADE_DADOS_LANCAMENTO_PESSOAL];
            confirmacaoAssumirAberta.value = true;
            return;
        }

        carregandoDadosPessoais.value = true;

        router.reload({
            only: [PROPRIEDADE_DADOS_LANCAMENTO_PESSOAL],
            onSuccess: () => {
                dadosLancamentoPessoal.value = pagina.props[PROPRIEDADE_DADOS_LANCAMENTO_PESSOAL] ?? null;
                confirmacaoAssumirAberta.value = true;
            },
            onFinish: () => {
                carregandoDadosPessoais.value = false;
            },
        });
    }

    function confirmarAssumirLancamento(idCategoria) {
        const lancamento = lancamentoSelecionado.value;

        if (!lancamento || processandoAcao.value) {
            return;
        }

        if (!idCategoria) {
            erroCategoriaAssumir.value = 'Selecione uma das suas categorias para assumir este lançamento.';
            return;
        }

        processandoAcao.value = true;
        erroCategoriaAssumir.value = '';

        router.post(lancamento.url_assumir, {
            versao: lancamento.versao,
            id_categoria: idCategoria,
        }, {
            preserveScroll: true,
            onSuccess: (paginaAtualizada) => {
                processandoAcao.value = false;
                confirmacaoAssumirAberta.value = false;
                continuarBaixaAposAssumir(paginaAtualizada, lancamento.id);
            },
            onError: (erros) => {
                erroCategoriaAssumir.value = erros.id_categoria ?? erros.lancamento ?? '';
            },
            onFinish: () => {
                processandoAcao.value = false;
            },
        });
    }

    function continuarBaixaAposAssumir(paginaAtualizada, idLancamento) {
        const lancamentoAtualizado = (paginaAtualizada.props.lancamentos ?? [])
            .find((lancamento) => lancamento.id === idLancamento);

        if (
            !lancamentoAtualizado
            || !lancamentoAtualizado.eh_responsavel_usuario_atual
            || lancamentoAtualizado.status !== STATUS_LANCAMENTO_COMPARTILHADO_PENDENTE
        ) {
            cancelarFluxo();
            return;
        }

        lancamentoSelecionado.value = lancamentoAtualizado;
        confirmacaoBaixaAberta.value = true;
    }

    function iniciarEstornoDaBaixa(lancamento) {
        lancamentoSelecionado.value = lancamento;
        confirmacaoEstornoAberta.value = true;
    }

    function confirmarEstornoDaBaixa() {
        const lancamento = lancamentoSelecionado.value;

        if (!lancamento || processandoAcao.value) {
            return;
        }

        processandoAcao.value = true;

        router.post(lancamento.url_estornar_baixa, { versao: lancamento.versao }, {
            preserveScroll: true,
            onFinish: () => {
                processandoAcao.value = false;
                cancelarFluxo();
            },
        });
    }

    function cancelarFluxo() {
        if (processandoAcao.value || carregandoDadosPessoais.value) {
            return;
        }

        confirmacaoBaixaAberta.value = false;
        formularioPessoalAberto.value = false;
        avisoNaoResponsavelAberto.value = false;
        confirmacaoAssumirAberta.value = false;
        confirmacaoEstornoAberta.value = false;
        lancamentoSelecionado.value = null;
        erroCategoriaAssumir.value = '';
    }

    return {
        lancamentoSelecionado,
        confirmacaoBaixaAberta,
        formularioPessoalAberto,
        avisoNaoResponsavelAberto,
        confirmacaoAssumirAberta,
        confirmacaoEstornoAberta,
        carregandoDadosPessoais,
        processandoAcao,
        dadosLancamentoPessoal,
        erroCategoriaAssumir,
        valoresIniciaisDoLancamentoPessoal,
        dadosExtrasDaBaixa,
        iniciarBaixaDoLancamento,
        confirmarRegistroNoControlePessoal,
        pedirConfirmacaoParaAssumir,
        confirmarAssumirLancamento,
        iniciarEstornoDaBaixa,
        confirmarEstornoDaBaixa,
        cancelarFluxo,
    };
}
