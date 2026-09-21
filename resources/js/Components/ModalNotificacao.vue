<script setup>
import Modal from '@/Components/Modal.vue';

defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    titulo: {
        type: String,
        default: 'Confirmação',
    },
    mensagem: {
        type: String,
        default: '',
    },
    textoConfirmar: {
        type: String,
        default: 'Confirmar',
    },
    textoCancelar: {
        type: String,
        default: 'Cancelar',
    },
    perigo: {
        type: Boolean,
        default: false,
    },
    processando: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirmar', 'cancelar']);
</script>

<template>
    <Modal :aberto="aberto" max-largura="sm">
        <div class="p-6">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                    :class="perigo ? 'ng-tint ng-tint--red' : 'ng-tint ng-tint--brand'"
                >
                    <span class="material-symbols-outlined text-[22px]">
                        {{ perigo ? 'warning' : 'help' }}
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-semibold text-ng-ink">
                        {{ titulo }}
                    </h3>
                    <p class="mt-2 text-sm text-ng-ink-muted whitespace-pre-line">
                        {{ mensagem }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-ng-line-strong px-4 py-2 text-sm font-semibold text-ng-ink-secondary hover:bg-ng-brand-soft transition disabled:opacity-60"
                    :disabled="processando"
                    @click="emit('cancelar')"
                >
                    {{ textoCancelar }}
                </button>
                <button
                    type="button"
                    :class="perigo ? 'ng-btn-danger' : 'rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#198a68] disabled:opacity-60'"
                    :disabled="processando"
                    @click="emit('confirmar')"
                >
                    {{ processando ? 'Aguarde...' : textoConfirmar }}
                </button>
            </div>
        </div>
    </Modal>
</template>
