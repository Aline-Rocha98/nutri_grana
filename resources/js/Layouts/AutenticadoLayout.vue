<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BarraLateral from '@/Components/BarraLateral.vue';

defineProps({
    titulo: {
        type: String,
        default: '',
    },
});

const pagina = usePage();
const flash = computed(() => pagina.props.flash ?? {});

const mensagemErro = computed(() => {
    const erro = flash.value.erro;
    if (!erro) {
        return null;
    }
    if (typeof erro === 'string') {
        return erro;
    }
    if (Array.isArray(erro)) {
        return erro.flat().join(' ');
    }
    if (typeof erro === 'object') {
        return Object.values(erro).flat().join(' ');
    }
    return String(erro);
});
</script>

<template>
    <div class="font-sans antialiased bg-[#0c2e24] min-h-screen">
        <div class="flex min-h-screen gap-3 p-3">
            <BarraLateral />

            <div class="flex min-w-0 flex-1 flex-col">
                <header
                    v-if="titulo || $slots.cabecalho"
                    class="mb-3 rounded-2xl bg-white/95 px-6 py-4 shadow-sm border border-white/20"
                >
                    <slot name="cabecalho">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ titulo }}
                        </h2>
                    </slot>
                </header>

                <Alerta :mensagem="flash.sucesso" tipo="sucesso" />
                <Alerta :mensagem="mensagemErro" tipo="erro" />
                <slot />
                <!-- <main class="flex-1 rounded-[2rem] bg-gray-100 shadow-inner overflow-auto">
                    <div v-if="flash.sucesso" class="p-6 pb-0">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            {{ flash.sucesso }}
                        </div>
                    </div>

                    <div v-if="mensagemErro" class="p-6 pb-0">
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ mensagemErro }}
                        </div>
                    </div>

                    <slot />
                </main> -->
            </div>
        </div>
    </div>
</template>
