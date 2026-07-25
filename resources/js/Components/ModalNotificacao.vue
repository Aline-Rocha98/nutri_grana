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
                    :class="perigo ? 'bg-red-50 text-red-600' : 'bg-[#e8f7f1] text-[#1fa67e]'"
                >
                    <span class="material-symbols-outlined text-[22px]">
                        {{ perigo ? 'warning' : 'help' }}
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ titulo }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">
                        {{ mensagem }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition disabled:opacity-60"
                    :disabled="processando"
                    @click="emit('cancelar')"
                >
                    {{ textoCancelar }}
                </button>
                <button
                    type="button"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition disabled:opacity-60"
                    :class="perigo ? 'bg-red-600 hover:bg-red-700' : 'bg-[#1fa67e] hover:bg-[#198a68]'"
                    :disabled="processando"
                    @click="emit('confirmar')"
                >
                    {{ processando ? 'Aguarde...' : textoConfirmar }}
                </button>
            </div>
        </div>
    </Modal>
</template>
