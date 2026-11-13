<script setup>
import { computed, ref } from 'vue';
import ListaLancamentos from '@/Components/Ui/ListaLancamentos.vue';
import { converterMovimentacaoAlocacaoParaLinha } from '@/Helpers/alocacao';

const props = defineProps({
    nome: {
        type: String,
        required: true,
    },
    saldo: {
        type: String,
        default: '0,00',
    },
    saldoNumero: {
        type: Number,
        default: 0,
    },
    saldosPorConta: {
        type: Array,
        default: () => [],
    },
    movimentacoesRecentes: {
        type: Array,
        default: () => [],
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
});

const emit = defineEmits(['transferir', 'resgatar']);

const detalhesAbertos = ref(false);

const possuiSaldo = computed(() => props.saldoNumero > 0);
const linhasMovimentacoes = computed(() => props.movimentacoesRecentes.map(converterMovimentacaoAlocacaoParaLinha));
const resumoContas = computed(() => {
    const quantidade = props.saldosPorConta.length;

    if (quantidade === 0) {
        return 'Nenhum valor guardado ainda';
    }

    return quantidade === 1 ? `Guardado em ${props.saldosPorConta[0].nome}` : `Distribuído em ${quantidade} contas`;
});

function alternarDetalhes() {
    detalhesAbertos.value = !detalhesAbertos.value;
}
</script>

<template>
    <article class="ng-card flex flex-col gap-4 p-5">
        <header class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-500/10 text-sky-500">
                    <span class="material-symbols-outlined text-[18px]">savings</span>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-ng-ink">{{ nome }}</p>
                    <p class="text-[11px] text-ng-ink-subtle">{{ resumoContas }}</p>
                </div>
            </div>
        </header>

        <p class="font-mono text-2xl font-semibold tabular-nums" :class="saldoNumero < 0 ? 'text-red-500' : 'text-sky-500'">
            {{ prefixoMoeda }} {{ saldo }}
        </p>

        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg border border-ng-line-strong bg-ng-card-muted px-3 py-1.5 text-xs font-medium text-ng-ink-secondary transition hover:border-ng-brand hover:text-ng-ink disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="!possuiSaldo"
                @click="emit('transferir')"
            >
                <span class="material-symbols-outlined text-[16px]">swap_horiz</span>
                Transferir
            </button>
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg border border-ng-line-strong bg-ng-card-muted px-3 py-1.5 text-xs font-medium text-ng-ink-secondary transition hover:border-ng-brand hover:text-ng-ink disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="!possuiSaldo"
                @click="emit('resgatar')"
            >
                <span class="material-symbols-outlined text-[16px]">south_west</span>
                Resgatar
            </button>
        </div>

        <div class="border-t border-ng-line pt-3">
            <button
                type="button"
                class="flex w-full items-center justify-between text-xs font-semibold text-ng-ink-muted transition hover:text-ng-ink"
                :aria-expanded="detalhesAbertos"
                @click="alternarDetalhes"
            >
                Detalhes
                <span class="material-symbols-outlined text-[18px] transition" :class="detalhesAbertos ? 'rotate-180' : ''">
                    expand_more
                </span>
            </button>

            <div v-show="detalhesAbertos" class="mt-3 space-y-4">
                <section class="space-y-2">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-ng-ink-subtle">Saldo por conta</p>
                    <ul v-if="saldosPorConta.length" class="space-y-1.5">
                        <li
                            v-for="saldoConta in saldosPorConta"
                            :key="saldoConta.id_conta_bancaria"
                            class="flex items-center justify-between text-xs"
                        >
                            <span class="text-ng-ink-secondary">{{ saldoConta.nome }}</span>
                            <span class="font-mono font-semibold tabular-nums text-ng-ink">
                                {{ prefixoMoeda }} {{ saldoConta.saldo_formatado }}
                            </span>
                        </li>
                    </ul>
                    <p v-else class="text-xs text-ng-ink-muted">Nenhuma conta com saldo nesta alocação.</p>
                </section>

                <section class="space-y-2">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-ng-ink-subtle">Movimentações recentes</p>
                    <ListaLancamentos
                        :itens="linhasMovimentacoes"
                        sem-moldura
                        mensagem-vazia="Nenhuma movimentação ainda."
                    />
                </section>
            </div>
        </div>
    </article>
</template>
