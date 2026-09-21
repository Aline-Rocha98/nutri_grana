<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import BarraProgresso from '@/Components/BarraProgresso.vue';
import { badgeSituacaoObjetivo } from '@/Helpers/badge';

defineProps({
    usuario: {
        type: Object,
        required: true,
    },
    dataHoje: {
        type: String,
        required: true,
    },
    objetivosDashboard: {
        type: Array,
        default: () => [],
    },
});

const agora = new Date();
const urlLancamentos = `/lancamentos/${agora.getFullYear()}/${agora.getMonth() + 1}`;

function classeSituacao(situacao) {
    return badgeSituacaoObjetivo(situacao);
}
</script>

<template>
    <Head title="Home" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-ng-ink leading-tight">Home</h2>
                <span class="text-sm text-ng-ink-muted">{{ dataHoje }}</span>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-6">
                <h3 class="text-lg font-semibold text-[#1fa67e]">
                    Olá, {{ usuario.nome }}!
                </h3>
                <p class="mt-2 text-ng-ink-muted">
                    Pequenas ações geram grandes resultados. Organize suas finanças pelo menu lateral.
                </p>
            </div>

            <div v-if="objetivosDashboard.length" class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-6">
                <div class="flex items-center justify-between gap-3">
                    <h4 class="font-semibold text-ng-ink">Objetivos no dashboard</h4>
                    <Link href="/objetivos" class="text-sm font-medium text-[#1fa67e] hover:underline">
                        Ver todos
                    </Link>
                </div>

                <div class="mt-4 space-y-4">
                    <div
                        v-for="objetivo in objetivosDashboard"
                        :key="objetivo.id"
                        class="rounded-xl border border-ng-line p-4"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-medium text-ng-ink">{{ objetivo.descricao }}</p>
                            <span :class="classeSituacao(objetivo.situacao_ritmo)">
                                {{ objetivo.situacao_ritmo_rotulo }}
                            </span>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-sm text-ng-ink-muted">
                            <span>{{ objetivo.percentual_atual }}%</span>
                            <span>R$ {{ objetivo.valor_guardado }} / R$ {{ objetivo.valor_meta }}</span>
                        </div>
                        <div class="mt-2">
                            <BarraProgresso :percentual="objetivo.percentual_atual" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Link
                    href="/dashboard"
                    class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-6 text-center hover:border-[#1fa67e]/40 transition"
                >
                    <h4 class="font-semibold text-ng-ink">Dashboard</h4>
                    <p class="mt-2 text-sm text-ng-ink-muted">Resumo financeiro e gráficos</p>
                </Link>
                <Link
                    :href="urlLancamentos"
                    class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-6 text-center hover:border-[#1fa67e]/40 transition"
                >
                    <h4 class="font-semibold text-ng-ink">Lançamentos</h4>
                    <p class="mt-2 text-sm text-ng-ink-muted">Receitas e despesas do mês</p>
                </Link>
                <Link
                    href="/contas-bancarias"
                    class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-6 text-center hover:border-[#1fa67e]/40 transition"
                >
                    <h4 class="font-semibold text-ng-ink">Contas</h4>
                    <p class="mt-2 text-sm text-ng-ink-muted">Saldos e contas bancárias</p>
                </Link>
                <Link
                    href="/objetivos"
                    class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-6 text-center hover:border-[#1fa67e]/40 transition"
                >
                    <h4 class="font-semibold text-ng-ink">Objetivos</h4>
                    <p class="mt-2 text-sm text-ng-ink-muted">Metas e aportes</p>
                </Link>
            </div>
        </div>
    </AutenticadoLayout>
</template>
