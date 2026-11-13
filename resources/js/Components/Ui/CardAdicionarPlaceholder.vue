<script setup>
defineProps({
    titulo: {
        type: String,
        required: true,
    },
    descricao: {
        type: String,
        default: '',
    },
    icone: {
        type: String,
        default: 'add',
    },
});

const emit = defineEmits(['click']);
</script>

<template>
    <button
        type="button"
        class="group flex min-h-[10rem] w-full flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-ng-line-strong p-6 text-center transition hover:border-ng-brand hover:bg-ng-brand-soft"
        @click="emit('click')"
    >
        <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-ng-line-strong bg-ng-card-muted text-ng-ink-muted transition group-hover:text-ng-brand">
            <span class="material-symbols-outlined text-[20px]">{{ icone }}</span>
        </span>
        <span class="text-sm font-semibold text-ng-ink">{{ titulo }}</span>
        <span v-if="descricao" class="text-xs text-ng-ink-muted">{{ descricao }}</span>
    </button>
</template>
