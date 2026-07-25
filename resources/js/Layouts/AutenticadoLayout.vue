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
    <div class="font-sans antialiased bg-[#0c2e24] min-h-screen">
        <div class="flex min-h-screen gap-3 p-3">
            <BarraLateral />

            <div class="flex min-w-0 flex-1 flex-col">
                <header
                    v-if="titulo || $slots.cabecalho"
                    class="mb-3 rounded-2xl bg-white/95 px-6 py-4 shadow-sm border border-white/20"
                >
                    <slot name="cabecalho">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ titulo }}
                        </h2>
                    </slot>
                </header>

                <slot />
            </div>
        </div>
    </div>
</template>
