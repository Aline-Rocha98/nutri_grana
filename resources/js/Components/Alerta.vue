<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    mensagem: { type: String, default: null },
    tipo: { type: String, default: 'sucesso' }, // sucesso | erro | aviso
    duracao: { type: Number, default: 4000 },
});

const visivel = ref(false);
let timer = null;

const classes = computed(() => ({
    sucesso: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    erro: 'border-red-200 bg-red-50 text-red-700',
    aviso: 'border-amber-200 bg-amber-50 text-amber-800',
}[props.tipo] ?? 'border-emerald-200 bg-emerald-50 text-emerald-800'));

function limparTimer() {
    if (timer) {
        clearTimeout(timer);
        timer = null;
    }
}
 
function exibirAlerta() {
    limparTimer();
    visivel.value = true;
    timer = setTimeout(() => {
        visivel.value = false;
    }, props.duracao);
}

watch(
    () => props.mensagem,
    (valor) => {
        if (valor) exibirAlerta();
        else visivel.value = false;
    },
    { immediate: true },
);

onUnmounted(limparTimer);
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visivel && mensagem"
            class="fixed top-6 right-6 z-50 max-w-md rounded-xl border px-4 py-3 text-sm shadow-sm"
            :class="classes"
            role="alert"
        >
            {{ mensagem }}
        </div>
    </Transition>
</template>