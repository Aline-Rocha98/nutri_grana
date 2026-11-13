<script setup>
const props = defineProps({
    icone: {
        type: String,
        required: true,
    },
    rotulo: {
        type: String,
        required: true,
    },
    variante: {
        type: String,
        default: 'padrao',
    },
    ativo: {
        type: Boolean,
        default: false,
    },
    desabilitado: {
        type: Boolean,
        default: false,
    },
    pararPropagacao: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['click']);

function aoClicar(evento) {
    if (props.pararPropagacao) {
        evento.stopPropagation();
    }

    emit('click', evento);
}

const classesVariante = {
    padrao: 'text-ng-ink-muted hover:bg-ng-brand-soft hover:text-ng-ink',
    perigo: 'text-ng-ink-muted hover:bg-red-500/10 hover:text-red-400',
};
</script>

<template>
    <button
        type="button"
        class="rounded-lg p-1.5 transition disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
        :class="[ativo ? 'text-ng-brand' : (classesVariante[variante] ?? classesVariante.padrao)]"
        :title="rotulo"
        :aria-label="rotulo"
        :aria-pressed="ativo"
        :disabled="desabilitado"
        @click="aoClicar"
    >
        <span class="material-symbols-outlined block text-[18px]">{{ icone }}</span>
    </button>
</template>
