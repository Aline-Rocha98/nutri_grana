<script setup>
import BarraProgresso from '@/Components/BarraProgresso.vue';

defineProps({
    dados: {
        type: Object,
        default: null,
    },
    carregando: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line p-6 h-full">
        <h3 class="font-semibold text-ng-ink">Cartões</h3>

        <div v-if="carregando" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="n in 2" :key="n" class="h-28 rounded-xl bg-ng-card-muted animate-pulse" />
        </div>

        <template v-else-if="dados">
            <div v-if="!dados.itens?.length" class="mt-4 text-sm text-ng-ink-muted">
                Nenhum cartão ativo cadastrado.
            </div>

            <div v-else class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="cartao in dados.itens"
                    :key="cartao.id"
                    class="rounded-xl border border-ng-line p-4"
                >
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium text-ng-ink">{{ cartao.nome }}</p>
                        <span class="text-xs font-medium text-ng-ink-muted">
                            {{ cartao.percentual_utilizado }}% usado
                        </span>
                    </div>

                    <div class="mt-3">
                        <BarraProgresso
                            :percentual="cartao.percentual_utilizado"
                            :ultrapassado="cartao.percentual_utilizado > 100"
                        />
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <p class="text-ng-ink-muted">Limite total</p>
                            <p class="font-medium text-ng-ink">R$ {{ cartao.limite_total }}</p>
                        </div>
                        <div>
                            <p class="text-ng-ink-muted">Disponível</p>
                            <p class="font-medium text-[#1fa67e]">R$ {{ cartao.limite_disponivel }}</p>
                        </div>
                        <div>
                            <p class="text-ng-ink-muted">Fatura atual</p>
                            <p class="font-medium text-ng-ink">R$ {{ cartao.fatura_atual }}</p>
                        </div>
                        <div>
                            <p class="text-ng-ink-muted">Próxima fatura</p>
                            <p class="font-medium text-ng-ink">R$ {{ cartao.fatura_proxima }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
