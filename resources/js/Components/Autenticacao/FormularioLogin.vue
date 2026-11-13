<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onDeactivated } from 'vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import CampoSenha from '@/Components/Formulario/CampoSenha.vue';
import CampoCaixaSelecao from '@/Components/Formulario/CampoCaixaSelecao.vue';

const pagina = usePage();
const rotas = computed(() => pagina.props.rotas);

const formularioLogin = useForm({
    email: '',
    password: '',
    remember: false,
});

function enviarLogin() {
    formularioLogin.post(rotas.value.login, {
        onFinish: () => formularioLogin.reset('password'),
    });
}

function limparErrosLogin() {
    formularioLogin.clearErrors();
}

onDeactivated(limparErrosLogin);
</script>

<template>
    <form class="space-y-5" novalidate @submit.prevent="enviarLogin">
        <CampoTexto
            id="login_email"
            v-model="formularioLogin.email"
            rotulo="E-mail"
            tipo="email"
            inputmode="email"
            autocomplete="username"
            placeholder="voce@email.com"
            :erro="formularioLogin.errors.email"
            obrigatorio
            foco-automatico
        />

        <CampoSenha
            id="login_senha"
            v-model="formularioLogin.password"
            rotulo="Senha"
            autocomplete="current-password"
            placeholder="Sua senha"
            :erro="formularioLogin.errors.password"
            obrigatorio
        >
            <template #rotulo-extra>
                <Link :href="rotas.passwordRequest" class="text-xs font-medium text-ng-brand hover:underline">
                    Esqueceu a senha?
                </Link>
            </template>
        </CampoSenha>

        <CampoCaixaSelecao
            id="login_lembrar"
            v-model="formularioLogin.remember"
            rotulo="Me manter conectado"
        />

        <button
            type="submit"
            class="ng-btn-primary w-full py-2.5"
            :disabled="formularioLogin.processing"
        >
            <span class="material-symbols-outlined text-[18px]">login</span>
            {{ formularioLogin.processing ? 'Entrando...' : 'Entrar' }}
        </button>
    </form>
</template>
