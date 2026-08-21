<script setup>
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    dados: {
        type: Object,
        default: null,
    },
    carregando: {
        type: Boolean,
        default: false,
    },
    periodo: {
        type: String,
        default: 'atual',
    },
});

const emit = defineEmits(['atualizar-periodo']);

const opcoesPeriodo = [
    { id: 'atual', rotulo: 'Mês atual' },
    { id: 'anterior', rotulo: 'Mês anterior' },
    { id: '3meses', rotulo: 'Últimos 3 meses' },
];

const categorias = computed(() => (props.dados?.series ?? []).map((item) => item.rotulo));

const series = computed(() => [
    {
        name: 'Receitas',
        data: (props.dados?.series ?? []).map((item) => item.receitas),
    },
    {
        name: 'Despesas',
        data: (props.dados?.series ?? []).map((item) => item.despesas),
    },
]);

const opcoes = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    colors: ['#1fa67e', '#ef4444'],
    plotOptions: {
        bar: {
            borderRadius: 6,
            columnWidth: '45%',
        },
    },
    dataLabels: { enabled: false },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent'],
    },
    xaxis: {
        categories: categorias.value,
        labels: {
            style: { colors: '#6b7280' },
        },
    },
    yaxis: {
        labels: {
            style: { colors: '#6b7280' },
            formatter: (valor) => Number(valor).toLocaleString('pt-BR', {
                maximumFractionDigits: 0,
            }),
        },
    },
    grid: {
        borderColor: '#f3f4f6',
        strokeDashArray: 4,
    },
    legend: {
        position: 'top',
        horizontalAlign: 'right',
    },
    tooltip: {
        y: {
            formatter: (valor) => `R$ ${Number(valor).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })}`,
        },
    },
}));
</script>

<template>
    <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-semibold text-gray-800">Receitas x despesas</h3>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="opcao in opcoesPeriodo"
                    :key="opcao.id"
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-medium border transition"
                    :class="periodo === opcao.id
                        ? 'bg-[#1fa67e] text-white border-[#1fa67e]'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-[#1fa67e]/50'"
                    @click="emit('atualizar-periodo', opcao.id)"
                >
                    {{ opcao.rotulo }}
                </button>
            </div>
        </div>

        <div v-if="carregando" class="mt-6 h-64 rounded-xl bg-gray-100 animate-pulse" />

        <div v-else-if="dados?.series?.length" class="mt-4">
            <VueApexCharts
                type="bar"
                height="280"
                :options="opcoes"
                :series="series"
            />
        </div>

        <p v-else class="mt-6 text-sm text-gray-500">Sem dados para o período selecionado.</p>
    </div>
</template>
