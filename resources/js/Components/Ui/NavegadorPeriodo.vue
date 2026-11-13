<script setup>
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';

defineProps({
    rotulo: {
        type: String,
        required: true,
    },
    rotuloAnterior: {
        type: String,
        default: 'Período anterior',
    },
    rotuloProximo: {
        type: String,
        default: 'Próximo período',
    },
});

const emit = defineEmits(['anterior', 'proximo']);
</script>

<template>
    <div class="inline-flex items-center gap-1 rounded-xl border border-ng-line-strong bg-ng-card-muted px-1 py-1">
        <BotaoIcone icone="chevron_left" :rotulo="rotuloAnterior" @click="emit('anterior')" />
        <span class="min-w-[8.5rem] text-center font-mono text-xs font-semibold text-ng-ink">{{ rotulo }}</span>
        <BotaoIcone icone="chevron_right" :rotulo="rotuloProximo" @click="emit('proximo')" />
    </div>
</template>
