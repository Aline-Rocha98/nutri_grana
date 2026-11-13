<script setup>
import { computed } from 'vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import LinkAcaoPainel from '@/Components/Ui/LinkAcaoPainel.vue';
import { estiloDaVariante } from '@/Helpers/varianteCor';

const props = defineProps({
    rotulo: {
        type: String,
        required: true,
    },
    icone: {
        type: String,
        default: '',
    },
    variante: {
        type: String,
        default: 'brand',
    },
    varianteValor: {
        type: String,
        default: '',
    },
    valor: {
        type: String,
        default: '0,00',
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    descricao: {
        type: String,
        default: '',
    },
    badgeTexto: {
        type: String,
        default: '',
    },
    badgeVariante: {
        type: String,
        default: 'brand',
    },
    rodapeTexto: {
        type: String,
        default: '',
    },
    linkRotulo: {
        type: String,
        default: '',
    },
    linkHref: {
        type: String,
        default: '',
    },
});

const estilo = computed(() => estiloDaVariante(props.variante));
const classeValor = computed(() => estiloDaVariante(props.varianteValor || props.variante).texto);
</script>

<template>
    <article class="ng-card flex flex-col p-5">
        <div class="flex items-center justify-between gap-3">
            <p class="flex min-w-0 items-center gap-2 text-xs font-medium text-ng-ink-secondary">
                <span v-if="icone" class="material-symbols-outlined text-[16px]" :class="estilo.texto">{{ icone }}</span>
                <span class="truncate">{{ rotulo }}</span>
            </p>
            <BadgeStatus v-if="badgeTexto" :texto="badgeTexto" :variante="badgeVariante" compacto />
        </div>

        <p class="mt-3 font-mono text-2xl font-semibold tabular-nums" :class="classeValor">
            {{ prefixoMoeda }} {{ valor }}
        </p>
        <p v-if="descricao" class="mt-1 truncate text-[11px] text-ng-ink-muted" :title="descricao">{{ descricao }}</p>

        <div v-if="rodapeTexto || linkHref" class="mt-auto pt-4">
            <div class="flex items-center justify-between gap-3 border-t border-ng-line pt-3 text-xs">
                <span class="min-w-0 truncate text-ng-ink-muted">{{ rodapeTexto }}</span>
                <LinkAcaoPainel
                    v-if="linkHref && linkRotulo"
                    :href="linkHref"
                    :rotulo="linkRotulo"
                    icone-direita="chevron_right"
                    :variante="variante === 'zinc' ? 'brand' : variante"
                    destacado
                />
            </div>
        </div>
    </article>
</template>
