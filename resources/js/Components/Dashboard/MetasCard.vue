<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
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
});

const BREAKPOINT_MD = 768;
const POR_PAGINA_DESKTOP = 3;
const POR_PAGINA_MOBILE = 1;

const paginaAtual = ref(0);
const ehTelaPequena = ref(false);
const temaEscuro = ref(
    typeof document !== 'undefined' && document.documentElement.classList.contains('dark'),
);

let observadorTema = null;

const itens = computed(() => props.dados?.itens ?? []);

const porPagina = computed(() =>
    ehTelaPequena.value ? POR_PAGINA_MOBILE : POR_PAGINA_DESKTOP,
);

const totalPaginas = computed(() =>
    Math.max(1, Math.ceil(itens.value.length / porPagina.value)),
);

const itensVisiveis = computed(() => {
    const inicio = paginaAtual.value * porPagina.value;

    return itens.value.slice(inicio, inicio + porPagina.value);
});

const temCarrossel = computed(() => itens.value.length > porPagina.value);

const cores = ['#38bdf8', '#f472b6', '#1fa67e', '#a78bfa'];

function atualizarViewport() {
    ehTelaPequena.value = window.innerWidth < BREAKPOINT_MD;
}

watch([itens, porPagina], () => {
    paginaAtual.value = 0;
});

onMounted(() => {
    atualizarViewport();
    window.addEventListener('resize', atualizarViewport);

    observadorTema = new MutationObserver(() => {
        temaEscuro.value = document.documentElement.classList.contains('dark');
    });
    observadorTema.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
});

onUnmounted(() => {
    window.removeEventListener('resize', atualizarViewport);
    observadorTema?.disconnect();
});

function corDoItem(indice) {
    return cores[indice % cores.length];
}

function indiceGlobal(indiceLocal) {
    return paginaAtual.value * porPagina.value + indiceLocal;
}

function opcoesGrafico(objetivo, indiceLocal) {
    const cor = corDoItem(indiceGlobal(indiceLocal));
    const escuro = temaEscuro.value;

    return {
        chart: {
            type: 'radialBar',
            sparkline: { enabled: true },
            animations: { enabled: true },
            background: 'transparent',
            foreColor: escuro ? '#fafafa' : '#18181b',
        },
        theme: {
            mode: escuro ? 'dark' : 'light',
        },
        colors: [cor],
        plotOptions: {
            radialBar: {
                startAngle: -135,
                endAngle: 135,
                hollow: { size: '68%' },
                track: {
                    background: escuro ? 'rgba(255,255,255,0.1)' : '#e4e4e7',
                    strokeWidth: '97%',
                },
                dataLabels: {
                    name: { show: false },
                    value: {
                        show: true,
                        fontSize: '18px',
                        fontWeight: 700,
                        color: escuro ? '#fafafa' : '#18181b',
                        offsetY: 6,
                        formatter: (valor) => `${Math.round(Number(valor))}%`,
                    },
                },
            },
        },
        stroke: { lineCap: 'round' },
        labels: [objetivo.descricao],
    };
}

function anterior() {
    paginaAtual.value = paginaAtual.value <= 0
        ? totalPaginas.value - 1
        : paginaAtual.value - 1;
}

function proxima() {
    paginaAtual.value = paginaAtual.value >= totalPaginas.value - 1
        ? 0
        : paginaAtual.value + 1;
}

function irPara(pagina) {
    paginaAtual.value = pagina;
}
</script>

<template>
    <div class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-6 h-full">
        <div class="flex items-center justify-between gap-3">
            <h3 class="font-semibold text-ng-ink">Objetivos</h3>
            <a
                href="/objetivos"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex h-8 w-8 items-center justify-center rounded-full text-ng-ink-muted hover:text-[#1fa67e] transition"
                title="Abrir objetivos"
                aria-label="Abrir página de objetivos em nova guia"
            >
                <span class="material-symbols-outlined text-[18px]">visibility</span>
            </a>
        </div>

        <div v-if="carregando" class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div v-for="n in 3" :key="n" class="h-40 rounded-xl bg-ng-card-muted animate-pulse" />
        </div>

        <template v-else-if="dados">
            <div v-if="!itens.length" class="mt-4 text-sm text-ng-ink-muted">
                Nenhum objetivo marcado para o dashboard.
            </div>

            <div v-else class="mt-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div
                        v-for="(objetivo, indice) in itensVisiveis"
                        :key="`${objetivo.id}-${temaEscuro ? 'dark' : 'light'}`"
                        class="group relative min-w-0 w-full rounded-xl p-3 flex flex-col items-center text-center"
                    >
                        <div
                            class="pointer-events-none absolute -top-2 left-1/2 z-10 w-max max-w-[11rem] -translate-x-1/2 -translate-y-full rounded-lg bg-zinc-900 px-2.5 py-1.5 text-xs text-white opacity-0 shadow-lg transition group-hover:opacity-100"
                            role="tooltip"
                        >
                            Meta: R$ {{ objetivo.valor_meta }}
                            <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-zinc-900" />
                        </div>

                        <VueApexCharts
                            type="radialBar"
                            height="130"
                            width="130"
                            :options="opcoesGrafico(objetivo, indice)"
                            :series="[Math.min(100, Math.max(0, Number(objetivo.percentual_atual ?? 0)))]"
                        />
                        <p
                            class="mt-1 w-full truncate text-sm font-medium text-ng-ink"
                            :title="objetivo.descricao"
                        >
                            {{ objetivo.descricao }}
                        </p>
                        <p class="mt-0.5 text-sm font-semibold text-ng-ink">
                            R$ {{ objetivo.valor_guardado }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="temCarrossel"
                    class="mt-4 flex items-center justify-center gap-3"
                >
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full text-ng-ink-muted hover:text-[#1fa67e] transition"
                        aria-label="Objetivos anteriores"
                        @click="anterior"
                    >
                        <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                    </button>

                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="pagina in totalPaginas"
                            :key="pagina"
                            type="button"
                            class="h-2 w-2 rounded-full transition"
                            :class="paginaAtual === pagina - 1 ? 'bg-[#1fa67e]' : 'bg-zinc-300 dark:bg-white/15'"
                            :aria-label="`Página ${pagina}`"
                            @click="irPara(pagina - 1)"
                        />
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full text-ng-ink-muted hover:text-[#1fa67e] transition"
                        aria-label="Próximos objetivos"
                        @click="proxima"
                    >
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>
