<script setup>
import { computed } from 'vue';

const props = defineProps({
    corAcabamento: {
        type: String,
        default: '#1fa67e',
    },
    titulo: {
        type: String,
        default: '',
    },
    subtitulo: {
        type: String,
        default: '',
    },
    detalhe: {
        type: String,
        default: '',
    },
});

const estiloAcabamento = computed(() => ({
    borderColor: `${props.corAcabamento}59`,
    backgroundImage: `linear-gradient(135deg, ${props.corAcabamento}38 0%, var(--ng-card) 55%, ${props.corAcabamento}1f 100%)`,
}));
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl border p-5 shadow-lg" :style="estiloAcabamento">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <slot name="topo-esquerda" />
            </div>
            <slot name="topo-direita" />
        </div>

        <div class="mt-5 flex items-end justify-between gap-3">
            <slot name="centro">
                <div class="min-w-0">
                    <p class="truncate text-base font-bold text-ng-ink">{{ titulo || '—' }}</p>
                    <p class="truncate text-xs text-ng-ink-muted">{{ subtitulo }}</p>
                </div>
                <p v-if="detalhe" class="shrink-0 font-mono text-xs tracking-wider text-ng-ink-secondary">
                    {{ detalhe }}
                </p>
            </slot>
        </div>

        <div class="mt-4 flex items-end justify-between gap-3 border-t border-ng-line pt-3">
            <div class="min-w-0">
                <slot name="rodape-esquerda" />
            </div>
            <div class="text-right">
                <slot name="rodape-direita" />
            </div>
        </div>
    </div>
</template>
