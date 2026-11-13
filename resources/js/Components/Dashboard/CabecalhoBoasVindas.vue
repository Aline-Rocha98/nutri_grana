<script setup>
import ChipsAlternancia from '@/Components/Ui/ChipsAlternancia.vue';

defineProps({
    nomeUsuario: {
        type: String,
        required: true,
    },
    modulos: {
        type: Object,
        required: true,
    },
    opcoesModulos: {
        type: Array,
        default: () => [],
    },
    modulosOcultos: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['alternar-modulo', 'alternar-visibilidade-modulos']);
</script>

<template>
    <section class="ng-card relative overflow-hidden p-6">
        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-br from-ng-brand-soft via-transparent to-transparent"
            aria-hidden="true"
        />

        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="font-semibold text-ng-brand">Visão Geral das Finanças</span>
                    <span class="text-ng-ink-subtle" aria-hidden="true">·</span>
                    <span class="font-mono text-[11px] text-ng-ink-muted">Atualizado em tempo real</span>
                </p>
                <h2 class="mt-2 text-xl font-bold text-ng-ink">Olá, {{ nomeUsuario }}!</h2>
                <p class="mt-1 text-sm text-ng-ink-muted">
                    Veja o panorama completo das suas finanças: dinheiro disponível, reservas guardadas, faturas e metas.
                </p>
            </div>

            <button
                type="button"
                class="ng-selecionavel inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-ng-ink-secondary hover:text-ng-ink"
                :aria-expanded="!modulosOcultos"
                @click="emit('alternar-visibilidade-modulos')"
            >
                <span class="material-symbols-outlined text-[16px]">
                    {{ modulosOcultos ? 'visibility' : 'visibility_off' }}
                </span>
                {{ modulosOcultos ? 'Exibir' : 'Ocultar' }}
            </button>
        </div>

        <div v-if="!modulosOcultos" class="relative mt-5 border-t border-ng-line pt-4">
            <ChipsAlternancia
                :model-value="modulos"
                :opcoes="opcoesModulos"
                rotulo="Exibir módulos:"
                icone="tune"
                @alternar="(id) => emit('alternar-modulo', id)"
            />
        </div>
    </section>
</template>
