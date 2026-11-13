<script setup>
import { computed, ref } from 'vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';
import IconeCategoria from '@/Components/Ui/IconeCategoria.vue';
import ListaLancamentos from '@/Components/Ui/ListaLancamentos.vue';
import ProgressoMeta from '@/Components/Ui/ProgressoMeta.vue';
import TileMetrica from '@/Components/Ui/TileMetrica.vue';

const ROTULOS_PADRAO = {
    editar: 'Editar',
    excluir: 'Excluir',
    historico: 'Histórico',
};

const props = defineProps({
    titulo: {
        type: String,
        required: true,
    },
    subtitulo: {
        type: String,
        default: '',
    },
    icone: {
        type: String,
        default: '',
    },
    corIcone: {
        type: String,
        default: '#1fa67e',
    },
    badgeTexto: {
        type: String,
        default: '',
    },
    badgeVariante: {
        type: String,
        default: 'zinc',
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    rotuloProgresso: {
        type: String,
        default: '',
    },
    valorAtual: {
        type: String,
        default: '0,00',
    },
    valorMeta: {
        type: String,
        default: '0,00',
    },
    percentual: {
        type: Number,
        default: 0,
    },
    corProgresso: {
        type: String,
        default: '#1fa67e',
    },
    legendaEsquerda: {
        type: String,
        default: '',
    },
    classeLegendaEsquerda: {
        type: String,
        default: 'text-ng-brand',
    },
    legendaDireita: {
        type: String,
        default: '',
    },
    metricas: {
        type: Array,
        default: () => [],
    },
    historico: {
        type: Array,
        default: () => [],
    },
    destacado: {
        type: Boolean,
        default: false,
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['editar', 'excluir']);

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));
const historicoAberto = ref(false);

function alternarHistorico() {
    historicoAberto.value = !historicoAberto.value;
}
</script>

<template>
    <article
        class="ng-card flex flex-col rounded-2xl p-5 transition hover:border-ng-brand"
        :class="{ '!border-ng-brand': destacado }"
    >
        <header class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <IconeCategoria v-if="icone" :icone="icone" :cor="corIcone" />
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="truncate text-sm font-semibold text-ng-ink">{{ titulo }}</h3>
                        <BadgeStatus v-if="badgeTexto" :texto="badgeTexto" :variante="badgeVariante" compacto />
                    </div>
                    <p v-if="subtitulo" class="mt-1 truncate font-mono text-[11px] text-ng-ink-muted">{{ subtitulo }}</p>
                </div>
            </div>

            <div class="flex shrink-0 items-center">
                <BotaoIcone icone="edit" :rotulo="textos.editar" @click="emit('editar')" />
                <BotaoIcone icone="delete" :rotulo="textos.excluir" variante="perigo" @click="emit('excluir')" />
            </div>
        </header>

        <ProgressoMeta
            class="mt-4 border-t border-ng-line pt-4"
            :rotulo="rotuloProgresso"
            :prefixo-moeda="prefixoMoeda"
            :valor-atual="valorAtual"
            :valor-meta="valorMeta"
            :percentual="percentual"
            :cor="corProgresso"
        >
            <template v-if="legendaEsquerda" #legenda-esquerda>
                <span class="font-medium" :class="classeLegendaEsquerda">{{ legendaEsquerda }}</span>
            </template>
            <template v-if="legendaDireita" #legenda-direita>
                {{ legendaDireita }}
            </template>
        </ProgressoMeta>

        <div v-if="metricas.length" class="mt-4 grid grid-cols-2 gap-3">
            <TileMetrica
                v-for="metrica in metricas"
                :key="metrica.rotulo"
                :rotulo="metrica.rotulo"
                :valor="metrica.valor"
                :prefixo-moeda="metrica.prefixoMoeda ?? prefixoMoeda"
                :destacado="Boolean(metrica.destacado)"
            />
        </div>

        <div v-if="$slots.default" class="mt-4">
            <slot />
        </div>

        <div v-if="historico.length" class="mt-4 border-t border-ng-line pt-3">
            <button
                type="button"
                class="flex w-full items-center justify-between text-xs font-medium text-ng-ink-muted transition hover:text-ng-ink"
                :aria-expanded="historicoAberto"
                @click="alternarHistorico"
            >
                {{ textos.historico }} ({{ historico.length }})
                <span class="material-symbols-outlined text-[18px]">
                    {{ historicoAberto ? 'expand_less' : 'expand_more' }}
                </span>
            </button>

            <ListaLancamentos v-if="historicoAberto" class="-mx-4 mt-1" :itens="historico" sem-moldura />
        </div>
    </article>
</template>
