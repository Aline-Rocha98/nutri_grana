<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Boolean],
        default: null,
    },
    titulo: {
        type: String,
        default: '',
    },
    descricao: {
        type: String,
        default: '',
    },
    icone: {
        type: String,
        default: '',
    },
    valorAtivo: {
        type: [String, Boolean],
        default: 'S',
    },
    valorInativo: {
        type: [String, Boolean],
        default: 'N',
    },
    desabilitado: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const estaAtivo = computed(() => props.modelValue === props.valorAtivo);

function alternarValor() {
    if (props.desabilitado) {
        return;
    }

    emit('update:modelValue', estaAtivo.value ? props.valorInativo : props.valorAtivo);
}
</script>

<template>
    <div class="ng-card-muted flex items-center justify-between gap-4 px-4 py-3">
        <div class="flex min-w-0 items-center gap-3">
            <span
                v-if="icone"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border"
                :class="estaAtivo ? 'ng-icone-destaque' : 'border-ng-line-strong text-ng-ink-muted'"
            >
                <span class="material-symbols-outlined text-[18px]">{{ icone }}</span>
            </span>
            <div class="min-w-0">
                <p class="flex items-center gap-1.5 text-sm font-semibold text-ng-ink">
                    {{ titulo }}
                    <slot name="informacao" />
                </p>
                <p v-if="descricao" class="text-xs text-ng-ink-muted">{{ descricao }}</p>
            </div>
        </div>
        <button
            type="button"
            role="switch"
            class="ng-toggle disabled:cursor-not-allowed disabled:opacity-50"
            :class="estaAtivo ? 'ng-toggle--on' : 'ng-toggle--off'"
            :aria-checked="estaAtivo"
            :aria-label="titulo"
            :disabled="desabilitado"
            @click="alternarValor"
        >
            <span class="ng-toggle__knob" :class="estaAtivo ? 'translate-x-5' : 'translate-x-0'" />
        </button>
    </div>
</template>
