<script setup>
import BarraLateral from '@/Components/BarraLateral.vue';

defineProps({
    titulo: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="ng-page font-sans antialiased">
        <div class="flex min-h-screen gap-3 p-3">
            <BarraLateral />

            <div class="flex min-w-0 flex-1 flex-col">
                <header
                    v-if="titulo || $slots.cabecalho"
                    class="ng-header mb-3 rounded-2xl px-6 py-4"
                >
                    <slot name="cabecalho">
                        <h2 class="font-semibold text-xl text-ng-ink leading-tight tracking-tight">
                            {{ titulo }}
                        </h2>
                    </slot>
                </header>

                <slot />
            </div>
        </div>
    </div>
</template>
