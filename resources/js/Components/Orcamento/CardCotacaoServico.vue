<script setup>
import { computed } from 'vue';
import AvisoInformativo from '@/Components/Formulario/AvisoInformativo.vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';
import IconeCategoria from '@/Components/Ui/IconeCategoria.vue';
import TileMetrica from '@/Components/Ui/TileMetrica.vue';

const ROTULOS_PADRAO = {
    valorEstimado: 'Valor estimado',
    modalidade: 'Modalidade',
    forma: 'Forma',
    detalhar: 'Detalhar',
    recusar: 'Recusar',
    aprovar: 'Aprovar',
    editar: 'Editar cotação',
    excluir: 'Excluir cotação',
};

const props = defineProps({
    descricao: {
        type: String,
        required: true,
    },
    subtitulo: {
        type: String,
        default: '',
    },
    statusRotulo: {
        type: String,
        default: '',
    },
    statusVariante: {
        type: String,
        default: 'zinc',
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    valor: {
        type: String,
        default: '0,00',
    },
    modalidade: {
        type: String,
        default: '',
    },
    forma: {
        type: String,
        default: '',
    },
    emAnalise: {
        type: Boolean,
        default: false,
    },
    resumoCompromisso: {
        type: String,
        default: '',
    },
    compromissoViavel: {
        type: Boolean,
        default: false,
    },
    resumoAprovacao: {
        type: String,
        default: '',
    },
    observacao: {
        type: String,
        default: '',
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['editar', 'excluir', 'detalhar', 'recusar', 'aprovar']);

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));
</script>

<template>
    <article class="ng-card flex flex-col rounded-2xl p-5 transition hover:border-ng-brand">
        <header class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <IconeCategoria icone="request_quote" cor="#1fa67e" />
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="truncate text-sm font-semibold text-ng-ink">{{ descricao }}</h3>
                        <BadgeStatus v-if="statusRotulo" :texto="statusRotulo" :variante="statusVariante" compacto />
                    </div>
                    <p v-if="subtitulo" class="mt-1 truncate font-mono text-[11px] text-ng-ink-muted">{{ subtitulo }}</p>
                </div>
            </div>

            <div class="flex shrink-0 items-center">
                <BotaoIcone v-if="emAnalise" icone="edit" :rotulo="textos.editar" @click="emit('editar')" />
                <BotaoIcone icone="delete" :rotulo="textos.excluir" variante="perigo" @click="emit('excluir')" />
            </div>
        </header>

        <div class="mt-4 border-t border-ng-line pt-4">
            <p class="text-xs text-ng-ink-muted">{{ textos.valorEstimado }}</p>
            <p class="mt-1 font-mono text-2xl font-semibold tabular-nums text-ng-ink">{{ prefixoMoeda }} {{ valor }}</p>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <TileMetrica :rotulo="textos.modalidade" :valor="modalidade || '—'" prefixo-moeda="" />
            <TileMetrica :rotulo="textos.forma" :valor="forma || '—'" prefixo-moeda="" />
        </div>

        <AvisoInformativo
            v-if="emAnalise && resumoCompromisso"
            class="mt-4"
            :rotulo="resumoCompromisso"
            :variante="compromissoViavel ? 'brand' : 'amber'"
        />
        <AvisoInformativo v-else-if="resumoAprovacao" class="mt-4" :rotulo="resumoAprovacao" />

        <div v-if="emAnalise" class="mt-4 grid grid-cols-3 gap-2">
            <button type="button" class="ng-btn-ghost normal-case tracking-normal" @click="emit('detalhar')">
                <span class="material-symbols-outlined text-[16px]">analytics</span>
                {{ textos.detalhar }}
            </button>
            <button type="button" class="ng-btn-ghost normal-case tracking-normal" @click="emit('recusar')">
                {{ textos.recusar }}
            </button>
            <button type="button" class="ng-btn-suave py-2 text-xs" @click="emit('aprovar')">
                {{ textos.aprovar }}
            </button>
        </div>

        <p v-if="observacao" class="mt-4 border-t border-ng-line pt-3 text-xs text-ng-ink-muted">
            {{ observacao }}
        </p>
    </article>
</template>
