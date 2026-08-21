<script setup>
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

function classeVariacao(direcao) {
    return {
        alta: 'text-emerald-600',
        baixa: 'text-red-600',
        igual: 'text-gray-500',
    }[direcao] ?? 'text-gray-500';
}

function iconeVariacao(direcao) {
    return {
        alta: 'trending_up',
        baixa: 'trending_down',
        igual: 'remove',
    }[direcao] ?? 'remove';
}
</script>

<template>
    <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
        <div class="flex items-center justify-between gap-3">
            <h3 class="font-semibold text-gray-800">Resumo financeiro do mês</h3>
            <span v-if="dados?.comparacao" class="text-xs text-gray-400">
                vs {{ dados.comparacao.mes_anterior_rotulo }}
            </span>
        </div>

        <div v-if="carregando" class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="n in 6" :key="n" class="h-24 rounded-xl bg-gray-100 animate-pulse" />
        </div>

        <div v-else-if="dados" class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-100 p-4">
                <p class="text-sm text-gray-500">Receitas recebidas</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-600">R$ {{ dados.receitas_recebidas }}</p>
                <p
                    v-if="dados.comparacao"
                    class="mt-2 flex items-center gap-1 text-xs font-medium"
                    :class="classeVariacao(dados.comparacao.receitas_recebidas.direcao)"
                >
                    <span class="material-symbols-outlined text-sm">{{ iconeVariacao(dados.comparacao.receitas_recebidas.direcao) }}</span>
                    {{ dados.comparacao.receitas_recebidas.percentual }}%
                </p>
            </div>

            <div class="rounded-xl border border-gray-100 p-4">
                <p class="text-sm text-gray-500">Despesas pagas</p>
                <p class="mt-1 text-2xl font-semibold text-red-600">R$ {{ dados.despesas_pagas }}</p>
                <p
                    v-if="dados.comparacao"
                    class="mt-2 flex items-center gap-1 text-xs font-medium"
                    :class="classeVariacao(dados.comparacao.despesas_pagas.direcao)"
                >
                    <span class="material-symbols-outlined text-sm">{{ iconeVariacao(dados.comparacao.despesas_pagas.direcao) }}</span>
                    {{ dados.comparacao.despesas_pagas.percentual }}%
                </p>
            </div>

            <div class="rounded-xl border border-gray-100 p-4">
                <p class="text-sm text-gray-500">Saldo do mês</p>
                <p class="mt-1 text-2xl font-semibold text-[#1fa67e]">R$ {{ dados.saldo }}</p>
                <p
                    v-if="dados.comparacao"
                    class="mt-2 flex items-center gap-1 text-xs font-medium"
                    :class="classeVariacao(dados.comparacao.saldo.direcao)"
                >
                    <span class="material-symbols-outlined text-sm">{{ iconeVariacao(dados.comparacao.saldo.direcao) }}</span>
                    {{ dados.comparacao.saldo.percentual }}%
                </p>
            </div>

            <div class="rounded-xl border border-dashed border-gray-200 p-4 bg-gray-50/50">
                <p class="text-sm text-gray-500">Receitas previstas</p>
                <p class="mt-1 text-xl font-semibold text-gray-800">R$ {{ dados.receitas_previstas }}</p>
            </div>

            <div class="rounded-xl border border-dashed border-gray-200 p-4 bg-gray-50/50">
                <p class="text-sm text-gray-500">Despesas previstas</p>
                <p class="mt-1 text-xl font-semibold text-gray-800">R$ {{ dados.despesas_previstas }}</p>
            </div>

            <div class="rounded-xl border border-dashed border-gray-200 p-4 bg-gray-50/50">
                <p class="text-sm text-gray-500">Saldo previsto</p>
                <p class="mt-1 text-xl font-semibold text-gray-800">R$ {{ dados.saldo_previsto }}</p>
            </div>
        </div>
    </div>
</template>
