<script setup>
import BarraProgresso from '@/Components/BarraProgresso.vue';

defineProps({
    rotulo: {
        type: String,
        required: true,
    },
    valor: {
        type: String,
        default: '',
    },
    percentual: {
        type: Number,
        default: 0,
    },
    cor: {
        type: String,
        default: '#1fa67e',
    },
    classeValor: {
        type: String,
        default: 'text-ng-brand',
    },
    altura: {
        type: String,
        default: 'h-2.5',
    },
});
</script>

<template>
    <div>
        <div class="flex items-center justify-between gap-3 text-xs">
            <span class="text-ng-ink-muted">{{ rotulo }}</span>
            <span v-if="valor" class="font-mono font-semibold tabular-nums" :class="classeValor">{{ valor }}</span>
        </div>
        <BarraProgresso class="mt-2" :percentual="percentual" :cor="cor" :altura="altura" />
    </div>
</template>
