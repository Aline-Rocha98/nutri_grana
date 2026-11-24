<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import CabecalhoCentralDia from '@/Components/Home/CabecalhoCentralDia.vue';
import CardDica from '@/Components/Ui/CardDica.vue';
import CardResumoAcao from '@/Components/Ui/CardResumoAcao.vue';

const URL_LANCAMENTOS = '/lancamentos';
const URL_OBJETIVOS = '/objetivos';
const URL_DASHBOARD = '/dashboard';
const URL_CONTAS_BANCARIAS = '/contas-bancarias';

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

const saldoLivre = computed(() => props.resumoDoDia.saldo_livre);
const aPagar = computed(() => props.resumoDoDia.a_pagar);
const dicaFinanceira = computed(() => props.resumoDoDia.dica_financeira);

const rodapeSaldoLivre = computed(() => (saldoLivre.value.conta_principal_nome
    ? `${saldoLivre.value.conta_principal_nome} • Principal`
    : 'Nenhuma conta principal definida'));

const badgePendentes = computed(() => {
    const quantidade = aPagar.value.quantidade;

    return quantidade === 1 ? '1 pendente' : `${quantidade} pendentes`;
});
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
                    icone="account_balance_wallet"
                    :variante-valor="saldoLivre.valor_numero < 0 ? 'red' : 'brand'"
                    :valor="saldoLivre.valor"
                    descricao="Saldo disponível nas contas bancárias"
                    :badge-texto="saldoLivre.conta_principal_tipo_rotulo ?? ''"
                    :rodape-texto="rodapeSaldoLivre"
                    link-rotulo="Ver contas bancárias"
                    :link-href="URL_CONTAS_BANCARIAS"
                />

                <CardResumoAcao
                    rotulo="A Pagar nos Próximos Dias"
                    icone="schedule"
                    variante="amber"
                    :valor="aPagar.total"
                    :badge-texto="aPagar.quantidade ? badgePendentes : ''"
                    badge-variante="amber"
                    rodape-texto="Atenção aos vencimentos"
                    link-rotulo="Ver lançamentos"
                    :link-href="URL_LANCAMENTOS"
                />

                <CardDica
                    titulo="Dica Financeira de Hoje"
                    :partes="dicaFinanceira.partes"
                    :variante="VARIANTE_DICA_POR_TOM[dicaFinanceira.tom] ?? 'brand'"
                    link-rotulo="Ver relatório executivo completo"
                    :link-href="URL_DASHBOARD"
                />
            </div>
        </div>
    </AutenticadoLayout>
</template>
