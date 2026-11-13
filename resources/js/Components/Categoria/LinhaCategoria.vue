<script setup>
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import IconeCategoria from '@/Components/Ui/IconeCategoria.vue';

defineProps({
    nome: {
        type: String,
        required: true,
    },
    icone: {
        type: String,
        default: 'category',
    },
    cor: {
        type: String,
        default: undefined,
    },
    descricao: {
        type: String,
        default: '',
    },
    padrao: {
        type: Boolean,
        default: false,
    },
    subcategoria: {
        type: Boolean,
        default: false,
    },
    arquivada: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        class="flex items-center gap-3 transition"
        :class="[
            subcategoria ? 'bg-ng-card-muted py-3 pl-8 pr-4 sm:pl-12 sm:pr-5' : 'px-4 py-4 sm:px-5',
            arquivada ? 'opacity-70' : '',
        ]"
    >
        <span
            v-if="subcategoria"
            class="material-symbols-outlined shrink-0 text-[16px] text-ng-ink-subtle"
            aria-hidden="true"
        >
            subdirectory_arrow_right
        </span>

        <IconeCategoria :icone="icone" :cor="cor" :tamanho="subcategoria ? 'sm' : 'md'" />

        <div class="min-w-0 flex-1">
            <div class="flex min-w-0 items-center gap-2">
                <p class="truncate text-ng-ink" :class="subcategoria ? 'text-xs font-semibold' : 'text-sm font-semibold'">
                    {{ nome }}
                </p>
                <BadgeStatus v-if="padrao" texto="Padrão" variante="brand" compacto />
            </div>
            <p v-if="descricao" class="truncate text-ng-ink-muted" :class="subcategoria ? 'text-[11px]' : 'text-xs'">
                {{ descricao }}
            </p>
        </div>

        <div v-if="$slots.acoes || $slots['antes-acoes']" class="flex shrink-0 items-center gap-1">
            <slot name="antes-acoes" />
            <slot name="acoes" />
        </div>
    </div>
</template>
