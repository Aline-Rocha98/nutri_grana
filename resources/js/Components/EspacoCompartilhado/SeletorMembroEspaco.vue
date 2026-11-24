<script setup>
import AvatarMembro from '@/Components/EspacoCompartilhado/AvatarMembro.vue';

const props = defineProps({
    modelValue: {
        type: Number,
        default: null,
    },
    membros: {
        type: Array,
        default: () => [],
    },
    rotulo: {
        type: String,
        default: 'Responsável',
    },
    erro: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

function estaSelecionado(membro) {
    return Number(membro.id_usuario) === Number(props.modelValue);
}
</script>

<template>
    <div class="space-y-1.5">
        <span class="block text-xs font-medium text-ng-ink-secondary">{{ rotulo }} <span class="text-red-500">*</span></span>

        <div role="radiogroup" :aria-label="rotulo" class="flex flex-wrap gap-2">
            <button
                v-for="membro in membros"
                :key="membro.id_usuario"
                type="button"
                role="radio"
                :aria-checked="estaSelecionado(membro)"
                class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-medium transition"
                :class="estaSelecionado(membro)
                    ? 'ng-selecionavel--ativo text-ng-brand'
                    : 'border-ng-line-strong text-ng-ink-muted hover:border-ng-ink-subtle hover:text-ng-ink'"
                @click="emit('update:modelValue', membro.id_usuario)"
            >
                <AvatarMembro
                    :nome="membro.nome"
                    :iniciais="membro.iniciais"
                    :foto-url="membro.foto_url"
                    tamanho="xs"
                    :destacado="estaSelecionado(membro)"
                />
                {{ membro.nome }}
                <span v-if="membro.eh_usuario_atual" class="text-ng-ink-subtle">(você)</span>
            </button>
        </div>

        <p v-if="erro" class="text-xs text-red-500">{{ erro }}</p>
    </div>
</template>
