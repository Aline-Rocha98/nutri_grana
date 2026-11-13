<script setup>
const aberto = defineModel('aberto', { type: Boolean, default: false });

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
        default: '',
    },
    contador: {
        type: [Number, String],
        default: null,
    },
    destacarErro: {
        type: Boolean,
        default: false,
    },
});

function alternarSecao() {
    aberto.value = !aberto.value;
}
</script>

<template>
    <section
        class="ng-card-muted overflow-hidden rounded-xl border transition"
        :class="destacarErro ? '!border-red-500' : 'border-ng-line'"
    >
        <button
            type="button"
            class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-ng-brand-soft/60"
            :aria-expanded="aberto"
            @click="alternarSecao"
        >
            <span
                v-if="icone"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-500/10 text-sky-500"
            >
                <span class="material-symbols-outlined text-[18px]">{{ icone }}</span>
            </span>

            <span class="min-w-0 flex-1">
                <span class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-ng-ink">{{ titulo }}</span>
                    <span
                        v-if="contador !== null && Number(contador) > 0"
                        class="rounded-full bg-sky-500/10 px-2 text-[10px] font-semibold text-sky-500"
                    >
                        {{ contador }}
                    </span>
                </span>
                <span v-if="descricao" class="mt-0.5 block text-xs text-ng-ink-muted">{{ descricao }}</span>
            </span>

            <span class="material-symbols-outlined text-[20px] text-ng-ink-muted transition" :class="aberto ? 'rotate-180' : ''">
                expand_more
            </span>
        </button>

        <div v-show="aberto" class="space-y-4 border-t border-ng-line px-4 py-4">
            <slot />
        </div>
    </section>
</template>
