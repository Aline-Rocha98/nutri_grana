<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
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

const formulario = useForm({
    email: '',
});

function enviar() {
    formulario.post(rotas.value.passwordEmail);
}
</script>

<template>
    <Head title="Recuperar senha" />

    <ConvidadoLayout>
        <div class="bg-white/95 backdrop-blur-xl shadow-2xl rounded-2xl w-full max-w-md p-8 border border-white/20">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#1fa67e] tracking-wide">NutriGrana</h1>
                <p class="text-gray-500 text-sm mt-2">Recuperação de senha</p>
            </div>

            <p class="text-sm text-gray-600 mb-6 text-center">
                Informe seu e-mail cadastrado. Enviaremos um link para você redefinir sua senha.
            </p>

            <div
                v-if="status"
                class="mb-4 text-sm font-medium text-green-600 text-center"
            >
                {{ status }}
            </div>

            <form class="space-y-5" @submit.prevent="enviar">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-600">E-mail</label>
                    <input
                        id="email"
                        v-model="formulario.email"
                        type="email"
                        autofocus
                        autocomplete="username"
                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm"
                    >
                    <p v-if="formulario.errors.email" class="mt-1 text-sm text-red-600">
                        {{ formulario.errors.email }}
                    </p>
                </div>

                <button
                    type="submit"
                    class="w-full bg-[#1fa67e] hover:bg-[#188f6b] text-white font-semibold py-2.5 rounded-lg shadow-lg transition duration-300"
                    :disabled="formulario.processing"
                >
                    Enviar link de redefinição
                </button>

                <div class="text-center text-sm text-gray-500 mt-4">
                    Lembrou a senha?
                    <Link :href="rotas.login" class="text-[#1fa67e] font-semibold hover:underline">
                        Fazer login
                    </Link>
                </div>
            </form>
        </div>
    </ConvidadoLayout>
</template>
