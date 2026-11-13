<script setup>
import { computed } from 'vue';

const ROTULOS_PADRAO = {
    titulo: 'Saldo guardado',
    descricao: 'Separado pelo plano financeiro das rendas. Não entra no saldo disponível.',
    disponivel: 'Saldo disponível',
    acao: 'Ver alocações',
};

const props = defineProps({
    saldoGuardado: {
        type: String,
        default: '0,00',
    },
    saldoGuardadoNumero: {
        type: Number,
        default: 0,
    },
    saldoDisponivel: {
        type: String,
        default: '0,00',
    },
    saldoDisponivelNumero: {
        type: Number,
        default: 0,
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    exibirAcao: {
        type: Boolean,
        default: true,
    },
    iconeAcao: {
        type: String,
        default: 'savings',
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['acao']);

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));
</script>

<template>
    <article class="ng-card space-y-4 p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-500/10 text-sky-500">
                    <span class="material-symbols-outlined text-[18px]">savings</span>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-ng-ink-muted">{{ textos.titulo }}</p>
                    <p class="font-mono text-xl font-semibold tabular-nums text-sky-500">
                        {{ prefixoMoeda }} {{ saldoGuardado }}
                    </p>
                </div>
            </div>

            <button
                v-if="exibirAcao"
                type="button"
                class="inline-flex items-center gap-1 rounded-lg border border-ng-line-strong bg-ng-card-muted px-3 py-1.5 text-xs font-medium text-ng-ink-secondary transition hover:border-ng-brand hover:text-ng-ink"
                @click="emit('acao')"
            >
                <span class="material-symbols-outlined text-[16px]">{{ iconeAcao }}</span>
                {{ textos.acao }}
            </button>
        </div>

        <p class="text-[11px] text-ng-ink-subtle">{{ textos.descricao }}</p>

        <div class="flex items-center justify-between border-t border-ng-line pt-3 text-xs">
            <span class="text-ng-ink-muted">{{ textos.disponivel }}</span>
            <span
                class="font-mono font-semibold tabular-nums"
                :class="saldoDisponivelNumero < 0 ? 'text-red-500' : 'text-ng-brand'"
            >
                {{ prefixoMoeda }} {{ saldoDisponivel }}
            </span>
        </div>
    </article>
</template>
