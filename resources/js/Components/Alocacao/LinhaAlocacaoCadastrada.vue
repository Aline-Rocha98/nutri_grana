<script setup>
import { computed, ref, watch } from 'vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';

const MENSAGEM_EXCLUSAO_BLOQUEADA = 'Em uso em planos ou com histórico de movimentações';

const props = defineProps({
    nome: {
        type: String,
        required: true,
    },
    totalItensPlano: {
        type: Number,
        default: 0,
    },
    totalMovimentacoes: {
        type: Number,
        default: 0,
    },
    podeExcluir: {
        type: Boolean,
        default: false,
    },
    erro: {
        type: String,
        default: '',
    },
    processando: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['renomear', 'excluir']);

const editando = ref(false);
const confirmandoExclusao = ref(false);
const nomeEditado = ref(props.nome);

watch(() => props.nome, (nomeAtual) => {
    nomeEditado.value = nomeAtual;
    editando.value = false;
});

const descricaoUso = computed(() => {
    const partes = [];

    if (props.totalItensPlano > 0) {
        partes.push(props.totalItensPlano === 1 ? '1 item de plano' : `${props.totalItensPlano} itens de plano`);
    }

    if (props.totalMovimentacoes > 0) {
        partes.push(props.totalMovimentacoes === 1 ? '1 movimentação' : `${props.totalMovimentacoes} movimentações`);
    }

    return partes.length ? partes.join(' · ') : 'Ainda não utilizada';
});

const podeSalvarNome = computed(() => nomeEditado.value.trim() !== '' && nomeEditado.value.trim() !== props.nome);

function iniciarEdicaoNome() {
    nomeEditado.value = props.nome;
    confirmandoExclusao.value = false;
    editando.value = true;
}

function cancelarEdicaoNome() {
    nomeEditado.value = props.nome;
    editando.value = false;
}

function salvarNomeAlocacao() {
    if (!podeSalvarNome.value || props.processando) {
        return;
    }

    emit('renomear', nomeEditado.value.trim());
}

function pedirConfirmacaoExclusao() {
    if (!props.podeExcluir) {
        return;
    }

    editando.value = false;
    confirmandoExclusao.value = true;
}

function confirmarExclusao() {
    confirmandoExclusao.value = false;
    emit('excluir');
}
</script>

<template>
    <li class="space-y-1 px-4 py-3">
        <div class="flex items-center gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-500/10 text-sky-500">
                <span class="material-symbols-outlined text-[16px]">savings</span>
            </span>

            <div v-if="editando" class="flex min-w-0 flex-1 items-center gap-2">
                <input
                    v-model="nomeEditado"
                    type="text"
                    maxlength="120"
                    aria-label="Novo nome da alocação"
                    class="ng-input min-w-0 flex-1 rounded-lg text-sm"
                    :class="erro ? '!border-red-500' : ''"
                    @keydown.enter.prevent="salvarNomeAlocacao"
                    @keydown.esc.prevent="cancelarEdicaoNome"
                >
                <BotaoIcone icone="check" rotulo="Salvar nome" :desabilitado="!podeSalvarNome || processando" @click="salvarNomeAlocacao" />
                <BotaoIcone icone="close" rotulo="Cancelar" @click="cancelarEdicaoNome" />
            </div>

            <div v-else class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-ng-ink">{{ nome }}</p>
                <p class="text-[11px] text-ng-ink-subtle">{{ descricaoUso }}</p>
            </div>

            <div v-if="!editando && !confirmandoExclusao" class="flex shrink-0 items-center">
                <BotaoIcone icone="edit" rotulo="Renomear" @click="iniciarEdicaoNome" />
                <BotaoIcone
                    icone="delete"
                    :rotulo="podeExcluir ? 'Excluir' : MENSAGEM_EXCLUSAO_BLOQUEADA"
                    variante="perigo"
                    :desabilitado="!podeExcluir"
                    @click="pedirConfirmacaoExclusao"
                />
            </div>

            <div v-if="confirmandoExclusao" class="flex shrink-0 items-center gap-2 text-xs">
                <span class="text-ng-ink-muted">Excluir?</span>
                <button
                    type="button"
                    class="font-semibold text-red-500 transition hover:text-red-400 disabled:opacity-50"
                    :disabled="processando"
                    @click="confirmarExclusao"
                >
                    Sim
                </button>
                <button
                    type="button"
                    class="font-semibold text-ng-ink-muted transition hover:text-ng-ink"
                    @click="confirmandoExclusao = false"
                >
                    Não
                </button>
            </div>
        </div>

        <p v-if="erro" class="pl-11 text-xs text-red-500">{{ erro }}</p>
    </li>
</template>
