<script setup>
import { computed } from 'vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';

const ROTULOS_PADRAO = {
    recomendado: 'Recomendado',
    viavel: 'Viável',
    inviavel: 'Inviável',
    compromete: 'Compromete',
    ok: 'Ok',
    parcela: 'por parcela',
    selecionar: 'Aprovar com este cenário',
    vazio: 'Nenhum cenário simulado.',
};

const props = defineProps({
    cenarios: {
        type: Array,
        default: () => [],
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    permitirSelecao: {
        type: Boolean,
        default: true,
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['selecionar']);

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));

function rotuloDoFluxo(cenario) {
    return cenario.rotulo_fluxo || (cenario.compromete_fluxo ? textos.value.compromete : textos.value.ok);
}
</script>

<template>
    <div class="ng-card-muted overflow-hidden">
        <ul v-if="cenarios.length" class="divide-y divide-ng-line">
            <li
                v-for="cenario in cenarios"
                :key="cenario.rotulo"
                class="flex items-center gap-3 px-4 py-3"
                :class="{ 'bg-ng-brand-soft': cenario.recomendado }"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-ng-ink">{{ cenario.rotulo }}</p>
                        <BadgeStatus
                            v-if="cenario.recomendado"
                            :texto="textos.recomendado"
                            variante="brand"
                            icone="star"
                            compacto
                        />
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                        <BadgeStatus
                            :texto="rotuloDoFluxo(cenario)"
                            :variante="cenario.compromete_fluxo ? 'red' : 'brand'"
                            compacto
                        />
                        <BadgeStatus
                            :texto="cenario.viavel ? textos.viavel : textos.inviavel"
                            :variante="cenario.viavel ? 'emerald' : 'zinc'"
                            compacto
                        />
                    </div>
                </div>

                <div v-if="cenario.valor_parcela" class="shrink-0 text-right">
                    <p class="font-mono text-sm font-semibold tabular-nums text-ng-ink">
                        {{ prefixoMoeda }} {{ cenario.valor_parcela }}
                    </p>
                    <p class="text-[10px] text-ng-ink-subtle">{{ textos.parcela }}</p>
                </div>

                <BotaoIcone
                    v-if="permitirSelecao"
                    icone="task_alt"
                    :rotulo="textos.selecionar"
                    :desabilitado="!cenario.viavel"
                    @click="emit('selecionar', cenario)"
                />
            </li>
        </ul>
        <p v-else class="px-4 py-6 text-center text-sm text-ng-ink-muted">{{ textos.vazio }}</p>
    </div>
</template>
