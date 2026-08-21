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
        <h3 class="font-semibold text-gray-800">Despesas por categoria</h3>

        <div v-if="carregando" class="mt-4 space-y-3">
            <div v-for="n in 4" :key="n" class="h-12 rounded-lg bg-gray-100 animate-pulse" />
        </div>

        <template v-else-if="dados">
            <div v-if="!dados.itens?.length" class="mt-4 text-sm text-gray-500">
                Nenhuma despesa neste mês.
            </div>

            <ul v-else class="mt-4 space-y-4">
                <li
                    v-for="item in dados.itens"
                    :key="item.id_categoria"
                    class="space-y-2"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span
                                class="h-2.5 w-2.5 rounded-full shrink-0"
                                :style="{ backgroundColor: item.cor || '#1fa67e' }"
                            />
                            <span class="font-medium text-gray-800 truncate">{{ item.nome }}</span>
                        </div>
                        <span class="text-gray-700 whitespace-nowrap">R$ {{ item.valor }}</span>
                    </div>

                    <template v-if="item.limite !== null">
                        <BarraProgresso
                            :percentual="item.percentual_barra ?? 0"
                            :cor="item.cor || '#1fa67e'"
                            :ultrapassado="item.ultrapassado"
                        />
                        <p class="text-xs text-gray-500">
                            {{ item.percentual }}% do limite (R$ {{ item.limite }})
                        </p>
                    </template>
                </li>
            </ul>
        </template>
    </div>
</template>
