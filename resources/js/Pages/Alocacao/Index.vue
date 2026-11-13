<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import CardAlocacao from '@/Components/Alocacao/CardAlocacao.vue';
import BotaoAdicionarInline from '@/Components/Ui/BotaoAdicionarInline.vue';
import CabecalhoSecao from '@/Components/Ui/CabecalhoSecao.vue';
import CardAdicionarPlaceholder from '@/Components/Ui/CardAdicionarPlaceholder.vue';
import CardIndicador from '@/Components/Ui/CardIndicador.vue';
import CadastroAlocacaoModal from '@/Pages/Alocacao/CadastroAlocacaoModal.vue';
import ResgateAlocacaoModal from '@/Pages/Alocacao/ResgateAlocacaoModal.vue';
import TransferenciaAlocacaoModal from '@/Pages/Alocacao/TransferenciaAlocacaoModal.vue';
import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';

const props = defineProps({
    alocacoes: {
        type: Array,
        default: () => [],
    },
    alocacoesCadastradas: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    totalAlocado: {
        type: Number,
        default: 0,
    },
    contasBancarias: {
        type: Array,
        default: () => [],
    },
    urlCriar: {
        type: String,
        required: true,
    },
});

const modalCadastroAberto = ref(false);
const modalTransferenciaAberto = ref(false);
const modalResgateAberto = ref(false);
const idAlocacaoSelecionada = ref(null);

const alocacaoSelecionada = computed(() => props.alocacoes.find((alocacao) => alocacao.id === idAlocacaoSelecionada.value) ?? null);
const alocacoesComSaldo = computed(() => props.alocacoes.filter((alocacao) => alocacao.saldo_numero > 0));

const descricaoTotalAlocado = computed(() => {
    const quantidade = alocacoesComSaldo.value.length;

    return quantidade === 1 ? '1 alocação com saldo' : `${quantidade} alocações com saldo`;
});

function abrirCadastroAlocacao() {
    modalCadastroAberto.value = true;
}

function fecharCadastroAlocacao() {
    modalCadastroAberto.value = false;
}

function abrirTransferenciaAlocacao(alocacao) {
    idAlocacaoSelecionada.value = alocacao.id;
    modalTransferenciaAberto.value = true;
}

function fecharTransferenciaAlocacao() {
    modalTransferenciaAberto.value = false;
}

function abrirResgateAlocacao(alocacao) {
    idAlocacaoSelecionada.value = alocacao.id;
    modalResgateAberto.value = true;
}

function fecharResgateAlocacao() {
    modalResgateAberto.value = false;
}
</script>

<template>
    <Head title="Alocações" />

    <AutenticadoLayout titulo="Alocações">
        <div class="space-y-6 p-6 lg:p-8">
            <CabecalhoSecao
                tamanho="pagina"
                titulo="Alocações"
                subtitulo="Acompanhe o que você tem guardado em cada objetivo, transfira entre alocações ou resgate para uma conta."
            >
                <template #acoes>
                    <BotaoAdicionarInline rotulo="Nova alocação" @click="abrirCadastroAlocacao" />
                </template>
            </CabecalhoSecao>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <CardIndicador
                    rotulo="Total alocado"
                    variante="sky"
                    icone="savings"
                    :valor="formatarNumeroParaMoeda(totalAlocado)"
                    :descricao="descricaoTotalAlocado"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <CardAlocacao
                    v-for="alocacao in alocacoes"
                    :key="alocacao.id"
                    :nome="alocacao.nome"
                    :saldo="alocacao.saldo"
                    :saldo-numero="alocacao.saldo_numero"
                    :saldos-por-conta="alocacao.saldos_por_conta"
                    :movimentacoes-recentes="alocacao.movimentacoes_recentes"
                    @transferir="abrirTransferenciaAlocacao(alocacao)"
                    @resgatar="abrirResgateAlocacao(alocacao)"
                />

                <CardAdicionarPlaceholder
                    titulo="Nova alocação"
                    descricao="Crie um nome para usar nos seus planos financeiros"
                    icone="savings"
                    @click="abrirCadastroAlocacao"
                />
            </div>
        </div>

        <CadastroAlocacaoModal
            :aberto="modalCadastroAberto"
            :url-criar="urlCriar"
            :alocacoes-cadastradas="alocacoesCadastradas"
            @fechar="fecharCadastroAlocacao"
        />

        <TransferenciaAlocacaoModal
            :aberto="modalTransferenciaAberto"
            :alocacao="alocacaoSelecionada"
            :alocacoes="alocacoes"
            @fechar="fecharTransferenciaAlocacao"
        />

        <ResgateAlocacaoModal
            :aberto="modalResgateAberto"
            :alocacao="alocacaoSelecionada"
            :contas-bancarias="contasBancarias"
            @fechar="fecharResgateAlocacao"
        />
    </AutenticadoLayout>
</template>
