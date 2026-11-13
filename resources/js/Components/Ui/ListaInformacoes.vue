<script setup>
defineProps({
    itens: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <dl class="space-y-3 text-sm">
        <div
            v-for="(item, indice) in itens"
            :key="item.chave ?? item.rotulo"
            class="flex items-center justify-between gap-4"
            :class="item.separador && indice < itens.length - 1 ? 'border-b border-ng-line pb-3' : ''"
        >
            <dt class="text-ng-ink-muted">{{ item.rotulo }}</dt>
            <dd class="flex items-center gap-1.5 font-semibold" :class="item.classeValor ?? 'font-mono text-ng-ink'">
                <span v-if="item.indicador" class="h-1.5 w-1.5 rounded-full" :class="item.indicador" />
                {{ item.valor }}
            </dd>
        </div>
    </dl>
</template>
