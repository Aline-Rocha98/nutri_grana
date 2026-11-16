<script setup>
import { computed } from 'vue';
import AvatarMembro from '@/Components/EspacoCompartilhado/AvatarMembro.vue';

const props = defineProps({
    membros: {
        type: Array,
        default: () => [],
    },
    maximoVisivel: {
        type: Number,
        default: 4,
    },
    tamanho: {
        type: String,
        default: 'sm',
    },
});

const membrosVisiveis = computed(() => props.membros.slice(0, props.maximoVisivel));
const quantidadeOculta = computed(() => Math.max(0, props.membros.length - props.maximoVisivel));
</script>

<template>
    <div class="flex items-center -space-x-2">
        <AvatarMembro
            v-for="membro in membrosVisiveis"
            :key="membro.id_usuario"
            :nome="membro.nome"
            :iniciais="membro.iniciais"
            :foto-url="membro.foto_url"
            :tamanho="tamanho"
            com-borda
        />
        <span
            v-if="quantidadeOculta > 0"
            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-ng-line-strong bg-ng-card-muted text-[11px] font-semibold text-ng-ink-muted ring-2 ring-[var(--ng-card)]"
        >
            +{{ quantidadeOculta }}
        </span>
    </div>
</template>
