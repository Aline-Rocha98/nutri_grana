<script setup>
import { computed } from 'vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import IconeCategoria from '@/Components/Ui/IconeCategoria.vue';

const props = defineProps({
    descricao: {
        type: String,
        required: true,
    },
    subtitulo: {
        type: String,
        default: '',
    },
    subtituloMono: {
        type: Boolean,
        default: false,
    },
    valor: {
        type: String,
        required: true,
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    legendaValor: {
        type: String,
        default: '',
    },
    icone: {
        type: String,
        default: '',
    },
    cor: {
        type: String,
        default: undefined,
    },
    etiqueta: {
        type: String,
        default: '',
    },
    etiquetaIcone: {
        type: String,
        default: '',
    },
    badgeTexto: {
        type: String,
        default: '',
    },
    badgeVariante: {
        type: String,
        default: 'zinc',
    },
    badgeIcone: {
        type: String,
        default: '',
    },
    badgeNaDescricao: {
        type: Boolean,
        default: false,
    },
    negativo: {
        type: Boolean,
        default: false,
    },
    positivo: {
        type: Boolean,
        default: false,
    },
});

const sinalDoValor = computed(() => {
    if (props.negativo) {
        return '-';
    }

    return props.positivo ? '+' : '';
});

const classeCorDoValor = computed(() => {
    if (props.negativo) {
        return 'text-rose-500';
    }

    return props.positivo ? 'text-ng-brand' : 'text-ng-ink';
});
</script>

<template>
    <div class="flex items-center gap-3 px-4 py-3.5">
        <IconeCategoria :icone="icone" :cor="cor" tamanho="sm" />

        <div class="min-w-0 flex-1">
            <div class="flex min-w-0 items-center gap-2">
                <p class="truncate text-sm font-semibold text-ng-ink">{{ descricao }}</p>
                <span
                    v-if="etiqueta"
                    class="inline-flex shrink-0 items-center gap-0.5 rounded-md border border-ng-line-strong bg-ng-card-muted px-1.5 py-0.5 font-mono text-[10px] text-ng-ink-muted"
                >
                    <span v-if="etiquetaIcone" class="material-symbols-outlined text-[12px] text-ng-brand">{{ etiquetaIcone }}</span>
                    {{ etiqueta }}
                </span>
                <BadgeStatus
                    v-if="badgeTexto && badgeNaDescricao"
                    :texto="badgeTexto"
                    :variante="badgeVariante"
                    :icone="badgeIcone"
                    compacto
                />
            </div>
            <p
                v-if="subtitulo"
                class="truncate text-xs text-ng-ink-muted"
                :class="subtituloMono ? 'font-mono text-[11px]' : ''"
            >
                {{ subtitulo }}
            </p>
        </div>

        <div class="flex shrink-0 flex-col items-end gap-1">
            <p class="font-mono text-sm font-semibold tabular-nums" :class="classeCorDoValor">
                {{ sinalDoValor }} {{ prefixoMoeda }} {{ valor }}
            </p>
            <BadgeStatus
                v-if="badgeTexto && !badgeNaDescricao"
                :texto="badgeTexto"
                :variante="badgeVariante"
                :icone="badgeIcone"
                compacto
            />
            <p v-if="legendaValor" class="text-[10px] text-ng-ink-subtle">{{ legendaValor }}</p>
        </div>

        <div v-if="$slots.acoes" class="flex shrink-0 items-center">
            <slot name="acoes" />
        </div>
    </div>
</template>
