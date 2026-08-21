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
    <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 h-full">
        <h3 class="font-semibold text-gray-800">Cartões</h3>

        <div v-if="carregando" class="mt-4 space-y-4">
            <div v-for="n in 2" :key="n" class="h-28 rounded-xl bg-gray-100 animate-pulse" />
        </div>

        <template v-else-if="dados">
            <div v-if="!dados.itens?.length" class="mt-4 text-sm text-gray-500">
                Nenhum cartão ativo cadastrado.
            </div>

            <div v-else class="mt-4 space-y-4">
                <div
                    v-for="cartao in dados.itens"
                    :key="cartao.id"
                    class="rounded-xl border border-gray-100 p-4"
                >
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium text-gray-900">{{ cartao.nome }}</p>
                        <span class="text-xs font-medium text-gray-500">
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
                            <p class="text-gray-500">Limite total</p>
                            <p class="font-medium text-gray-800">R$ {{ cartao.limite_total }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Disponível</p>
                            <p class="font-medium text-[#1fa67e]">R$ {{ cartao.limite_disponivel }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Fatura atual</p>
                            <p class="font-medium text-gray-800">R$ {{ cartao.fatura_atual }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Próxima fatura</p>
                            <p class="font-medium text-gray-800">R$ {{ cartao.fatura_proxima }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
