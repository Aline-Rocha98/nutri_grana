<script setup>
const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: null,
    },
    id: {
        type: String,
        default: '',
    },
    rotulo: {
        type: String,
        required: true,
    },
    dica: {
        type: String,
        default: '',
    },
    minimo: {
        type: Number,
        default: 1,
    },
    maximo: {
        type: Number,
        default: null,
    },
    desabilitado: {
        type: Boolean,
        default: false,
    },
    erro: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

function aoDigitarNumero(evento) {
    const valorDigitado = evento.target.value;

    emit('update:modelValue', valorDigitado === '' ? props.minimo : Number(valorDigitado));
}
</script>

<template>
    <div class="space-y-1.5">
        <div class="ng-card-muted flex items-center justify-between gap-4 px-4 py-3">
            <label :for="id || undefined" class="min-w-0">
                <span class="block text-sm text-ng-ink">{{ rotulo }}</span>
                <span v-if="dica" class="block text-xs text-ng-ink-muted">{{ dica }}</span>
            </label>
            <input
                :id="id || undefined"
                :value="modelValue"
                type="number"
                inputmode="numeric"
                :min="minimo"
                :max="maximo ?? undefined"
                :disabled="desabilitado"
                class="ng-input w-20 shrink-0 rounded-lg text-center font-mono text-sm tabular-nums disabled:cursor-not-allowed"
                :class="erro ? '!border-red-500' : ''"
                @input="aoDigitarNumero"
            >
        </div>
        <p v-if="erro" class="text-xs text-red-500">{{ erro }}</p>
    </div>
</template>
