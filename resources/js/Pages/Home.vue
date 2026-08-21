<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import BarraProgresso from '@/Components/BarraProgresso.vue';

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
    return {
        adiantado: 'bg-emerald-50 text-emerald-700',
        em_dia: 'bg-sky-50 text-sky-700',
        atrasado: 'bg-amber-50 text-amber-700',
        concluido: 'bg-[#1fa67e]/10 text-[#198a68]',
        vencido: 'bg-red-50 text-red-700',
    }[situacao] ?? 'bg-gray-50 text-gray-600';
}
</script>

<template>
    <Head title="Home" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Home</h2>
                <span class="text-sm text-gray-500">{{ dataHoje }}</span>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-[#1fa67e]">
                    Olá, {{ usuario.nome }}!
                </h3>
                <p class="mt-2 text-gray-600">
                    Pequenas ações geram grandes resultados. Organize suas finanças pelo menu lateral.
                </p>
            </div>

            <div v-if="objetivosDashboard.length" class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
                <div class="flex items-center justify-between gap-3">
                    <h4 class="font-semibold text-gray-800">Objetivos no dashboard</h4>
                    <Link href="/objetivos" class="text-sm font-medium text-[#1fa67e] hover:underline">
                        Ver todos
                    </Link>
                </div>

                <div class="mt-4 space-y-4">
                    <div
                        v-for="objetivo in objetivosDashboard"
                        :key="objetivo.id"
                        class="rounded-xl border border-gray-100 p-4"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-medium text-gray-900">{{ objetivo.descricao }}</p>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="classeSituacao(objetivo.situacao_ritmo)"
                            >
                                {{ objetivo.situacao_ritmo_rotulo }}
                            </span>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-sm text-gray-500">
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
                    class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center hover:border-[#1fa67e]/40 transition"
                >
                    <h4 class="font-semibold text-gray-800">Dashboard</h4>
                    <p class="mt-2 text-sm text-gray-500">Resumo financeiro e gráficos</p>
                </Link>
                <Link
                    :href="urlLancamentos"
                    class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center hover:border-[#1fa67e]/40 transition"
                >
                    <h4 class="font-semibold text-gray-800">Lançamentos</h4>
                    <p class="mt-2 text-sm text-gray-500">Receitas e despesas do mês</p>
                </Link>
                <Link
                    href="/contas-bancarias"
                    class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center hover:border-[#1fa67e]/40 transition"
                >
                    <h4 class="font-semibold text-gray-800">Contas</h4>
                    <p class="mt-2 text-sm text-gray-500">Saldos e contas bancárias</p>
                </Link>
                <Link
                    href="/objetivos"
                    class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center hover:border-[#1fa67e]/40 transition"
                >
                    <h4 class="font-semibold text-gray-800">Objetivos</h4>
                    <p class="mt-2 text-sm text-gray-500">Metas e aportes</p>
                </Link>
            </div>
        </div>
    </AutenticadoLayout>
</template>
