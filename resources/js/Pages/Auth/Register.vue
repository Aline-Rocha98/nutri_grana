<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConvidadoLayout from '@/Layouts/ConvidadoLayout.vue';

const props = defineProps({
    motivos: {
        type: Array,
        default: () => [],
    },
});

const pagina = usePage();
const rotas = computed(() => pagina.props.rotas);

const formulario = useForm({
    nome: '',
    email: '',
    data_nascimento: '',
    motivo_controle_financeiro: '',
    password: '',
    password_confirmation: '',
});

function enviar() {
    formulario.post(rotas.value.register, {
        onFinish: () => formulario.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Criar conta" />

    <ConvidadoLayout>
        <div class="bg-white/95 backdrop-blur-xl shadow-2xl rounded-2xl w-full max-w-lg p-8 border border-white/20">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#1fa67e]">NutriGrana</h1>
                <p class="text-gray-500 text-sm mt-2">Crie sua conta e organize suas finanças</p>
            </div>

            <form class="space-y-5" @submit.prevent="enviar">
                <div>
                    <label for="nome" class="block text-sm font-medium text-gray-600">Nome</label>
                    <input
                        id="nome"
                        v-model="formulario.nome"
                        type="text"
                        autofocus
                        autocomplete="name"
                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm"
                    >
                    <p v-if="formulario.errors.nome" class="mt-1 text-sm text-red-600">{{ formulario.errors.nome }}</p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-600">Email</label>
                    <input
                        id="email"
                        v-model="formulario.email"
                        type="email"
                        autocomplete="username"
                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm"
                    >
                    <p v-if="formulario.errors.email" class="mt-1 text-sm text-red-600">{{ formulario.errors.email }}</p>
                </div>

                <div>
                    <label for="data_nascimento" class="block text-sm font-medium text-gray-600">
                        Data de nascimento
                    </label>
                    <input
                        id="data_nascimento"
                        v-model="formulario.data_nascimento"
                        type="date"
                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm"
                    >
                    <p v-if="formulario.errors.data_nascimento" class="mt-1 text-sm text-red-600">
                        {{ formulario.errors.data_nascimento }}
                    </p>
                </div>

                <div>
                    <label for="motivo_controle_financeiro" class="block text-sm font-medium text-gray-600">
                        Por que deseja controlar suas finanças?
                    </label>
                    <select
                        id="motivo_controle_financeiro"
                        v-model="formulario.motivo_controle_financeiro"
                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm"
                    >
                        <option value="">Selecione uma opção</option>
                        <option
                            v-for="motivo in motivos"
                            :key="motivo.value"
                            :value="motivo.value"
                        >
                            {{ motivo.label }}
                        </option>
                    </select>
                    <p v-if="formulario.errors.motivo_controle_financeiro" class="mt-1 text-sm text-red-600">
                        {{ formulario.errors.motivo_controle_financeiro }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-600">Senha</label>
                    <input
                        id="password"
                        v-model="formulario.password"
                        type="password"
                        autocomplete="new-password"
                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm"
                    >
                    <p v-if="formulario.errors.password" class="mt-1 text-sm text-red-600">
                        {{ formulario.errors.password }}
                    </p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-600">
                        Confirmar senha
                    </label>
                    <input
                        id="password_confirmation"
                        v-model="formulario.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full bg-[#1fa67e] hover:bg-[#188f6b] text-white font-semibold py-2.5 rounded-lg shadow-lg transition duration-300"
                    :disabled="formulario.processing"
                >
                    Criar conta
                </button>

                <div class="text-center text-sm text-gray-500 mt-4">
                    Já tem conta?
                    <Link :href="rotas.login" class="text-[#1fa67e] font-semibold hover:underline">
                        Entrar
                    </Link>
                </div>
            </form>
        </div>
    </ConvidadoLayout>
</template>
