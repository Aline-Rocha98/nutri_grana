<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import CabecalhoCentralDia from '@/Components/Home/CabecalhoCentralDia.vue';
import CardMetaDestaque from '@/Components/Objetivo/CardMetaDestaque.vue';
import CardDica from '@/Components/Ui/CardDica.vue';
import CardPainel from '@/Components/Ui/CardPainel.vue';
import CardResumoAcao from '@/Components/Ui/CardResumoAcao.vue';
import LinhaLancamento from '@/Components/Ui/LinhaLancamento.vue';
import LinkAcaoPainel from '@/Components/Ui/LinkAcaoPainel.vue';

const URL_LANCAMENTOS = '/lancamentos';
const URL_CARTOES_CREDITO = '/cartoes-credito';
const URL_OBJETIVOS = '/objetivos';
const URL_DASHBOARD = '/dashboard';

const VARIANTE_DICA_POR_TOM = {
    positivo: 'brand',
    atencao: 'amber',
    alerta: 'red',
};

const props = defineProps({
    usuario: {
        type: Object,
        required: true,
    },
    resumoDoDia: {
        type: Object,
        required: true,
    },
});

const idLancamentoPagando = ref(null);

const saldoLivre = computed(() => props.resumoDoDia.saldo_livre);
const aPagar = computed(() => props.resumoDoDia.a_pagar);
const compromissos = computed(() => props.resumoDoDia.compromissos ?? []);
const cartaoPreferencial = computed(() => props.resumoDoDia.cartao_preferencial);
const metaEmDestaque = computed(() => props.resumoDoDia.meta_em_destaque);
const dicaFinanceira = computed(() => props.resumoDoDia.dica_financeira);

const rodapeSaldoLivre = computed(() => (saldoLivre.value.conta_principal_nome
    ? `${saldoLivre.value.conta_principal_nome} • Principal`
    : 'Nenhuma conta principal definida'));

const badgePendentes = computed(() => {
    const quantidade = aPagar.value.quantidade;

    return quantidade === 1 ? '1 pendente' : `${quantidade} pendentes`;
});

const mensagemSemCompromissos = computed(
    () => `Nenhum compromisso pendente nos próximos ${props.resumoDoDia.dias_janela} dias.`,
);

function montarSubtituloCompromisso(compromisso) {
    return [compromisso.data_vencimento_formatada, compromisso.conta_bancaria_nome]
        .filter(Boolean)
        .join(' • ');
}

function pagarCompromisso(compromisso) {
    if (idLancamentoPagando.value) {
        return;
    }

    idLancamentoPagando.value = compromisso.id;

    router.patch(compromisso.url_situacao, { situacao: 'pago' }, {
        preserveScroll: true,
        only: ['resumoDoDia'],
        onFinish: () => {
            idLancamentoPagando.value = null;
        },
    });
}
</script>

