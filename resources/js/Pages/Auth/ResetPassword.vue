<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConvidadoLayout from '@/Layouts/ConvidadoLayout.vue';

const props = defineProps({
    email: {
        type: String,
        default: '',
    },
    token: {
        type: String,
        required: true,
    },
});

const pagina = usePage();
const rotas = computed(() => pagina.props.rotas);

const formulario = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function enviar() {
    formulario.post(rotas.value.passwordStore, {
        onFinish: () => formulario.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Redefinir senha" />

    <ConvidadoLayout>
        <div class="bg-ng-card/95 backdrop-blur-xl shadow-2xl rounded-2xl w-full max-w-md p-8 border border-ng-line">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#1fa67e]">NutriGrana</h1>
                <p class="text-ng-ink-muted text-sm mt-2">Redefinir senha</p>
            </div>

            <form class="space-y-5" @submit.prevent="enviar">
                <div>
                    <label for="email" class="block text-sm font-medium text-ng-ink-muted">Email</label>
                    <input
                        id="email"
                        v-model="formulario.email"
                        type="email"
                        autofocus
                        autocomplete="username"
                        class="mt-1 w-full rounded-lg border-ng-line-strong focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm"
                    >
                    <p v-if="formulario.errors.email" class="mt-1 text-sm text-red-600">
                        {{ formulario.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-ng-ink-muted">Senha</label>
                    <input
                        id="password"
                        v-model="formulario.password"
                        type="password"
                        autocomplete="new-password"
                        class="mt-1 w-full rounded-lg border-ng-line-strong focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm"
                    >
                    <p v-if="formulario.errors.password" class="mt-1 text-sm text-red-600">
                        {{ formulario.errors.password }}
                    </p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-ng-ink-muted">
                        Confirmar senha
                    </label>
                    <input
                        id="password_confirmation"
                        v-model="formulario.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        class="mt-1 w-full rounded-lg border-ng-line-strong focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full bg-[#1fa67e] hover:bg-[#188f6b] text-white font-semibold py-2.5 rounded-lg shadow-lg transition duration-300"
                    :disabled="formulario.processing"
                >
                    Redefinir senha
                </button>
            </form>
        </div>
    </ConvidadoLayout>
</template>
