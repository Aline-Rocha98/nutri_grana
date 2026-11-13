<script setup>
import CabecalhoSecao from '@/Components/Ui/CabecalhoSecao.vue';

defineProps({
    titulo: {
        type: String,
        required: true,
    },
    subtitulo: {
        type: String,
        default: '',
    },
    icone: {
        type: String,
        default: '',
    },
    varianteIcone: {
        type: String,
        default: 'brand',
    },
    carregando: {
        type: Boolean,
        default: false,
    },
    vazio: {
        type: Boolean,
        default: false,
    },
    mensagemVazio: {
        type: String,
        default: 'Nada para exibir por aqui.',
    },
});
</script>

<template>
    <section class="ng-card flex flex-col p-5">
        <CabecalhoSecao :titulo="titulo" :subtitulo="subtitulo" :icone="icone" :variante-icone="varianteIcone">
            <template v-if="$slots.acoes" #acoes>
                <div class="flex flex-wrap items-center gap-4">
                    <slot name="acoes" />
                </div>
            </template>
        </CabecalhoSecao>

        <div class="mt-5 flex-1">
            <slot v-if="carregando" name="carregando" />
            <slot v-else-if="vazio" name="vazio">
                <p class="text-sm text-ng-ink-muted">{{ mensagemVazio }}</p>
            </slot>
            <slot v-else />
        </div>

        <div v-if="$slots.rodape && !carregando && !vazio" class="mt-4 border-t border-ng-line pt-4">
            <slot name="rodape" />
        </div>
    </section>
</template>
