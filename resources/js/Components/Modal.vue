<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    maxLargura: {
        type: String,
        default: '2xl',
    },
});

const emit = defineEmits(['fechar']);

const classesMax = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
};

function fechar() {
    emit('fechar');
}

function aoTeclar(evento) {
    if (evento.key === 'Escape' && props.aberto) {
        fechar();
    }
}

watch(
    () => props.aberto,
    (aberto) => {
        document.body.classList.toggle('overflow-y-hidden', aberto);
    },
);

onMounted(() => {
    document.addEventListener('keydown', aoTeclar);
});

onUnmounted(() => {
    document.removeEventListener('keydown', aoTeclar);
    document.body.classList.remove('overflow-y-hidden');
});
</script>

<template>
    <Teleport to="body">
        <div
            v-show="aberto"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6 sm:px-0"
        >
            <div
                class="fixed inset-0 transform transition-all bg-gray-500/75"
                @click="fechar"
            />

            <div
                class="relative mb-6 w-full transform overflow-hidden rounded-lg bg-white shadow-xl transition-all sm:mx-auto sm:w-full"
                :class="classesMax[maxLargura] ?? classesMax['2xl']"
                @click.stop
            >
                <slot />
            </div>
        </div>
    </Teleport>
</template>
