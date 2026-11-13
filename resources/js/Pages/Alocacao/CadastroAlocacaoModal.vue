<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import LinhaAlocacaoCadastrada from '@/Components/Alocacao/LinhaAlocacaoCadastrada.vue';
import Paginacao from '@/Components/Ui/Paginacao.vue';
import SecaoRecolhivel from '@/Components/Ui/SecaoRecolhivel.vue';

const ERROR_BAG_CRIAR = 'criarAlocacao';
const ERROR_BAG_RENOMEAR = 'renomearAlocacao';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    urlCriar: {
        type: String,
        required: true,
    },
    alocacoesCadastradas: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
});

const emit = defineEmits(['fechar']);

const formulario = useForm({
    nome: '',
});

const listaCadastradasAberta = ref(true);
const idAlocacaoEmProcessamento = ref(null);
const errosRenomearPorAlocacao = ref({});

const itensCadastrados = computed(() => props.alocacoesCadastradas?.data ?? []);
const linksPaginacao = computed(() => props.alocacoesCadastradas?.links ?? []);
const totalCadastradas = computed(() => props.alocacoesCadastradas?.meta?.total ?? itensCadastrados.value.length);

watch(
    () => props.aberto,
    (aberto) => {
        if (aberto) {
            formulario.reset();
            formulario.clearErrors();
            errosRenomearPorAlocacao.value = {};
        }
    },
);

function fecharCadastroAlocacao() {
    emit('fechar');
}

function criarAlocacao() {
    if (!formulario.nome.trim()) {
        return;
    }

    formulario.post(props.urlCriar, {
        errorBag: ERROR_BAG_CRIAR,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            formulario.reset();
            listaCadastradasAberta.value = true;
        },
    });
}

function renomearAlocacao(alocacao, novoNome) {
    idAlocacaoEmProcessamento.value = alocacao.id;
    errosRenomearPorAlocacao.value = { ...errosRenomearPorAlocacao.value, [alocacao.id]: '' };

    router.put(alocacao.url_atualizar, { nome: novoNome }, {
        errorBag: ERROR_BAG_RENOMEAR,
        preserveScroll: true,
        preserveState: true,
        onError: (erros) => {
            errosRenomearPorAlocacao.value = { ...errosRenomearPorAlocacao.value, [alocacao.id]: erros.nome ?? '' };
        },
        onFinish: () => {
            idAlocacaoEmProcessamento.value = null;
        },
    });
}

function excluirAlocacao(alocacao) {
    idAlocacaoEmProcessamento.value = alocacao.id;

    router.delete(alocacao.url_excluir, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            idAlocacaoEmProcessamento.value = null;
        },
    });
}

function navegarParaPaginaDasCadastradas(url) {
    router.get(url, {}, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        only: ['alocacoesCadastradas'],
    });
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        icone="savings"
        titulo="Nova alocação"
        subtitulo="Crie o nome uma vez e reutilize em todos os seus planos financeiros."
        texto-confirmar="Salvar alocação"
        texto-cancelar="Fechar"
        :processando="formulario.processing"
        :desabilitar-confirmar="!formulario.nome.trim()"
        @fechar="fecharCadastroAlocacao"
        @confirmar="criarAlocacao"
    >
        <div class="space-y-5">
            <CampoTexto
                id="alocacao-nome"
                v-model="formulario.nome"
                rotulo="Nome da alocação"
                placeholder="Ex.: Carro, Reserva de emergência, Viagem"
                maxlength="120"
                obrigatorio
                :erro="formulario.errors.nome"
            />

            <SecaoRecolhivel
                v-model:aberto="listaCadastradasAberta"
                titulo="Alocações cadastradas"
                descricao="Renomeie ou exclua as alocações que ainda não foram usadas."
                icone="inventory_2"
                :contador="totalCadastradas"
            >
                <p v-if="itensCadastrados.length === 0" class="text-xs text-ng-ink-muted">
                    Nenhuma alocação cadastrada ainda.
                </p>

                <ul v-else class="divide-y divide-ng-line overflow-hidden rounded-xl border border-ng-line bg-ng-card">
                    <LinhaAlocacaoCadastrada
                        v-for="alocacao in itensCadastrados"
                        :key="alocacao.id"
                        :nome="alocacao.nome"
                        :total-itens-plano="alocacao.total_itens_plano"
                        :total-movimentacoes="alocacao.total_movimentacoes"
                        :pode-excluir="alocacao.pode_excluir"
                        :erro="errosRenomearPorAlocacao[alocacao.id] ?? ''"
                        :processando="idAlocacaoEmProcessamento === alocacao.id"
                        @renomear="renomearAlocacao(alocacao, $event)"
                        @excluir="excluirAlocacao(alocacao)"
                    />
                </ul>

                <Paginacao :links="linksPaginacao" @navegar="navegarParaPaginaDasCadastradas" />
            </SecaoRecolhivel>
        </div>
    </Modal>
</template>
