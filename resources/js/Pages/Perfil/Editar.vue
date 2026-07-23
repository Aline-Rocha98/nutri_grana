<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    usuario: {
        type: Object,
        required: true,
    },
});

const pagina = usePage();
const rotas = computed(() => pagina.props.rotas);
const status = computed(() => pagina.props.flash?.status);
const modalExclusaoAberto = ref(false);

const formularioPerfil = useForm({
    nome: props.usuario.nome,
    email: props.usuario.email,
});

const formularioSenha = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const formularioExclusao = useForm({
    password: '',
});

function salvarPerfil() {
    formularioPerfil.patch(rotas.value.profileUpdate, {
        preserveScroll: true,
    });
}

function salvarSenha() {
    formularioSenha.put(rotas.value.passwordUpdate, {
        preserveScroll: true,
        onSuccess: () => formularioSenha.reset(),
    });
}

function excluirConta() {
    formularioExclusao.delete(rotas.value.profileDestroy, {
        onFinish: () => formularioExclusao.reset(),
    });
}
</script>

<template>
    <Head title="Perfil" />

    <AutenticadoLayout titulo="Perfil">
        <div class="p-6 lg:p-8 space-y-6">
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6 max-w-xl">
                <h3 class="text-lg font-medium text-gray-900">Informações do perfil</h3>
                <p class="mt-1 text-sm text-gray-600">Atualize seu nome e e-mail.</p>

                <form class="mt-6 space-y-6" @submit.prevent="salvarPerfil">
                    <div>
                        <label for="nome" class="block text-sm font-medium text-gray-700">Nome</label>
                        <input
                            id="nome"
                            v-model="formularioPerfil.nome"
                            type="text"
                            class="mt-1 block w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            required
                            autocomplete="name"
                        >
                        <p v-if="formularioPerfil.errors.nome" class="mt-2 text-sm text-red-600">
                            {{ formularioPerfil.errors.nome }}
                        </p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input
                            id="email"
                            v-model="formularioPerfil.email"
                            type="email"
                            class="mt-1 block w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            required
                            autocomplete="username"
                        >
                        <p v-if="formularioPerfil.errors.email" class="mt-2 text-sm text-red-600">
                            {{ formularioPerfil.errors.email }}
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-md bg-[#1fa67e] text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#198a68]"
                            :disabled="formularioPerfil.processing"
                        >
                            Salvar
                        </button>
                        <p
                            v-if="status === 'profile-updated'"
                            class="text-sm text-gray-600"
                        >
                            Salvo.
                        </p>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6 max-w-xl">
                <h3 class="text-lg font-medium text-gray-900">Atualizar senha</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Use uma senha longa e aleatória para manter sua conta segura.
                </p>

                <form class="mt-6 space-y-6" @submit.prevent="salvarSenha">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">
                            Senha atual
                        </label>
                        <input
                            id="current_password"
                            v-model="formularioSenha.current_password"
                            type="password"
                            class="mt-1 block w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            autocomplete="current-password"
                        >
                        <p v-if="formularioSenha.errors.current_password" class="mt-2 text-sm text-red-600">
                            {{ formularioSenha.errors.current_password }}
                        </p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Nova senha
                        </label>
                        <input
                            id="password"
                            v-model="formularioSenha.password"
                            type="password"
                            class="mt-1 block w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            autocomplete="new-password"
                        >
                        <p v-if="formularioSenha.errors.password" class="mt-2 text-sm text-red-600">
                            {{ formularioSenha.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            Confirmar senha
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="formularioSenha.password_confirmation"
                            type="password"
                            class="mt-1 block w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="flex items-center gap-4">
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-md bg-[#1fa67e] text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#198a68]"
                            :disabled="formularioSenha.processing"
                        >
                            Salvar
                        </button>
                        <p
                            v-if="status === 'password-updated'"
                            class="text-sm text-gray-600"
                        >
                            Salvo.
                        </p>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6 max-w-xl space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Excluir conta</h3>
                <p class="text-sm text-gray-600">
                    Ao excluir sua conta, todos os recursos e dados serão removidos permanentemente.
                </p>
                <button
                    type="button"
                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500"
                    @click="modalExclusaoAberto = true"
                >
                    Excluir conta
                </button>
            </div>
        </div>

        <Modal :aberto="modalExclusaoAberto" max-largura="lg" @fechar="modalExclusaoAberto = false">
            <form class="p-6" @submit.prevent="excluirConta">
                <h2 class="text-lg font-medium text-gray-900">
                    Tem certeza que deseja excluir sua conta?
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Digite sua senha para confirmar a exclusão permanente da conta.
                </p>

                <div class="mt-6">
                    <label for="password_exclusao" class="sr-only">Senha</label>
                    <input
                        id="password_exclusao"
                        v-model="formularioExclusao.password"
                        type="password"
                        class="mt-1 block w-3/4 rounded-lg border-gray-200"
                        placeholder="Senha"
                    >
                    <p v-if="formularioExclusao.errors.password" class="mt-2 text-sm text-red-600">
                        {{ formularioExclusao.errors.password }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest"
                        @click="modalExclusaoAberto = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest"
                        :disabled="formularioExclusao.processing"
                    >
                        Excluir conta
                    </button>
                </div>
            </form>
        </Modal>
    </AutenticadoLayout>
</template>
