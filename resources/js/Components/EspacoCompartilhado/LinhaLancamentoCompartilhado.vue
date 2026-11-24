<script setup>
import { computed } from 'vue';
import AvatarMembro from '@/Components/EspacoCompartilhado/AvatarMembro.vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import {
    iconeStatusLancamentoCompartilhado,
    varianteStatusLancamentoCompartilhado,
} from '@/Helpers/espacoCompartilhado';

const props = defineProps({
    lancamento: {
        type: Object,
        required: true,
    },
});

const nomeResponsavel = computed(() => props.lancamento.responsavel?.nome ?? 'Sem responsável');

const subtitulo = computed(() => {
    const partes = [`Vence ${props.lancamento.data_vencimento_formatada}`, nomeResponsavel.value];

    if (props.lancamento.esta_pago && props.lancamento.data_pagamento_formatada) {
        partes.push(`pago em ${props.lancamento.data_pagamento_formatada}`);
    }

    return partes.join(' · ');
});
</script>

<template>
    <div class="flex items-center gap-3 px-4 py-3.5">
        <AvatarMembro
            :nome="nomeResponsavel"
            :iniciais="lancamento.responsavel?.iniciais ?? '?'"
            :foto-url="lancamento.responsavel?.foto_url"
            :destacado="lancamento.eh_responsavel_usuario_atual"
            tamanho="sm"
        />

        <div class="min-w-0 flex-1">
            <div class="flex min-w-0 items-center gap-2">
                <p class="truncate text-sm font-semibold text-ng-ink">{{ lancamento.descricao }}</p>
                <span
                    v-if="lancamento.eh_recorrente"
                    class="inline-flex shrink-0 items-center gap-0.5 rounded-md border border-ng-line-strong bg-ng-card-muted px-1.5 py-0.5 font-mono text-[10px] text-ng-ink-muted"
                >
                    <span class="material-symbols-outlined text-[12px] text-ng-brand">autorenew</span>
                    Recorrente
                </span>
                <BadgeStatus
                    :texto="lancamento.status_rotulo ?? ''"
                    :variante="varianteStatusLancamentoCompartilhado(lancamento)"
                    :icone="iconeStatusLancamentoCompartilhado(lancamento)"
                    compacto
                />
            </div>
            <p class="truncate font-mono text-[11px] text-ng-ink-muted">{{ subtitulo }}</p>
        </div>

        <p class="shrink-0 font-mono text-sm font-semibold tabular-nums text-ng-ink">R$ {{ lancamento.valor }}</p>

        <div v-if="$slots.acoes" class="flex shrink-0 items-center">
            <slot name="acoes" />
        </div>
    </div>
</template>
