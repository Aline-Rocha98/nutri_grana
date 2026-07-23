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
    password: '',
    remember: false,
});

function enviar() {
    formulario.post(rotas.value.login, {
        onFinish: () => formulario.reset('password'),
    });
}
</script>

<template>
    <Head title="Entrar" />

    <ConvidadoLayout>
        <div class="bg-white/90 backdrop-blur-xl shadow-2xl rounded-2xl w-full max-w-md p-8 border border-white/20">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#1fa67e] tracking-wide">NutriGrana</h1>
                <p class="text-gray-500 text-sm mt-2">Acesse sua conta e continue evoluindo</p>
            </div>

            <div
                v-if="status"
                class="mb-4 text-sm font-medium text-green-600 text-center"
            >
                {{ status }}
            </div>

            <form class="space-y-5" @submit.prevent="enviar">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-600">Email</label>
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

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-600">Senha</label>
                    <input
                        id="password"
                        v-model="formulario.password"
                        type="password"
                        autocomplete="current-password"
                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm"
                    >
                    <p v-if="formulario.errors.password" class="mt-1 text-sm text-red-600">
                        {{ formulario.errors.password }}
                    </p>
                </div>

                <div class="flex items-center justify-between">
                    <label for="remember_me" class="flex items-center text-sm text-gray-600">
                        <input
                            id="remember_me"
                            v-model="formulario.remember"
                            type="checkbox"
                            class="rounded border-gray-300 text-[#1fa67e] focus:ring-[#1fa67e] shadow-sm"
                        >
                        <span class="ml-2">Me manter conectado</span>
                    </label>

                    <Link
                        :href="rotas.passwordRequest"
                        class="text-sm text-[#1fa67e] hover:underline"
                    >
                        Esqueceu?
                    </Link>
                </div>

                <button
                    type="submit"
                    class="w-full bg-[#1fa67e] hover:bg-[#188f6b] text-white font-semibold py-2.5 rounded-lg shadow-lg transition duration-300"
                    :disabled="formulario.processing"
                >
                    Entrar
                </button>

                <div class="text-center text-sm text-gray-500 mt-4">
                    Ainda não tem conta?
                    <Link :href="rotas.register" class="text-[#1fa67e] font-semibold hover:underline">
                        Criar conta
                    </Link>
                </div>
            </form>
        </div>
    </ConvidadoLayout>
</template>
