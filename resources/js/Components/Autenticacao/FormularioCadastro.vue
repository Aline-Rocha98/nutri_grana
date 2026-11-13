<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onDeactivated } from 'vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import CampoSenha from '@/Components/Formulario/CampoSenha.vue';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';

const props = defineProps({
    motivos: {
        type: Array,
        default: () => [],
    },
});

const pagina = usePage();
const rotas = computed(() => pagina.props.rotas);

const opcoesMotivoControleFinanceiro = computed(() =>
    props.motivos.map((motivo) => ({
        valor: motivo.value,
        rotulo: motivo.label,
    })),
);

const formularioCadastro = useForm({
    nome: '',
    email: '',
    data_nascimento: '',
    motivo_controle_financeiro: '',
    password: '',
    password_confirmation: '',
});

function enviarCadastro() {
    formularioCadastro.post(rotas.value.register, {
        onFinish: () => formularioCadastro.reset('password', 'password_confirmation'),
    });
}

function limparErrosCadastro() {
    formularioCadastro.clearErrors();
}

onDeactivated(limparErrosCadastro);
</script>

<template>
    <form class="space-y-5" novalidate @submit.prevent="enviarCadastro">
        <CampoTexto
            id="cadastro_nome"
            v-model="formularioCadastro.nome"
            rotulo="Nome"
            autocomplete="name"
            placeholder="Como podemos te chamar?"
            :erro="formularioCadastro.errors.nome"
            obrigatorio
            foco-automatico
        />

        <CampoTexto
            id="cadastro_email"
            v-model="formularioCadastro.email"
            rotulo="E-mail"
            tipo="email"
            inputmode="email"
            autocomplete="username"
            placeholder="voce@email.com"
            :erro="formularioCadastro.errors.email"
            obrigatorio
        />

        <div class="grid gap-5 sm:grid-cols-2">
            <CampoTexto
                id="cadastro_data_nascimento"
                v-model="formularioCadastro.data_nascimento"
                rotulo="Data de nascimento"
                tipo="date"
                autocomplete="bday"
                :erro="formularioCadastro.errors.data_nascimento"
                obrigatorio
            />

            <CampoSelecao
                id="cadastro_motivo"
                v-model="formularioCadastro.motivo_controle_financeiro"
                rotulo="Seu objetivo"
                placeholder="Selecione"
                :opcoes="opcoesMotivoControleFinanceiro"
                :erro="formularioCadastro.errors.motivo_controle_financeiro"
                obrigatorio
            />
        </div>

        <CampoSenha
            id="cadastro_senha"
            v-model="formularioCadastro.password"
            rotulo="Senha"
            autocomplete="new-password"
            placeholder="Crie uma senha"
            :erro="formularioCadastro.errors.password"
            obrigatorio
        />

        <CampoSenha
            id="cadastro_confirmar_senha"
            v-model="formularioCadastro.password_confirmation"
            rotulo="Confirmar senha"
            autocomplete="new-password"
            placeholder="Repita a senha"
            :erro="formularioCadastro.errors.password_confirmation"
            obrigatorio
        />

        <button
            type="submit"
            class="ng-btn-primary w-full py-2.5"
            :disabled="formularioCadastro.processing"
        >
            <span class="material-symbols-outlined text-[18px]">person_add</span>
            {{ formularioCadastro.processing ? 'Criando conta...' : 'Criar conta' }}
        </button>
    </form>
</template>
