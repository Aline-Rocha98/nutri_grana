<script setup>
const props = defineProps({
    widgets: {
        type: Object,
        required: true,
    },
    opcoes: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['toggle']);

function estaAtivo(id) {
    return Boolean(props.widgets[id]);
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-medium text-ng-ink-muted mr-1">Exibir:</span>
        <button
            v-for="opcao in opcoes"
            :key="opcao.id"
            type="button"
            class="rounded-full px-3 py-1.5 text-sm font-medium border transition"
            :class="estaAtivo(opcao.id)
                ? 'bg-[#1fa67e] text-white border-[#1fa67e]'
                : 'bg-ng-card text-ng-ink-muted border-ng-line-strong hover:border-[#1fa67e]/50'"
            @click="emit('toggle', opcao.id)"
        >
            {{ opcao.rotulo }}
        </button>
    </div>
</template>
