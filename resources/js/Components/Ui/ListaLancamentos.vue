<script setup>
import LinhaLancamento from '@/Components/Ui/LinhaLancamento.vue';

defineProps({
    itens: {
        type: Array,
        default: () => [],
    },
    mensagemVazia: {
        type: String,
        default: '',
    },
    semMoldura: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div :class="semMoldura ? '' : 'ng-card overflow-hidden'">
        <div v-if="itens.length > 0" class="divide-y divide-ng-line">
            <template v-for="item in itens" :key="item.chave">
                <slot name="item" :item="item">
                    <LinhaLancamento
                        :descricao="item.descricao"
                        :subtitulo="item.subtitulo"
                        :subtitulo-mono="item.subtituloMono"
                        :valor="item.valor"
                        :legenda-valor="item.legendaValor"
                        :icone="item.icone"
                        :cor="item.cor"
                        :etiqueta="item.etiqueta"
                        :etiqueta-icone="item.etiquetaIcone"
                        :badge-texto="item.badgeTexto"
                        :badge-variante="item.badgeVariante"
                        :badge-icone="item.badgeIcone"
                        :badge-na-descricao="item.badgeNaDescricao"
                        :negativo="item.negativo"
                        :positivo="item.positivo"
                    >
                        <template v-if="$slots.acoes" #acoes>
                            <slot name="acoes" :item="item" />
                        </template>
                    </LinhaLancamento>
                </slot>
            </template>
        </div>
        <p v-else class="px-6 py-10 text-center text-sm text-ng-ink-muted">
            {{ mensagemVazia }}
        </p>
    </div>
</template>
