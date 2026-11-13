<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { estiloDaVariante } from '@/Helpers/varianteCor';

const props = defineProps({
    titulo: {
        type: String,
        required: true,
    },
    icone: {
        type: String,
        default: 'bolt',
    },
    partes: {
        type: Array,
        default: () => [],
    },
    variante: {
        type: String,
        default: 'brand',
    },
    linkRotulo: {
        type: String,
        default: '',
    },
    linkHref: {
        type: String,
        default: '',
    },
});

const estilo = computed(() => estiloDaVariante(props.variante));
</script>

<template>
    <article class="rounded-2xl border p-5" :class="estilo.selecionavelAtivo">
        <p class="flex items-center gap-2 text-sm font-semibold" :class="estilo.texto">
            <span class="material-symbols-outlined text-[18px]">{{ icone }}</span>
            {{ titulo }}
        </p>

        <p class="mt-3 text-xs leading-relaxed text-ng-ink-secondary">
            <template v-for="(parte, indice) in partes" :key="indice">
                <strong v-if="parte.destaque" class="font-mono font-semibold" :class="estilo.texto">{{ parte.texto }}</strong>
                <template v-else>{{ parte.texto }}</template>
            </template>
        </p>

        <Link
            v-if="linkHref && linkRotulo"
            :href="linkHref"
            class="mt-4 inline-flex items-center gap-1 text-xs font-semibold transition hover:opacity-80"
            :class="estilo.texto"
        >
            {{ linkRotulo }}
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </Link>
    </article>
</template>
