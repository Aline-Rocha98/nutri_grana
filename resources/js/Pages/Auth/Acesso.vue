<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConvidadoLayout from '@/Layouts/ConvidadoLayout.vue';
import CardAutenticacao from '@/Components/Autenticacao/CardAutenticacao.vue';
import FormularioLogin from '@/Components/Autenticacao/FormularioLogin.vue';
import FormularioCadastro from '@/Components/Autenticacao/FormularioCadastro.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';
import AlertaMensagem from '@/Components/Ui/AlertaMensagem.vue';

const MODO_LOGIN = 'login';
const MODO_CADASTRO = 'cadastro';

const props = defineProps({
    modoInicial: {
        type: String,
        default: MODO_LOGIN,
    },
    status: {
        type: String,
        default: null,
    },
    motivos: {
        type: Array,
        default: () => [],
    },
});

const pagina = usePage();
const rotas = computed(() => pagina.props.rotas);

const opcoesModoAcesso = [
    { valor: MODO_LOGIN, rotulo: 'Entrar', icone: 'login' },
    { valor: MODO_CADASTRO, rotulo: 'Criar conta', icone: 'person_add' },
];

const textosPorModo = {
    [MODO_LOGIN]: {
        tituloAba: 'Entrar',
        subtitulo: 'Acesse sua conta e continue evoluindo',
        perguntaRodape: 'Ainda não tem conta?',
        acaoRodape: 'Criar conta',
    },
    [MODO_CADASTRO]: {
        tituloAba: 'Criar conta',
        subtitulo: 'Crie sua conta e organize suas finanças',
        perguntaRodape: 'Já tem conta?',
        acaoRodape: 'Entrar',
    },
};

const modoAtual = computed({
    get: () => (props.modoInicial === MODO_CADASTRO ? MODO_CADASTRO : MODO_LOGIN),
    set: (novoModo) => alternarModoAcesso(novoModo),
});

const textosModoAtual = computed(() => textosPorModo[modoAtual.value]);

const estaNoModoLogin = computed(() => modoAtual.value === MODO_LOGIN);

function obterCaminhoDaRota(urlCompleta) {
    const url = new URL(urlCompleta, window.location.origin);

    return `${url.pathname}${url.search}`;
}

function alternarModoAcesso(novoModo) {
    if (!novoModo || novoModo === modoAtual.value) {
        return;
    }

    const rotaDestino = novoModo === MODO_CADASTRO ? rotas.value.register : rotas.value.login;

    router.replace({
        url: obterCaminhoDaRota(rotaDestino),
        props: (propsAtuais) => ({ ...propsAtuais, modoInicial: novoModo }),
        preserveState: true,
        preserveScroll: true,
    });
}

function alternarParaOutroModo() {
    alternarModoAcesso(estaNoModoLogin.value ? MODO_CADASTRO : MODO_LOGIN);
}
</script>

<template>
    <Head :title="textosModoAtual.tituloAba" />

    <ConvidadoLayout>
        <div class="w-full max-w-lg py-10">
            <CardAutenticacao :subtitulo="textosModoAtual.subtitulo" largura="lg">
                <template #topo>
                    <GrupoSegmentado
                        v-model="modoAtual"
                        :opcoes="opcoesModoAcesso"
                        rotulo="Escolha entre entrar ou criar conta"
                        expandido
                    />
                </template>

                <AlertaMensagem
                    v-if="status"
                    class="mb-5"
                    variante="success"
                    :mensagem="status"
                />

                <Transition
                    mode="out-in"
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-1 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="-translate-y-1 opacity-0"
                >
                    <KeepAlive>
                        <FormularioLogin v-if="estaNoModoLogin" key="login" />
                        <FormularioCadastro v-else key="cadastro" :motivos="motivos" />
                    </KeepAlive>
                </Transition>

                <template #rodape>
                    {{ textosModoAtual.perguntaRodape }}
                    <button
                        type="button"
                        class="font-semibold text-ng-brand hover:underline"
                        @click="alternarParaOutroModo"
                    >
                        {{ textosModoAtual.acaoRodape }}
                    </button>
                </template>
            </CardAutenticacao>
        </div>
    </ConvidadoLayout>
</template>
