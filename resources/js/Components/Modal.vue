<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';
import { estiloDaVariante } from '@/Helpers/varianteCor';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    maxLargura: {
        type: String,
        default: '2xl',
    },
    titulo: {
        type: String,
        default: '',
    },
    subtitulo: {
        type: String,
        default: '',
    },
    icone: {
        type: String,
        default: '',
    },
    textoConfirmar: {
        type: String,
        default: 'Salvar',
    },
    textoCancelar: {
        type: String,
        default: 'Cancelar',
    },
    processando: {
        type: Boolean,
        default: false,
    },
    desabilitarConfirmar: {
        type: Boolean,
        default: false,
    },
    varianteIcone: {
        type: String,
        default: 'brand',
    },
});

const emit = defineEmits(['fechar', 'confirmar']);

const classesMax = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
};

const usaLayoutCadastro = computed(() => Boolean(props.titulo));
const estiloIcone = computed(() => estiloDaVariante(props.varianteIcone));

function fecharModal() {
    if (props.processando) {
        return;
    }

    emit('fechar');
}

function confirmarModal() {
    if (props.processando || props.desabilitarConfirmar) {
        return;
    }

    emit('confirmar');
}

function aoPressionarTecla(evento) {
    if (evento.key === 'Escape' && props.aberto && usaLayoutCadastro.value) {
        fecharModal();
    }
}

watch(
    () => props.aberto,
    (aberto) => {
        document.body.classList.toggle('overflow-y-hidden', aberto);

        if (aberto) {
            document.addEventListener('keydown', aoPressionarTecla);
            return;
        }

        document.removeEventListener('keydown', aoPressionarTecla);
    },
);

onBeforeUnmount(() => {
    document.removeEventListener('keydown', aoPressionarTecla);

    if (props.aberto) {
        document.body.classList.remove('overflow-y-hidden');
    }
});
</script>

<template>
    <Teleport to="body">
        <div
            v-show="aberto"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6 sm:px-0"
            style="background-color: var(--ng-overlay)"
        >
            <div
                class="ng-card relative w-full overflow-hidden shadow-2xl sm:mx-auto sm:w-full"
                :class="[classesMax[maxLargura] ?? classesMax['2xl'], usaLayoutCadastro ? '' : 'mb-6']"
            >
                <form
                    v-if="usaLayoutCadastro"
                    class="flex max-h-[90vh] flex-col"
                    novalidate
                    @submit.prevent="confirmarModal"
                >
                    <header class="flex shrink-0 items-start gap-3 border-b border-ng-line px-5 py-4">
                        <span
                            v-if="icone"
                            class="h-10 w-10 rounded-xl transition-colors"
                            :class="estiloIcone.icone"
                        >
                            <span class="material-symbols-outlined text-[20px]">{{ icone }}</span>
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-base font-semibold text-ng-ink">{{ titulo }}</h2>
                            <p v-if="subtitulo" class="mt-0.5 text-xs text-ng-ink-muted">{{ subtitulo }}</p>
                        </div>

                        <slot name="cabecalho-acoes" />

                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-ng-ink-muted transition hover:bg-ng-brand-soft hover:text-ng-ink"
                            :aria-label="textoCancelar"
                            @click="fecharModal"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </header>

                    <div class="ng-scroll min-h-0 flex-1 overflow-y-auto px-5 py-5">
                        <slot />
                    </div>

                    <footer class="flex shrink-0 items-center justify-end gap-3 border-t border-ng-line px-5 py-4">
                        <slot name="rodape" :fechar="fecharModal" :processando="processando">
                            <button
                                type="button"
                                class="rounded-lg px-4 py-2.5 text-sm font-medium text-ng-ink-muted transition hover:text-ng-ink"
                                :disabled="processando"
                                @click="fecharModal"
                            >
                                {{ textoCancelar }}
                            </button>
                            <button
                                type="submit"
                                class="ng-btn-primary px-5 py-2.5 text-sm normal-case tracking-normal"
                                :disabled="processando || desabilitarConfirmar"
                            >
                                {{ textoConfirmar }}
                            </button>
                        </slot>
                    </footer>
                </form>

                <slot v-else />
            </div>
        </div>
    </Teleport>
</template>
