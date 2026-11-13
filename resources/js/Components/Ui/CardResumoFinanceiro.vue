<script setup>
defineProps({
    nome: {
        type: String,
        required: true,
    },
    identificacao: {
        type: String,
        default: '',
    },
    selecionado: {
        type: Boolean,
        default: false,
    },
    arquivado: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['selecionar']);
</script>

<template>
    <article
        role="button"
        tabindex="0"
        class="ng-card flex cursor-pointer flex-col gap-4 rounded-2xl p-5 outline-none transition hover:border-ng-line-strong focus-visible:ring-2 focus-visible:ring-ng-brand"
        :class="[
            selecionado ? '!border-ng-brand bg-gradient-to-b from-ng-brand-soft to-transparent' : '',
            arquivado ? 'opacity-70' : '',
        ]"
        :aria-pressed="selecionado"
        @click="emit('selecionar')"
        @keydown.enter.self="emit('selecionar')"
    >
        <header class="flex items-start gap-3">
            <slot name="marca" />

            <div class="min-w-0 flex-1">
                <div class="flex min-w-0 items-center gap-2">
                    <h4 class="truncate text-sm font-semibold text-ng-ink">{{ nome }}</h4>
                    <slot name="badges" />
                </div>
                <p v-if="identificacao" class="mt-0.5 truncate font-mono text-[11px] text-ng-ink-muted">
                    {{ identificacao }}
                </p>
            </div>

            <div class="flex shrink-0 items-center">
                <slot name="acoes" />
            </div>
        </header>

        <slot />

        <footer v-if="$slots.rodape" class="ng-card-muted flex items-center justify-between gap-3 px-3 py-2.5 text-xs">
            <slot name="rodape" />
        </footer>
    </article>
</template>
