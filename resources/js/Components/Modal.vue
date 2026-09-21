<script setup>
import { watch } from 'vue';

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

const classesMax = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
};

watch(
    () => props.aberto,
    (aberto) => {
        document.body.classList.toggle('overflow-y-hidden', aberto);
    },
);
</script>

<template>
    <Teleport to="body">
        <div
            v-show="aberto"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6 sm:px-0"
            style="background-color: var(--ng-overlay)"
        >
            <div
                class="ng-card relative mb-6 w-full overflow-hidden shadow-2xl sm:mx-auto sm:w-full"
                :class="classesMax[maxLargura] ?? classesMax['2xl']"
            >
                <slot />
            </div>
        </div>
    </Teleport>
</template>
