<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import SeletorCategoriaUsuario from '@/Components/Categoria/SeletorCategoriaUsuario.vue';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    mensagem: {
        type: String,
        default: '',
    },
    categorias: {
        type: Array,
        default: () => [],
    },
    processando: {
        type: Boolean,
        default: false,
    },
    erroCategoria: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['confirmar', 'cancelar']);

const idCategoria = ref(null);

watch(
    () => props.aberto,
    (aberto) => {
        if (aberto) {
            idCategoria.value = null;
        }
    },
);

function confirmarAssumir() {
    emit('confirmar', idCategoria.value);
}
</script>

<template>
    <Modal :aberto="aberto" max-largura="md">
        <div class="p-6">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full ng-tint ng-tint--brand">
                    <span class="material-symbols-outlined text-[22px]">swap_horiz</span>
                </div>

                <div class="min-w-0 flex-1 space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-ng-ink">Assumir lançamento</h3>
                        <p class="mt-2 text-sm text-ng-ink-muted whitespace-pre-line">{{ mensagem }}</p>
                    </div>

                    <SeletorCategoriaUsuario
                        v-model="idCategoria"
                        rotulo="Sua categoria"
                        :categorias="categorias"
                        :desabilitado="processando"
                        :erro="erroCategoria"
                        id-prefixo="assumir-categoria"
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-ng-line-strong px-4 py-2 text-sm font-semibold text-ng-ink-secondary hover:bg-ng-brand-soft transition disabled:opacity-60"
                    :disabled="processando"
                    @click="emit('cancelar')"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#198a68] disabled:opacity-60"
                    :disabled="processando || !idCategoria"
                    @click="confirmarAssumir"
                >
                    {{ processando ? 'Aguarde...' : 'Assumir e dar baixa' }}
                </button>
            </div>
        </div>
    </Modal>
</template>
