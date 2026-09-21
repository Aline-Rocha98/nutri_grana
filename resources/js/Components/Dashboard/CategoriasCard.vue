<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
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

const temaEscuro = ref(
    typeof document !== 'undefined' && document.documentElement.classList.contains('dark'),
);

let observadorTema = null;

onMounted(() => {
    observadorTema = new MutationObserver(() => {
        temaEscuro.value = document.documentElement.classList.contains('dark');
    });
    observadorTema.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
});

onUnmounted(() => {
    observadorTema?.disconnect();
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

const opcoes = computed(() => {
    const escuro = temaEscuro.value;
    const corEixo = escuro ? '#a1a1aa' : '#6b7280';
    const corLimite = escuro ? '#27272a' : '#e4e4e7';
    const tooltipBg = escuro ? '#1a1a1a' : '#ffffff';
    const tooltipBorder = escuro ? 'rgba(255,255,255,0.08)' : '#f3f4f6';
    const tooltipTitulo = escuro ? '#fafafa' : '#111827';
    const tooltipTexto = escuro ? '#a1a1aa' : '#4b5563';
    const tooltipForte = escuro ? '#fafafa' : '#111827';

    return {
        chart: {
            type: 'bar',
            stacked: true,
            toolbar: { show: false },
            fontFamily: 'inherit',
            background: 'transparent',
            foreColor: corEixo,
            animations: { enabled: true },
        },
        theme: {
            mode: escuro ? 'dark' : 'light',
        },
        colors: ['#1fa67e', '#ef4444', corLimite],
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
                    colors: corEixo,
                    fontSize: '11px',
                },
                trim: true,
                hideOverlappingLabels: true,
            },
        },
        yaxis: {
            labels: {
                style: { colors: corEixo, fontSize: '11px' },
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
            labels: { colors: corEixo },
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
                    `<div style="font-weight:600;margin-bottom:6px;color:${tooltipTitulo}">${item.nome}</div>`,
                    `<div style="color:${tooltipTexto}">Gasto: <strong style="color:#1fa67e">${formatarMoeda(item.valor_numero)}</strong></div>`,
                ];

                if (temLimite) {
                    linhas.push(
                        `<div style="color:${tooltipTexto};margin-top:2px">Limite: <strong style="color:${tooltipForte}">${formatarMoeda(item.limite_numero)}</strong></div>`,
                        `<div style="color:${tooltipTexto};margin-top:2px">Uso: <strong style="color:${tooltipForte}">${item.percentual}%</strong></div>`,
                    );

                    if (excedente > 0) {
                        linhas.push(
                            `<div style="margin-top:8px;padding-top:8px;border-top:1px solid ${tooltipBorder};color:#ef4444">`,
                            `<strong>${formatarNumeroParaMoeda(percentualExtra)}% a mais</strong>`,
                            ` referente ao limite da categoria`,
                            `</div>`,
                            `<div style="color:${tooltipTexto};font-size:11px;margin-top:2px">Excedente: ${formatarMoeda(excedente)}</div>`,
                        );
                    }
                }

                return `<div style="padding:10px 12px;background:${tooltipBg};border:1px solid ${tooltipBorder};border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.35);min-width:180px">${linhas.join('')}</div>`;
            },
        },
        states: {
            hover: { filter: { type: 'none' } },
            active: { filter: { type: 'none' } },
        },
    };
});
</script>

<template>
    <div class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-6 h-full">
        <h3 class="font-semibold text-ng-ink">Despesas por categoria</h3>

        <div v-if="carregando" class="mt-6 h-64 rounded-xl bg-ng-card-muted animate-pulse" />

        <template v-else-if="dados">
            <div v-if="!itens.length" class="mt-4 text-sm text-ng-ink-muted">
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
