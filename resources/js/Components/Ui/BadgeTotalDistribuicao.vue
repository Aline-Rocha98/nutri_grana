<script setup>
import { computed } from 'vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';

const props = defineProps({
    percentual: {
        type: Number,
        default: 0,
    },
    percentualMeta: {
        type: Number,
        default: 100,
    },
});

function formatarPercentual(valor) {
    return `${Math.round(valor * 100) / 100}`.replace('.', ',');
}

const estado = computed(() => {
    const diferenca = Math.round((props.percentualMeta - props.percentual) * 100) / 100;

    if (diferenca === 0) {
        return { texto: `Total balanceado (${formatarPercentual(props.percentualMeta)}%)`, variante: 'emerald', icone: 'check_circle' };
    }

    if (diferenca > 0) {
        return { texto: `Faltam ${formatarPercentual(diferenca)}%`, variante: 'amber', icone: 'hourglass_top' };
    }

    return { texto: `Excedeu ${formatarPercentual(Math.abs(diferenca))}%`, variante: 'red', icone: 'error' };
});
</script>

<template>
    <BadgeStatus :texto="estado.texto" :variante="estado.variante" :icone="estado.icone" />
</template>
