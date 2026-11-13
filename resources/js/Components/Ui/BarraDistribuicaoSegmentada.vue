<script setup>
import { computed } from 'vue';

const props = defineProps({
    segmentos: {
        type: Array,
        default: () => [],
    },
    altura: {
        type: String,
        default: 'h-2',
    },
    corPadrao: {
        type: String,
        default: '#1fa67e',
    },
});

const segmentosVisiveis = computed(() => {
    let percentualAcumulado = 0;

    return props.segmentos.reduce((visiveis, segmento) => {
        const espacoRestante = Math.max(0, 100 - percentualAcumulado);
        const largura = Math.min(Math.max(0, Number(segmento.percentual) || 0), espacoRestante);

        if (largura > 0) {
            percentualAcumulado += largura;
            visiveis.push({
                chave: segmento.chave,
                rotulo: segmento.rotulo ?? '',
                estilo: { width: `${largura}%`, backgroundColor: segmento.cor || props.corPadrao },
            });
        }

        return visiveis;
    }, []);
});
</script>

<template>
    <div
        class="flex w-full gap-0.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10"
        :class="altura"
        role="img"
        :aria-label="segmentosVisiveis.map((segmento) => segmento.rotulo).filter(Boolean).join(', ')"
    >
        <span
            v-for="segmento in segmentosVisiveis"
            :key="segmento.chave"
            class="h-full shrink-0 transition-all duration-500 ease-smooth first:rounded-l-full last:rounded-r-full"
            :style="segmento.estilo"
            :title="segmento.rotulo"
        />
    </div>
</template>
