<script setup>
import { computed } from 'vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import BarraDistribuicaoSegmentada from '@/Components/Ui/BarraDistribuicaoSegmentada.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';
import { TIPO_CALCULO_VALOR_FIXO, montarSegmentosDistribuicao } from '@/Helpers/planoFinanceiro';

const ROTULOS_PADRAO = {
    padrao: 'Padrão',
    ativo: 'Ativo',
    inativo: 'Inativo',
    semItens: 'Nenhuma categoria cadastrada.',
    fixo: 'fixo',
    separaDoSaldo: 'Separa do saldo quando a renda é confirmada',
    orientativo: 'Orientativo: não desconta do saldo',
    editar: 'Editar plano',
    excluir: 'Excluir plano',
    definirPadrao: 'Definir como padrão',
    jaPadrao: 'Este já é o plano padrão',
    ativar: 'Ativar plano',
    inativar: 'Inativar plano',
    semUso: 'Não aplicado em rendas',
};

const props = defineProps({
    nome: {
        type: String,
        required: true,
    },
    descricao: {
        type: String,
        default: '',
    },
    padrao: {
        type: Boolean,
        default: false,
    },
    ativo: {
        type: Boolean,
        default: true,
    },
    itens: {
        type: Array,
        default: () => [],
    },
    quantidadeRendas: {
        type: Number,
        default: 0,
    },
    rotulos: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['editar', 'excluir', 'definir-padrao', 'alternar-situacao']);

const textos = computed(() => ({ ...ROTULOS_PADRAO, ...props.rotulos }));

const segmentos = computed(() => montarSegmentosDistribuicao(props.itens));

const textoUsoEmRendas = computed(() => {
    if (props.quantidadeRendas === 0) {
        return textos.value.semUso;
    }

    return props.quantidadeRendas === 1
        ? 'Aplicado em 1 renda'
        : `Aplicado em ${props.quantidadeRendas} rendas`;
});

function regraDoItem(item) {
    return item.tipo_calculo === TIPO_CALCULO_VALOR_FIXO
        ? `${item.regra_formatada} ${textos.value.fixo}`
        : item.regra_formatada;
}
</script>

<template>
    <article
        class="ng-card flex h-full flex-col gap-4 rounded-2xl p-5 transition"
        :class="[
            padrao ? '!border-ng-brand shadow-[0_0_0_1px_rgba(31,166,126,0.25)]' : '',
            ativo ? '' : 'opacity-70',
        ]"
    >
        <header class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <h3 class="truncate text-sm font-semibold text-ng-ink">{{ nome }}</h3>
                <BadgeStatus v-if="padrao" :texto="textos.padrao" variante="zinc" compacto />
            </div>
            <BadgeStatus
                :texto="ativo ? textos.ativo : textos.inativo"
                :variante="ativo ? 'emerald' : 'zinc'"
                compacto
            />
        </header>

        <p v-if="descricao" class="-mt-2 line-clamp-2 text-xs text-ng-ink-muted">{{ descricao }}</p>

        <BarraDistribuicaoSegmentada :segmentos="segmentos" />

        <ul v-if="itens.length" class="space-y-1.5">
            <li
                v-for="item in itens"
                :key="item.id"
                class="flex items-center justify-between gap-3 rounded-lg bg-ng-card-muted px-3 py-2 text-xs"
            >
                <span class="flex min-w-0 items-center gap-2">
                    <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: item.cor_hexadecimal }" />
                    <span class="truncate text-ng-ink-secondary">{{ item.nome }}</span>
                    <span
                        class="material-symbols-outlined shrink-0 text-[14px]"
                        :class="item.separar_do_saldo ? 'text-sky-500' : 'text-ng-ink-subtle'"
                        :title="item.separar_do_saldo ? textos.separaDoSaldo : textos.orientativo"
                    >
                        {{ item.separar_do_saldo ? 'savings' : 'explore' }}
                    </span>
                </span>
                <span class="shrink-0 font-mono tabular-nums text-ng-ink-muted">{{ regraDoItem(item) }}</span>
            </li>
        </ul>
        <p v-else class="text-xs text-ng-ink-subtle">{{ textos.semItens }}</p>

        <footer class="mt-auto flex items-center justify-between gap-3 border-t border-ng-line pt-3">
            <div class="flex items-center gap-1">
                <BotaoIcone icone="edit" :rotulo="textos.editar" @click="emit('editar')" />
                <BotaoIcone
                    icone="star"
                    :rotulo="padrao ? textos.jaPadrao : textos.definirPadrao"
                    :ativo="padrao"
                    :desabilitado="padrao || !ativo"
                    @click="emit('definir-padrao')"
                />
                <BotaoIcone
                    :icone="ativo ? 'toggle_on' : 'toggle_off'"
                    :rotulo="ativo ? textos.inativar : textos.ativar"
                    @click="emit('alternar-situacao')"
                />
                <BotaoIcone icone="delete" :rotulo="textos.excluir" variante="perigo" @click="emit('excluir')" />
            </div>
            <span class="flex items-center gap-1 text-[11px] text-ng-ink-muted">
                <span class="material-symbols-outlined text-[14px] text-ng-brand">payments</span>
                {{ textoUsoEmRendas }}
            </span>
        </footer>
    </article>
</template>
