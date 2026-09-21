<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import Modal from '@/Components/Modal.vue';
import { useTema } from '@/Composables/useTema';

const { escuro, alternarTema } = useTema();

const props = defineProps({
    usuario: {
        type: Object,
        required: true,
    },
    motivos: {
        type: Array,
        default: () => [],
    },
});

const pagina = usePage();
const rotas = computed(() => pagina.props.rotas);

const editando = ref(false);
const modalSenhaAberto = ref(false);
const etapaSenha = ref('solicitar');
const modalExclusaoAberto = ref(false);
const autenticacaoDoisFatores = ref(true);
const previewFoto = ref(props.usuario.foto_url);
const inputFoto = ref(null);

const formularioPerfil = useForm({
    nome: props.usuario.nome ?? '',
    email: props.usuario.email ?? '',
    data_nascimento: props.usuario.data_nascimento ?? '',
    motivo_controle_financeiro: props.usuario.motivo_controle_financeiro ?? '',
    foto: null,
});

const formularioSenha = useForm({
    codigo: '',
    password: '',
    password_confirmation: '',
});

const formularioExclusao = useForm({
    password: '',
});

watch(
    () => props.usuario,
    (usuario) => {
        formularioPerfil.nome = usuario.nome ?? '';
        formularioPerfil.email = usuario.email ?? '';
        formularioPerfil.data_nascimento = usuario.data_nascimento ?? '';
        formularioPerfil.motivo_controle_financeiro = usuario.motivo_controle_financeiro ?? '';
        formularioPerfil.foto = null;
        previewFoto.value = usuario.foto_url;
    },
    { deep: true },
);

const iniciais = computed(() => props.usuario.iniciais || 'NG');

function ativarEdicao() {
    editando.value = true;
}

function cancelarEdicao() {
    editando.value = false;
    formularioPerfil.clearErrors();
    formularioPerfil.nome = props.usuario.nome ?? '';
    formularioPerfil.email = props.usuario.email ?? '';
    formularioPerfil.data_nascimento = props.usuario.data_nascimento ?? '';
    formularioPerfil.motivo_controle_financeiro = props.usuario.motivo_controle_financeiro ?? '';
    formularioPerfil.foto = null;
    previewFoto.value = props.usuario.foto_url;
    if (inputFoto.value) {
        inputFoto.value.value = '';
    }
}

function selecionarFoto(evento) {
    const arquivo = evento.target.files?.[0] ?? null;
    formularioPerfil.foto = arquivo;

    if (arquivo) {
        previewFoto.value = URL.createObjectURL(arquivo);
    }
}

function abrirSeletorFoto() {
    if (!editando.value) {
        return;
    }
    inputFoto.value?.click();
}

function salvarPerfil() {
    formularioPerfil.transform((dados) => ({
        ...dados,
        _method: 'patch',
    }));

    formularioPerfil.post(rotas.value.usuarioAtualizar, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            editando.value = false;
            formularioPerfil.foto = null;
            formularioPerfil.transform((dados) => dados);
            if (inputFoto.value) {
                inputFoto.value.value = '';
            }
        },
        onError: () => {
            formularioPerfil.transform((dados) => dados);
        },
    });
}

function abrirModalSenha() {
    etapaSenha.value = 'solicitar';
    formularioSenha.reset();
    formularioSenha.clearErrors();
    modalSenhaAberto.value = true;
}

function fecharModalSenha() {
    modalSenhaAberto.value = false;
    etapaSenha.value = 'solicitar';
    formularioSenha.reset();
    formularioSenha.clearErrors();
}

function enviarCodigoSenha() {
    formularioSenha
        .transform(() => ({}))
        .post(rotas.value.usuarioSenhaEnviarCodigo, {
            preserveScroll: true,
            onSuccess: () => {
                etapaSenha.value = 'confirmar';
                formularioSenha.transform((dados) => dados);
                formularioSenha.reset();
                formularioSenha.clearErrors();
            },
            onError: () => {
                formularioSenha.transform((dados) => dados);
            },
        });
}

function confirmarNovaSenha() {
    formularioSenha.put(rotas.value.usuarioSenhaConfirmar, {
        preserveScroll: true,
        onSuccess: () => {
            fecharModalSenha();
        },
    });
}

