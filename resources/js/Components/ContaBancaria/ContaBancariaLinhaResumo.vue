<script setup>
import { computed } from 'vue';
import AvatarInstituicao from '@/Components/Ui/AvatarInstituicao.vue';
import { iconeTipoContaBancaria } from '@/Helpers/tipoContaBancaria';

const props = defineProps({
    nome: {
        type: String,
        required: true,
    },
    tipo: {
        type: String,
        default: '',
    },
    tipoRotulo: {
        type: String,
        default: '',
    },
    logo: {
        type: String,
        default: null,
    },
    prefixoMoeda: {
        type: String,
        default: 'R$',
    },
    saldoDisponivel: {
        type: String,
        default: '0,00',
    },
    saldoDisponivelNumero: {
        type: Number,
        default: 0,
    },
    rotuloSaldo: {
        type: String,
        default: 'Disponível',
    },
});

const saldoNegativo = computed(() => props.saldoDisponivelNumero < 0);
</script>

<template>
    <div class="ng-card-muted flex items-center justify-between gap-3 px-4 py-3">
        <div class="flex min-w-0 items-center gap-3">
            <AvatarInstituicao
                :logo="logo"
                :icone="iconeTipoContaBancaria(tipo)"
                :rotulo="nome"
                tamanho="sm"
                com-moldura
            />
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-ng-ink">{{ nome }}</p>
                <p v-if="tipoRotulo" class="truncate text-[11px] text-ng-ink-muted">{{ tipoRotulo }}</p>
            </div>
        </div>

        <div class="shrink-0 text-right">
            <p
                class="font-mono text-sm font-semibold tabular-nums"
                :class="saldoNegativo ? 'text-rose-500' : 'text-ng-brand'"
            >
                {{ prefixoMoeda }} {{ saldoDisponivel }}
            </p>
            <p class="text-[10px] font-medium uppercase tracking-wider text-ng-ink-subtle">{{ rotuloSaldo }}</p>
        </div>
    </div>
</template>
