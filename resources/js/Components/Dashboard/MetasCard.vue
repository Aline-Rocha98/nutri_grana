<script setup>
import { Link } from '@inertiajs/vue3';
import BarraProgresso from '@/Components/BarraProgresso.vue';

defineProps({
    dados: {
        type: Object,
        default: null,
    },
    carregando: {
        type: Boolean,
        default: false,
    },
});

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
    <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 h-full">
        <div class="flex items-center justify-between gap-3">
            <h3 class="font-semibold text-gray-800">Metas</h3>
            <Link href="/objetivos" class="text-sm font-medium text-[#1fa67e] hover:underline">Ver todos</Link>
        </div>

        <div v-if="carregando" class="mt-4 space-y-4">
            <div v-for="n in 2" :key="n" class="h-20 rounded-xl bg-gray-100 animate-pulse" />
        </div>

        <template v-else-if="dados">
            <div v-if="!dados.itens?.length" class="mt-4 text-sm text-gray-500">
                Nenhuma meta marcada para o dashboard.
            </div>

            <div v-else class="mt-4 space-y-4">
                <div
                    v-for="meta in dados.itens"
                    :key="meta.id"
                    class="rounded-xl border border-gray-100 p-4"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-medium text-gray-900">{{ meta.descricao }}</p>
                        <span
                            v-if="meta.situacao_ritmo"
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="classeSituacao(meta.situacao_ritmo)"
                        >
                            {{ meta.situacao_ritmo_rotulo }}
                        </span>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-sm text-gray-500">
                        <span>{{ meta.percentual_atual }}%</span>
                        <span>R$ {{ meta.valor_guardado }} / R$ {{ meta.valor_meta }}</span>
                    </div>
                    <div class="mt-2">
                        <BarraProgresso :percentual="meta.percentual_atual" />
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
