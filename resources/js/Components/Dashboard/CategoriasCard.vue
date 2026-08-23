<script setup>
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { formatarNumeroParaMoeda } from '@/Helpers/mascaraMoeda';

const props = defineProps({
    dados: {
        type: Object,
        default: null,
    },
    carregando: {
        type: Boolean,
        default: false,
    },
});

const itens = computed(() => props.dados?.itens ?? []);
const categorias = computed(() => itens.value.map((item) => item.nome));

const fatias = computed(() =>
    itens.value.map((item) => {
        const valor = Number(item.valor_numero ?? 0);
        const limite = item.limite_numero !== null && item.limite_numero !== undefined
            ? Number(item.limite_numero)
            : null;

        if (limite === null || limite <= 0) {
            return {
                gasto: valor,
                excedente: 0,
                restante: 0,
                temLimite: false,
                percentualExtra: 0,
                item,
            };
        }

        const gasto = Math.min(valor, limite);
        const excedente = Math.max(0, valor - limite);
        const restante = Math.max(0, limite - valor);
        const percentualExtra = excedente > 0
            ? Number(item.percentual ?? Math.round((valor / limite) * 100)) - 100
            : 0;

        return {
            gasto,
            excedente,
            restante,
            temLimite: true,
            percentualExtra: Math.max(0, Number(percentualExtra.toFixed(1))),
            item,
        };
    }),
);

const series = computed(() => [
    {
        name: 'Gasto',
        data: fatias.value.map((f) => Number(f.gasto.toFixed(2))),
    },
    {
        name: 'Excedente',
        data: fatias.value.map((f) => Number(f.excedente.toFixed(2))),
    },
    {
        name: 'Limite restante',
        data: fatias.value.map((f) => Number(f.restante.toFixed(2))),
    },
]);

function formatarMoeda(valor) {
    return `R$ ${formatarNumeroParaMoeda(valor)}`;
}

const opcoes = computed(() => ({
    chart: {
        type: 'bar',
        stacked: true,
        toolbar: { show: false },
        fontFamily: 'inherit',
        animations: { enabled: true },
    },
    colors: ['#1fa67e', '#f472b6', '#e5e7eb'],
    plotOptions: {
        bar: {
            horizontal: false,
            borderRadius: 8,
            borderRadiusApplication: 'end',
            borderRadiusWhenStacked: 'last',
            columnWidth: '38%',
        },
    },
    dataLabels: { enabled: false },
    stroke: {
        show: false,
    },
    xaxis: {
        categories: categorias.value,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: {
                colors: '#6b7280',
                fontSize: '11px',
            },
            trim: true,
            hideOverlappingLabels: true,
        },
    },
    yaxis: {
        labels: {
            style: { colors: '#9ca3af', fontSize: '11px' },
            formatter: (valor) => Number(valor).toLocaleString('pt-BR', {
                maximumFractionDigits: 0,
            }),
        },
    },
    grid: {
        show: false,
        padding: { left: 8, right: 8 },
    },
    legend: {
        show: true,
        position: 'top',
        horizontalAlign: 'right',
        markers: { radius: 12 },
        labels: { colors: '#6b7280' },
        formatter: (nomeSerie) => (nomeSerie === 'Limite restante' ? 'Limite' : nomeSerie),
    },
    tooltip: {
        shared: true,
        intersect: false,
        custom: ({ dataPointIndex }) => {
            const fatia = fatias.value[dataPointIndex];

            if (!fatia) {
                return '';
            }

            const { item, temLimite, excedente, percentualExtra } = fatia;
            const linhas = [
                `<div style="font-weight:600;margin-bottom:6px;color:#111827">${item.nome}</div>`,
                `<div style="color:#4b5563">Gasto: <strong style="color:#111827">${formatarMoeda(item.valor_numero)}</strong></div>`,
            ];

            if (temLimite) {
                linhas.push(
                    `<div style="color:#4b5563;margin-top:2px">Limite: <strong style="color:#111827">${formatarMoeda(item.limite_numero)}</strong></div>`,
                    `<div style="color:#4b5563;margin-top:2px">Uso: <strong style="color:#111827">${item.percentual}%</strong></div>`,
                );

                if (excedente > 0) {
                    linhas.push(
                        `<div style="margin-top:8px;padding-top:8px;border-top:1px solid #f3f4f6;color:#be185d">`,
                        `<strong>${formatarNumeroParaMoeda(percentualExtra)}% a mais</strong>`,
                        ` referente ao limite da categoria`,
                        `</div>`,
                        `<div style="color:#9ca3af;font-size:11px;margin-top:2px">Excedente: ${formatarMoeda(excedente)}</div>`,
                    );
                }
            }

            return `<div style="padding:10px 12px;background:#fff;border:1px solid #f3f4f6;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.08);min-width:180px">${linhas.join('')}</div>`;
        },
    },
    states: {
        hover: { filter: { type: 'none' } },
        active: { filter: { type: 'none' } },
    },
}));
</script>

<template>
    <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 h-full">
        <h3 class="font-semibold text-gray-800">Despesas por categoria</h3>

        <div v-if="carregando" class="mt-6 h-64 rounded-xl bg-gray-100 animate-pulse" />

        <template v-else-if="dados">
            <div v-if="!itens.length" class="mt-4 text-sm text-gray-500">
                Nenhuma despesa neste mês.
            </div>

            <div v-else class="mt-2">
                <VueApexCharts
                    type="bar"
                    height="280"
                    :options="opcoes"
                    :series="series"
                />
            </div>
        </template>
    </div>
</template>
