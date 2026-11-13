<script setup>
import BarraProgresso from '@/Components/BarraProgresso.vue';
import IconeCategoria from '@/Components/Ui/IconeCategoria.vue';

defineProps({
    nome: {
        type: String,
        required: true,
    },
    icone: {
        type: String,
        default: 'category',
    },
    cor: {
        type: String,
        default: '#1fa67e',
    },
    valor: {
        type: String,
        default: '0,00',
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    percentual: {
        type: Number,
        default: 0,
    },
    ultrapassado: {
        type: Boolean,
        default: false,
    },
    dica: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div :title="dica || undefined">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2.5">
                <IconeCategoria :icone="icone" :cor="cor" tamanho="sm" />
                <span class="truncate text-sm font-medium text-ng-ink">{{ nome }}</span>
            </div>
            <div class="flex shrink-0 items-center gap-3 font-mono text-xs tabular-nums">
                <span class="text-ng-ink-muted">{{ Math.round(percentual) }}%</span>
                <span class="font-semibold" :class="ultrapassado ? 'text-rose-500' : 'text-ng-ink'">
                    {{ prefixoMoeda }} {{ valor }}
                </span>
            </div>
        </div>

        <BarraProgresso class="mt-2" :percentual="percentual" :ultrapassado="ultrapassado" altura="h-1.5" />
    </div>
</template>
