<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import LinhaAlternancia from '@/Components/Formulario/LinhaAlternancia.vue';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    lancamento: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['fechar']);

const encerrarRecorrencia = ref(false);
const excluindo = ref(false);

const mensagem = computed(() => {
    const descricao = props.lancamento?.descricao ?? 'este lançamento';

    return props.lancamento?.eh_recorrente
        ? `"${descricao}" será removido deste mês. As próximas ocorrências continuam sendo geradas, a menos que você encerre a recorrência.`
        : `Deseja excluir "${descricao}"? Essa ação fica registrada no histórico do espaço.`;
});

watch(
    () => props.aberto,
    (aberto) => {
        if (aberto) {
            encerrarRecorrencia.value = false;
        }
    },
);

function fechar() {
    if (!excluindo.value) {
        emit('fechar');
    }
}

function excluirLancamentoCompartilhado() {
    if (!props.lancamento || excluindo.value) {
        return;
    }

    excluindo.value = true;

    router.delete(props.lancamento.url_excluir, {
        data: { encerrar_recorrencia: encerrarRecorrencia.value },
        preserveScroll: true,
        onFinish: () => {
            excluindo.value = false;
            emit('fechar');
        },
    });
}
</script>

<template>
    <Modal :aberto="aberto" max-largura="sm">
        <div class="p-6">
            <div class="flex items-start gap-3">
                <div class="ng-tint ng-tint--red flex h-10 w-10 shrink-0 items-center justify-center rounded-full">
                    <span class="material-symbols-outlined text-[22px]">warning</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-semibold text-ng-ink">Excluir lançamento</h3>
                    <p class="mt-2 text-sm text-ng-ink-muted">{{ mensagem }}</p>
                </div>
            </div>

            <LinhaAlternancia
                v-if="lancamento?.eh_recorrente"
                v-model="encerrarRecorrencia"
                class="mt-4"
                icone="event_busy"
                titulo="Encerrar recorrência"
                descricao="Cancela também as próximas ocorrências"
                :valor-ativo="true"
                :valor-inativo="false"
            />

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-ng-line-strong px-4 py-2 text-sm font-semibold text-ng-ink-secondary transition hover:bg-ng-brand-soft disabled:opacity-60"
                    :disabled="excluindo"
                    @click="fechar"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="ng-btn-danger"
                    :disabled="excluindo"
                    @click="excluirLancamentoCompartilhado"
                >
                    {{ excluindo ? 'Aguarde...' : 'Excluir' }}
                </button>
            </div>
        </div>
    </Modal>
</template>
