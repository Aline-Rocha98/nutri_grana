<script setup>
const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({}),
    },
    opcoes: {
        type: Array,
        default: () => [],
    },
    rotulo: {
        type: String,
        default: '',
    },
    icone: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['alternar']);

function estaAtiva(opcao) {
    return Boolean(props.modelValue[opcao.id]);
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2" role="group" :aria-label="rotulo || undefined">
        <span v-if="rotulo" class="mr-1 inline-flex items-center gap-1.5 text-xs text-ng-ink-muted">
            <span v-if="icone" class="material-symbols-outlined text-[16px]">{{ icone }}</span>
            {{ rotulo }}
        </span>

        <button
            v-for="opcao in opcoes"
            :key="opcao.id"
            type="button"
            :aria-pressed="estaAtiva(opcao)"
            class="ng-selecionavel rounded-lg px-3 py-1.5 text-xs font-medium"
            :class="estaAtiva(opcao) ? 'ng-selecionavel--ativo text-ng-brand' : 'text-ng-ink-muted hover:text-ng-ink'"
            @click="emit('alternar', opcao.id)"
        >
            {{ opcao.rotulo }}
        </button>
    </div>
</template>
