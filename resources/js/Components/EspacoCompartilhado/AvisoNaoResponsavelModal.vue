<script setup>
import Modal from '@/Components/Modal.vue';
import AvatarMembro from '@/Components/EspacoCompartilhado/AvatarMembro.vue';

defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    lancamento: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['assumir', 'fechar']);
</script>

<template>
    <Modal :aberto="aberto" max-largura="sm">
        <div class="p-6">
            <div class="flex items-start gap-3">
                <AvatarMembro
                    :nome="lancamento?.responsavel?.nome ?? ''"
                    :iniciais="lancamento?.responsavel?.iniciais ?? '?'"
                    :foto-url="lancamento?.responsavel?.foto_url"
                />
                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-semibold text-ng-ink">Lançamento de outro membro</h3>
                    <p class="mt-2 text-sm text-ng-ink-muted">
                        Este lançamento pertence a
                        <strong class="text-ng-ink">{{ lancamento?.responsavel?.nome ?? 'outro membro' }}</strong>.
                        Para dar baixa, transfira a responsabilidade para você.
                    </p>
                    <p class="mt-2 text-xs text-ng-ink-subtle">
                        A transferência fica registrada no histórico do espaço.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-ng-line-strong px-4 py-2 text-sm font-semibold text-ng-ink-secondary transition hover:bg-ng-brand-soft"
                    @click="emit('fechar')"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="ng-btn-primary px-4 py-2 text-sm normal-case tracking-normal"
                    @click="emit('assumir')"
                >
                    Assumir lançamento
                </button>
            </div>
        </div>
    </Modal>
</template>
