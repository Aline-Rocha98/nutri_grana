<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import ConvidadoLayout from '@/Layouts/ConvidadoLayout.vue';

const formulario = useForm({
    password: '',
});

function enviar() {
    formulario.post('/confirm-password', {
        onFinish: () => formulario.reset('password'),
    });
}
</script>

<template>
    <Head title="Confirmar senha" />

    <ConvidadoLayout>
        <div class="bg-white/95 backdrop-blur-xl shadow-2xl rounded-2xl w-full max-w-md p-8 border border-white/20">
            <h1 class="text-xl font-semibold text-gray-900 text-center">Confirmar senha</h1>
            <p class="mt-2 text-sm text-gray-600 text-center">
                Esta é uma área segura. Confirme sua senha para continuar.
            </p>

            <form class="mt-6 space-y-4" @submit.prevent="enviar">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-600">Senha</label>
                    <input
                        id="password"
                        v-model="formulario.password"
                        type="password"
                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        required
                    >
                    <p v-if="formulario.errors.password" class="mt-1 text-sm text-red-600">
                        {{ formulario.errors.password }}
                    </p>
                </div>

                <button
                    type="submit"
                    class="w-full bg-[#1fa67e] hover:bg-[#188f6b] text-white font-semibold py-2.5 rounded-lg"
                    :disabled="formulario.processing"
                >
                    Confirmar
                </button>
            </form>
        </div>
    </ConvidadoLayout>
</template>
