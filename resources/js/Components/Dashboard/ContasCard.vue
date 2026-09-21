<script setup>
import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';

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
</script>

<template>
    <div class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-6 h-full">
        <h3 class="font-semibold text-ng-ink">Contas</h3>

        <div v-if="carregando" class="mt-4 space-y-3">
            <div v-for="n in 3" :key="n" class="h-10 rounded-lg bg-ng-card-muted animate-pulse" />
        </div>

        <template v-else-if="dados">
            <div v-if="!dados.itens?.length" class="mt-4 text-sm text-ng-ink-muted">
                Nenhuma conta marcada para o resumo.
            </div>

            <ul v-else class="mt-4 divide-y divide-ng-line">
                <li
                    v-for="conta in dados.itens"
                    :key="conta.id"
                    class="flex items-center justify-between py-3 gap-3"
                >
                    <span class="font-medium text-ng-ink truncate">{{ conta.nome }}</span>
                    <span class="text-ng-ink-secondary whitespace-nowrap">R$ {{ conta.saldo }}</span>
                </li>
            </ul>

            <div
                v-if="dados.itens?.length"
                class="mt-4 pt-4 border-t border-ng-line flex items-center justify-between"
            >
                <span class="font-semibold text-ng-ink">Total</span>
                <span class="font-semibold text-[#1fa67e]">
                    R$ {{ dados.total?.saldo ?? formatarNumeroParaMoeda(0) }}
                </span>
            </div>
        </template>
    </div>
</template>
