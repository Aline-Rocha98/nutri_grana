<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConvidadoLayout from '@/Layouts/ConvidadoLayout.vue';

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const pagina = usePage();
const rotas = computed(() => pagina.props.rotas);

const formulario = useForm({});

function reenviar() {
    formulario.post(rotas.value.verificationSend);
}
</script>

<template>
    <Head title="Verificar e-mail" />

    <ConvidadoLayout>
        <div class="bg-white/95 backdrop-blur-xl shadow-2xl rounded-2xl w-full max-w-md p-8 border border-white/20 text-center space-y-4">
            <h1 class="text-xl font-semibold text-gray-900">Verifique seu e-mail</h1>
            <p class="text-sm text-gray-600">
                Obrigado por se cadastrar. Antes de começar, verifique seu e-mail clicando no link enviado.
            </p>
            <p v-if="status === 'verification-link-sent'" class="text-sm text-green-600">
                Um novo link de verificação foi enviado.
            </p>
            <button
                type="button"
                class="w-full bg-[#1fa67e] hover:bg-[#188f6b] text-white font-semibold py-2.5 rounded-lg"
                :disabled="formulario.processing"
                @click="reenviar"
            >
                Reenviar e-mail de verificação
            </button>
        </div>
    </ConvidadoLayout>
</template>
