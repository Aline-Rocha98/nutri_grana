<script setup>
defineProps({
    rotulo: {
        type: String,
        default: '',
    },
    icone: {
        type: String,
        default: '',
    },
    itens: {
        type: Array,
        default: () => [],
    },
    chaveAtiva: {
        type: [String, Number],
        default: null,
    },
});

const emit = defineEmits(['selecionar']);
</script>

<template>
    <div v-if="itens.length > 0" class="flex items-center gap-3">
        <span v-if="rotulo || icone" class="flex shrink-0 items-center gap-1.5 text-xs text-ng-ink-muted">
            <span v-if="icone" class="material-symbols-outlined text-[16px] text-ng-brand">{{ icone }}</span>
            {{ rotulo }}
        </span>
        <div class="ng-scroll flex min-w-0 flex-1 gap-2 overflow-x-auto pb-1">
            <button
                v-for="item in itens"
                :key="item.chave"
                type="button"
                class="ng-selecionavel shrink-0 whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium text-ng-ink-secondary"
                :class="{ 'ng-selecionavel--ativo': chaveAtiva === item.chave }"
                @click="emit('selecionar', item)"
            >
                {{ item.rotulo }}
            </button>
        </div>
    </div>
</template>