function abrirModalExclusao() {
    formularioExclusao.reset();
    formularioExclusao.clearErrors();
    modalExclusaoAberto.value = true;
}

function fecharModalExclusao() {
    modalExclusaoAberto.value = false;
    formularioExclusao.reset();
    formularioExclusao.clearErrors();
}

function excluirConta() {
    formularioExclusao.delete(rotas.value.usuarioExcluir, {
        onFinish: () => formularioExclusao.reset(),
    });
}

function aoErroFoto() {
    previewFoto.value = null;
}
</script>

<template>
    <Head title="Meu Perfil" />

    <AutenticadoLayout titulo="Meu Perfil">
        <div class="space-y-6 p-6 lg:p-8">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <!-- Dados Pessoais -->
                <section class="ng-card xl:col-span-2 p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ng-line pb-4">
                        <h3 class="text-lg font-semibold text-ng-ink">Dados Pessoais</h3>

                        <div class="flex items-center gap-2">
                            <template v-if="editando">
                                <button
                                    type="button"
                                    class="ng-btn-ghost"
                                    :disabled="formularioPerfil.processing"
                                    @click="cancelarEdicao"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    class="ng-btn-primary"
                                    :disabled="formularioPerfil.processing"
                                    @click="salvarPerfil"
                                >
                                    <span class="material-icons text-sm">save</span>
                                    Salvar
                                </button>
                            </template>
                            <button
                                v-else
                                type="button"
                                class="ng-btn-primary"
                                @click="ativarEdicao"
                            >
                                <span class="material-icons text-sm">edit</span>
                                Editar Perfil
                            </button>
                        </div>
                    </div>

                    <form class="mt-6" @submit.prevent="salvarPerfil">
                        <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                            <div class="relative mx-auto shrink-0 sm:mx-0">
                                <div
                                    class="group/foto relative flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-ng-brand-soft text-3xl font-bold text-[#1fa67e]"
                                    :class="{ 'cursor-not-allowed': !editando }"
                                >
                                    <img
                                        v-if="previewFoto"
                                        :src="previewFoto"
                                        alt="Foto de perfil"
                                        class="h-full w-full object-cover transition"
                                        :class="{ 'group-hover/foto:opacity-40': !editando }"
                                        @error="aoErroFoto"
                                    >
                                    <span
                                        v-else
                                        class="transition"
                                        :class="{ 'group-hover/foto:opacity-40': !editando }"
                                    >
                                        {{ iniciais }}
                                    </span>
                                    <span
                                        v-if="!editando"
                                        class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition group-hover/foto:opacity-100"
                                        title="Clique em Editar Perfil para alterar"
                                    >
                                        <span class="material-icons text-2xl text-ng-ink-secondary">lock</span>
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="absolute bottom-0 right-0 flex h-9 w-9 items-center justify-center rounded-full bg-[#151a18] text-white shadow-md transition"
                                    :class="editando ? 'hover:bg-[#1fa67e]' : 'opacity-50 cursor-not-allowed'"
                                    :disabled="!editando"
                                    aria-label="Alterar foto de perfil"
                                    @click="abrirSeletorFoto"
                                >
                                    <span class="material-icons text-base">photo_camera</span>
                                </button>
                                <input
                                    ref="inputFoto"
                                    type="file"
                                    class="hidden"
                                    accept="image/png,image/jpeg,image/jpg,image/webp"
                                    @change="selecionarFoto"
                                >
                            </div>

                            <div class="min-w-0 flex-1 space-y-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="group/campo relative">
                                        <label for="nome" class="block text-xs font-semibold uppercase tracking-wide text-ng-ink-muted">
                                            Nome completo
                                        </label>
                                        <div class="relative mt-1">
                                            <input
                                                id="nome"
                                                v-model="formularioPerfil.nome"
                                                type="text"
                                                class="block w-full rounded-lg border-ng-line-strong bg-ng-input focus:border-[#1fa67e] focus:ring-[#1fa67e] disabled:cursor-not-allowed disabled:opacity-70"
                                                :class="{ 'group-hover/campo:pr-10': !editando }"
                                                :disabled="!editando"
                                                required
                                                autocomplete="name"
                                            >
                                            <span
                                                v-if="!editando"
                                                class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-ng-ink-subtle opacity-0 transition group-hover/campo:opacity-100"
                                                title="Clique em Editar Perfil para alterar"
                                            >
                                                <span class="material-icons text-lg">lock</span>
                                            </span>
                                        </div>
                                        <p v-if="formularioPerfil.errors.nome" class="mt-2 text-sm text-red-600">
                                            {{ formularioPerfil.errors.nome }}
                                        </p>
                                    </div>

                                    <div class="group/campo relative">
                                        <label
                                            for="data_nascimento"
                                            class="block text-xs font-semibold uppercase tracking-wide text-ng-ink-muted"
                                        >
                                            Data de nascimento
                                        </label>
                                        <div class="relative mt-1">
                                            <input
                                                id="data_nascimento"
                                                v-model="formularioPerfil.data_nascimento"
                                                type="date"
                                                class="block w-full rounded-lg border-ng-line-strong bg-ng-input focus:border-[#1fa67e] focus:ring-[#1fa67e] disabled:cursor-not-allowed disabled:opacity-70"
                                                :class="{ 'group-hover/campo:pr-10': !editando }"
                                                :disabled="!editando"
                                                required
                                            >
                                            <span
                                                v-if="!editando"
                                                class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-ng-ink-subtle opacity-0 transition group-hover/campo:opacity-100"
                                                title="Clique em Editar Perfil para alterar"
                                            >
                                                <span class="material-icons text-lg">lock</span>
                                            </span>
                                        </div>
                                        <p v-if="formularioPerfil.errors.data_nascimento" class="mt-2 text-sm text-red-600">
                                            {{ formularioPerfil.errors.data_nascimento }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="group/campo relative">
                                        <label for="email" class="block text-xs font-semibold uppercase tracking-wide text-ng-ink-muted">
                                            E-mail
                                        </label>
                                        <div class="relative mt-1">
                                            <input
                                                id="email"
                                                v-model="formularioPerfil.email"
                                                type="email"
                                                class="block w-full rounded-lg border-ng-line-strong bg-ng-input focus:border-[#1fa67e] focus:ring-[#1fa67e] disabled:cursor-not-allowed disabled:opacity-70"
                                                :class="{ 'group-hover/campo:pr-10': !editando }"
                                                :disabled="!editando"
                                                required
                                                autocomplete="username"
                                            >
                                            <span
                                                v-if="!editando"
                                                class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-ng-ink-subtle opacity-0 transition group-hover/campo:opacity-100"
                                                title="Clique em Editar Perfil para alterar"
                                            >
                                                <span class="material-icons text-lg">lock</span>
                                            </span>
                                        </div>
                                        <p v-if="formularioPerfil.errors.email" class="mt-2 text-sm text-red-600">
                                            {{ formularioPerfil.errors.email }}
                                        </p>
                                    </div>

                                    <div class="group/campo relative">
                                        <label
                                            for="motivo_controle_financeiro"
                                            class="block text-xs font-semibold uppercase tracking-wide text-ng-ink-muted"
                                        >
                                            Motivo do controle financeiro
                                        </label>
                                        <div class="relative mt-1">
                                            <select
                                                id="motivo_controle_financeiro"
                                                v-model="formularioPerfil.motivo_controle_financeiro"
                                                class="block w-full rounded-lg border-ng-line-strong bg-ng-input focus:border-[#1fa67e] focus:ring-[#1fa67e] disabled:cursor-not-allowed disabled:opacity-70"
                                                :class="{ 'group-hover/campo:pr-10': !editando }"
                                                :disabled="!editando"
                                                required
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
                                            <span
                                                v-if="!editando"
                                                class="pointer-events-none absolute inset-y-0 right-8 flex items-center text-ng-ink-subtle opacity-0 transition group-hover/campo:opacity-100"
                                                title="Clique em Editar Perfil para alterar"
                                            >
                                                <span class="material-icons text-lg">lock</span>
                                            </span>
                                        </div>
                                        <p
                                            v-if="formularioPerfil.errors.motivo_controle_financeiro"
                                            class="mt-2 text-sm text-red-600"
                                        >
                                            {{ formularioPerfil.errors.motivo_controle_financeiro }}
                                        </p>
                                    </div>
                                </div>

                                <p v-if="formularioPerfil.errors.foto" class="text-sm text-red-600">
                                    {{ formularioPerfil.errors.foto }}
                                </p>
                            </div>
                        </div>
                    </form>
                </section>

                <div class="space-y-6">
                    <!-- Segurança -->
                    <section class="bg-ng-card shadow-sm rounded-2xl border border-ng-line p-6">
                        <div class="flex items-center gap-2 border-b border-ng-line pb-4">
                            <span class="material-icons text-[#1fa67e]">shield</span>
                            <h3 class="text-lg font-semibold text-ng-ink">Segurança</h3>
                        </div>

                        <div class="mt-4 space-y-3">
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-ng-brand-soft"
                                @click="abrirModalSenha"
                            >
                                <span class="material-icons text-ng-ink-muted">lock</span>
                                <span class="flex-1 text-sm font-medium text-ng-ink">Alterar senha</span>
                                <span class="material-icons text-ng-ink-subtle">chevron_right</span>
                            </button>

                            <div class="flex items-center gap-3 rounded-xl px-3 py-3 opacity-70">
                                <span class="material-icons text-ng-ink-muted">verified_user</span>
                                <span class="flex-1 text-sm font-medium text-ng-ink">
                                    Autenticação em 2 fatores
                                </span>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="autenticacaoDoisFatores"
                                    class="ng-toggle"
                                    :class="autenticacaoDoisFatores ? 'ng-toggle--on' : 'ng-toggle--off'"
                                    disabled
                                    title="Em breve"
                                >
                                    <span
                                        class="ng-toggle__knob"
                                        :class="{ 'translate-x-5': autenticacaoDoisFatores }"
                                    />
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Configurações -->
                    <section class="rounded-2xl border border-ng-line bg-ng-card p-6 shadow-sm">
                        <div class="flex items-center gap-2 border-b border-ng-line pb-4">
                            <span class="material-icons text-[#1fa67e]">settings</span>
                            <h3 class="text-lg font-semibold text-ng-ink">Configurações</h3>
                        </div>

                        <div class="mt-4 space-y-5">
                            <div class="flex items-center gap-3 rounded-xl px-3 py-3">
                                <span class="material-icons text-ng-ink-muted">
                                    {{ escuro ? 'dark_mode' : 'light_mode' }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-ng-ink">Modo noturno</p>
                                    <p class="text-xs text-ng-ink-muted">
                                        {{ escuro ? 'Tema escuro ativo' : 'Tema claro ativo' }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="escuro"
                                    :aria-label="escuro ? 'Desativar modo noturno' : 'Ativar modo noturno'"
                                    class="ng-toggle"
                                    :class="escuro ? 'ng-toggle--on' : 'ng-toggle--off'"
                                    @click="alternarTema"
                                >
                                    <span
                                        class="ng-toggle__knob"
                                        :class="{ 'translate-x-5': escuro }"
                                    />
                                </button>
                            </div>

                            <div class="opacity-60">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-ng-ink-muted">
                                    Idioma
                                </label>
                                <select
                                    class="mt-1 block w-full rounded-lg border-ng-line-strong bg-ng-input text-ng-ink-secondary"
                                    disabled
                                >
                                    <option>Português (Brasil)</option>
                                    <option>English</option>
                                </select>
                            </div>

                            <div class="opacity-60">
                                <p class="text-xs font-semibold uppercase tracking-wide text-ng-ink-muted">
                                    Notificações
                                </p>
                                <div class="mt-3 space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-ng-ink-secondary">
                                        <input
                                            type="checkbox"
                                            class="rounded border-ng-input-border text-[#1fa67e] focus:ring-[#1fa67e]"
                                            checked
                                            disabled
                                        >
                                        Receber alertas por e-mail
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-ng-ink-secondary">
                                        <input
                                            type="checkbox"
                                            class="rounded border-ng-input-border text-[#1fa67e] focus:ring-[#1fa67e]"
                                            disabled
                                        >
                                        Notificações push
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Excluir conta -->
            <div class="flex justify-end pt-2">
                <button
                    type="button"
                    class="ng-btn-danger ng-btn-danger--lg"
                    @click="abrirModalExclusao"
                >
                    <span class="material-icons text-sm">delete_forever</span>
                    Excluir conta
                </button>
            </div>
        </div>

        <!-- Modal alteração de senha -->
        <Modal :aberto="modalSenhaAberto" max-largura="md">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-ng-ink">Alterar senha</h2>

                <template v-if="etapaSenha === 'solicitar'">
                    <p class="mt-2 text-sm text-ng-ink-muted">
                        Enviaremos um código de confirmação para
                        <strong>{{ usuario.email }}</strong>.
                        Use esse código para definir uma nova senha.
                    </p>

                    <div class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-ng-line-strong px-4 py-2 text-sm font-semibold text-ng-ink-secondary hover:bg-ng-brand-soft"
                            :disabled="formularioSenha.processing"
                            @click="fecharModalSenha"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#198a68] disabled:opacity-60"
                            :disabled="formularioSenha.processing"
                            @click="enviarCodigoSenha"
                        >
                            {{ formularioSenha.processing ? 'Enviando...' : 'Enviar código' }}
                        </button>
                    </div>
                </template>

                <form v-else class="mt-4 space-y-4" @submit.prevent="confirmarNovaSenha">
                    <p class="text-sm text-ng-ink-muted">
                        Digite o código recebido por e-mail e a nova senha.
                    </p>

                    <div>
                        <label for="codigo" class="block text-sm font-medium text-ng-ink-secondary">Código</label>
                        <input
                            id="codigo"
                            v-model="formularioSenha.codigo"
                            type="text"
                            maxlength="6"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            class="mt-1 block w-full rounded-lg border-ng-line-strong focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            required
                        >
                        <p v-if="formularioSenha.errors.codigo" class="mt-2 text-sm text-red-600">
                            {{ formularioSenha.errors.codigo }}
                        </p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-ng-ink-secondary">Nova senha</label>
                        <input
                            id="password"
                            v-model="formularioSenha.password"
                            type="password"
                            autocomplete="new-password"
                            class="mt-1 block w-full rounded-lg border-ng-line-strong focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            required
                        >
                        <p v-if="formularioSenha.errors.password" class="mt-2 text-sm text-red-600">
                            {{ formularioSenha.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-ng-ink-secondary">
                            Confirmar nova senha
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="formularioSenha.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="mt-1 block w-full rounded-lg border-ng-line-strong focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            required
                        >
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            class="rounded-lg border border-ng-line-strong px-4 py-2 text-sm font-semibold text-ng-ink-secondary hover:bg-ng-brand-soft"
                            :disabled="formularioSenha.processing"
                            @click="fecharModalSenha"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#198a68] disabled:opacity-60"
                            :disabled="formularioSenha.processing"
                        >
                            {{ formularioSenha.processing ? 'Salvando...' : 'Confirmar senha' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal exclusão -->
        <Modal :aberto="modalExclusaoAberto" max-largura="lg">
            <form class="p-6" @submit.prevent="excluirConta">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full ng-tint ng-tint--red">
                        <span class="material-symbols-outlined text-[22px]">warning</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-ng-ink">
                            Tem certeza que deseja excluir a conta?
                        </h2>
                        <p class="mt-2 text-sm text-ng-ink-muted">
                            Você perderá todos os registros feitos e esta ação é irreversível.
                            Digite sua senha para confirmar.
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <label for="password_exclusao" class="block text-sm font-medium text-ng-ink-secondary">Senha</label>
                    <input
                        id="password_exclusao"
                        v-model="formularioExclusao.password"
                        type="password"
                        class="mt-1 block w-full rounded-lg border-ng-line-strong focus:border-red-500 focus:ring-red-500"
                        placeholder="Digite sua senha"
                        required
                    >
                    <p v-if="formularioExclusao.errors.password" class="mt-2 text-sm text-red-600">
                        {{ formularioExclusao.errors.password }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg border border-ng-line-strong px-4 py-2 text-sm font-semibold text-ng-ink-secondary hover:bg-ng-brand-soft"
                        :disabled="formularioExclusao.processing"
                        @click="fecharModalExclusao"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="ng-btn-danger"
                        :disabled="formularioExclusao.processing"
                    >
                        {{ formularioExclusao.processing ? 'Excluindo...' : 'Excluir conta' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AutenticadoLayout>
</template>
