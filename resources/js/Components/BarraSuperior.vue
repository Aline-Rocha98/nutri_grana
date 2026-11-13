<script setup>
import TrilhaNavegacao from '@/Components/Ui/TrilhaNavegacao.vue';
import { montarTrilhaDoMenuAtivo } from '@/Helpers/menuNavegacao';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    titulo: {
        type: String,
        default: '',
    },
});

const pagina = usePage();

const itensTrilha = computed(() => {
    const trilhaDoMenu = montarTrilhaDoMenuAtivo(pagina.props.menu?.itens ?? []);

    if (trilhaDoMenu.length) {
        return trilhaDoMenu;
    }

    return props.titulo ? [{ rotulo: props.titulo }] : [];
});
</script>

<template>
    <header
        class="ng-header sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-4 px-6"
    >
        <TrilhaNavegacao :itens="itensTrilha" />

        <div v-if="$slots.acoes" class="flex shrink-0 items-center gap-2">
            <slot name="acoes" />
        </div>
    </header>
</template>
