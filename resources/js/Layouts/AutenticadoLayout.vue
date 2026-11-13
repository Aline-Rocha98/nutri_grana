<script setup>
import BarraLateral from '@/Components/BarraLateral.vue';
import BarraSuperior from '@/Components/BarraSuperior.vue';

defineProps({
    titulo: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="ng-page font-sans antialiased">
        <div class="flex min-h-screen">
            <BarraLateral />

            <div class="flex min-w-0 flex-1 flex-col">
                <BarraSuperior :titulo="titulo">
                    <template v-if="$slots.acoes" #acoes>
                        <slot name="acoes" />
                    </template>
                </BarraSuperior>

                <div v-if="$slots.cabecalho" class="px-6 pt-6">
                    <slot name="cabecalho" />
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