<template>
    <Head title="Home" />

    <AutenticadoLayout titulo="Home">
        <div class="space-y-6 p-6 lg:p-8">
            <CabecalhoCentralDia
                :nome-usuario="usuario.nome"
                :url-dashboard="URL_DASHBOARD"
                :url-registrar-pagamento="URL_LANCAMENTOS"
                :url-aporte-rapido="URL_OBJETIVOS"
            />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <CardResumoAcao
                    rotulo="Saldo Livre para o Dia"
                    icone="account_balance_wallet"
                    :variante-valor="saldoLivre.valor_numero < 0 ? 'red' : 'brand'"
                    :valor="saldoLivre.valor"
                    descricao="Saldo disponível nas contas do resumo"
                    :badge-texto="saldoLivre.conta_principal_tipo_rotulo ?? ''"
                    :rodape-texto="rodapeSaldoLivre"
                    link-rotulo="Extrato"
                    :link-href="URL_LANCAMENTOS"
                />

                <CardResumoAcao
                    rotulo="A Pagar nos Próximos Dias"
                    icone="schedule"
                    variante="amber"
                    :valor="aPagar.total"
                    :descricao="aPagar.resumo_descricoes || 'Nenhuma despesa pendente'"
                    :badge-texto="aPagar.quantidade ? badgePendentes : ''"
                    badge-variante="amber"
                    rodape-texto="Atenção aos vencimentos"
                    link-rotulo="Ver agenda"
                    :link-href="URL_LANCAMENTOS"
                />

                <CardResumoAcao
                    v-if="cartaoPreferencial"
                    rotulo="Cartão Preferencial"
                    icone="credit_card"
                    variante="zinc"
                    :valor="cartaoPreferencial.limite_disponivel"
                    :descricao="`Limite disponível no ${cartaoPreferencial.nome}`"
                    :badge-texto="`Fecha dia ${cartaoPreferencial.dia_fechamento_formatado}`"
                    badge-variante="zinc"
                    :rodape-texto="`Vencimento: dia ${cartaoPreferencial.dia_vencimento_formatado}`"
                    link-rotulo="Gerenciar"
                    :link-href="URL_CARTOES_CREDITO"
                />
                <article v-else class="ng-card flex flex-col items-start justify-center gap-3 p-5">
                    <p class="text-sm text-ng-ink-muted">Nenhum cartão cadastrado.</p>
                    <LinkAcaoPainel :href="URL_CARTOES_CREDITO" rotulo="Cadastrar cartão" icone-esquerda="add" destacado />
                </article>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <CardPainel
                    class="lg:col-span-2"
                    titulo="Compromissos e Contas Pendentes"
                    subtitulo="Clique em Pagar para marcar como pago e dar baixa instantânea."
                    icone="error"
                    :vazio="!compromissos.length"
                    :mensagem-vazio="mensagemSemCompromissos"
                >
                    <template #acoes>
                        <LinkAcaoPainel :href="URL_LANCAMENTOS" rotulo="Adicionar conta" icone-esquerda="add" destacado />
                    </template>

                    <ul class="space-y-3">
                        <li v-for="compromisso in compromissos" :key="compromisso.id" class="ng-card-muted">
                            <LinhaLancamento
                                :descricao="compromisso.descricao"
                                :subtitulo="montarSubtituloCompromisso(compromisso)"
                                subtitulo-mono
                                :valor="compromisso.valor"
                                :icone="compromisso.icone"
                                :cor="compromisso.cor"
                                :badge-texto="compromisso.atrasado ? 'Atrasado' : ''"
                                badge-variante="amber"
                                badge-na-descricao
                                negativo
                            >
                                <template #acoes>
                                    <button
                                        type="button"
                                        class="ng-selecionavel ml-1 inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium text-ng-ink-secondary hover:text-ng-ink disabled:cursor-wait disabled:opacity-50"
                                        :disabled="idLancamentoPagando !== null"
                                        :aria-label="`Marcar ${compromisso.descricao} como pago`"
                                        @click="pagarCompromisso(compromisso)"
                                    >
                                        <span
                                            v-if="idLancamentoPagando === compromisso.id"
                                            class="material-symbols-outlined animate-spin text-[14px]"
                                        >progress_activity</span>
                                        Pagar
                                    </button>
                                </template>
                            </LinhaLancamento>
                        </li>
                    </ul>
                </CardPainel>

                <div class="flex flex-col gap-6">
                    <CardMetaDestaque
                        v-if="metaEmDestaque"
                        :descricao="metaEmDestaque.descricao"
                        :data-limite-formatada="metaEmDestaque.data_limite_formatada"
                        :valor-guardado="metaEmDestaque.valor_guardado"
                        :valor-meta="metaEmDestaque.valor_meta"
                        :percentual="metaEmDestaque.percentual_atual"
                    >
                        <template #acao>
                            <Link :href="URL_OBJETIVOS" class="ng-btn-suave w-full">
                                <span class="material-symbols-outlined text-[18px]">add</span>
                                Fazer Aporte de Hoje
                            </Link>
                        </template>
                    </CardMetaDestaque>
                    <article v-else class="ng-card flex flex-col items-start gap-3 p-5">
                        <p class="text-sm text-ng-ink-muted">Nenhuma meta em destaque.</p>
                        <LinkAcaoPainel :href="URL_OBJETIVOS" rotulo="Criar meta" icone-esquerda="add" destacado />
                    </article>

                    <CardDica
                        titulo="Dica Financeira de Hoje"
                        :partes="dicaFinanceira.partes"
                        :variante="VARIANTE_DICA_POR_TOM[dicaFinanceira.tom] ?? 'brand'"
                        link-rotulo="Ver relatório executivo completo"
                        :link-href="URL_DASHBOARD"
                    />
                </div>
            </div>
        </div>
    </AutenticadoLayout>
</template>
