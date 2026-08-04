<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import Modal from '@/Components/Modal.vue';

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

    formularioPerfil.post(rotas.value.profileUpdate, {
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
        .post(rotas.value.profilePasswordSendCode, {
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
    formularioSenha.put(rotas.value.profilePasswordConfirm, {
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
    formularioExclusao.delete(rotas.value.profileDestroy, {
        onFinish: () => formularioExclusao.reset(),
    });
}
</script>

<template>
    <Head title="Meu Perfil" />

    <AutenticadoLayout titulo="Meu Perfil">
        <div class="p-6 lg:p-8 space-y-6">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Dados Pessoais -->
                <section class="xl:col-span-2 bg-white shadow-sm rounded-2xl border border-gray-100 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Dados Pessoais</h3>

                        <div class="flex items-center gap-2">
                            <template v-if="editando">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50"
                                    :disabled="formularioPerfil.processing"
                                    @click="cancelarEdicao"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-[#1fa67e] px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#198a68] disabled:opacity-60"
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
                                class="inline-flex items-center gap-1.5 rounded-lg bg-[#1fa67e] px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#198a68]"
                                @click="ativarEdicao"
                            >
                                <span class="material-icons text-sm">edit</span>
                                Editar Perfil
                            </button>
                        </div>
                    </div>

                    <form class="mt-6 space-y-6" @submit.prevent="salvarPerfil">
                        <div class="flex justify-center sm:justify-start">
                            <div class="relative">
                                <div
                                    class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-[#e8f7f1] text-3xl font-bold text-[#1fa67e]"
                                >
                                    <img
                                        v-if="previewFoto"
                                        :src="previewFoto"
                                        alt="Foto de perfil"
                                        class="h-full w-full object-cover"
                                    >
                                    <span v-else>{{ iniciais }}</span>
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
                        </div>
                        <p v-if="formularioPerfil.errors.foto" class="text-sm text-red-600">
                            {{ formularioPerfil.errors.foto }}
                        </p>

                        <div>
                            <label for="nome" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Nome completo
                            </label>
                            <input
                                id="nome"
                                v-model="formularioPerfil.nome"
                                type="text"
                                class="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 focus:border-[#1fa67e] focus:ring-[#1fa67e] disabled:opacity-70"
                                :disabled="!editando"
                                required
                                autocomplete="name"
                            >
                            <p v-if="formularioPerfil.errors.nome" class="mt-2 text-sm text-red-600">
                                {{ formularioPerfil.errors.nome }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    E-mail
                                </label>
                                <input
                                    id="email"
                                    v-model="formularioPerfil.email"
                                    type="email"
                                    class="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 focus:border-[#1fa67e] focus:ring-[#1fa67e] disabled:opacity-70"
                                    :disabled="!editando"
                                    required
                                    autocomplete="username"
                                >
                                <p v-if="formularioPerfil.errors.email" class="mt-2 text-sm text-red-600">
                                    {{ formularioPerfil.errors.email }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="data_nascimento"
                                    class="block text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Data de nascimento
                                </label>
                                <input
                                    id="data_nascimento"
                                    v-model="formularioPerfil.data_nascimento"
                                    type="date"
                                    class="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 focus:border-[#1fa67e] focus:ring-[#1fa67e] disabled:opacity-70"
                                    :disabled="!editando"
                                    required
                                >
                                <p v-if="formularioPerfil.errors.data_nascimento" class="mt-2 text-sm text-red-600">
                                    {{ formularioPerfil.errors.data_nascimento }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label
                                for="motivo_controle_financeiro"
                                class="block text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Motivo do controle financeiro
                            </label>
                            <select
                                id="motivo_controle_financeiro"
                                v-model="formularioPerfil.motivo_controle_financeiro"
                                class="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 focus:border-[#1fa67e] focus:ring-[#1fa67e] disabled:opacity-70"
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
                            <p
                                v-if="formularioPerfil.errors.motivo_controle_financeiro"
                                class="mt-2 text-sm text-red-600"
                            >
                                {{ formularioPerfil.errors.motivo_controle_financeiro }}
                            </p>
                        </div>
                    </form>
                </section>

                <div class="space-y-6">
                    <!-- Segurança -->
                    <section class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-4">
                            <span class="material-icons text-[#1fa67e]">shield</span>
                            <h3 class="text-lg font-semibold text-gray-900">Segurança</h3>
                        </div>

                        <div class="mt-4 space-y-3">
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-gray-50"
                                @click="abrirModalSenha"
                            >
                                <span class="material-icons text-gray-500">lock</span>
                                <span class="flex-1 text-sm font-medium text-gray-800">Alterar senha</span>
                                <span class="material-icons text-gray-400">chevron_right</span>
                            </button>

                            <div class="flex items-center gap-3 rounded-xl px-3 py-3 opacity-70">
                                <span class="material-icons text-gray-500">verified_user</span>
                                <span class="flex-1 text-sm font-medium text-gray-800">
                                    Autenticação em 2 fatores
                                </span>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="autenticacaoDoisFatores"
                                    class="relative h-6 w-11 rounded-full transition"
                                    :class="autenticacaoDoisFatores ? 'bg-[#1fa67e]' : 'bg-gray-300'"
                                    disabled
                                    title="Em breve"
                                >
                                    <span
                                        class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition"
                                        :class="{ 'translate-x-5': autenticacaoDoisFatores }"
                                    />
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Configurações -->
                    <section class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6 opacity-80">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-4">
                            <span class="material-icons text-[#1fa67e]">settings</span>
                            <h3 class="text-lg font-semibold text-gray-900">Configurações</h3>
                        </div>

                        <div class="mt-4 space-y-5">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Idioma
                                </label>
                                <select
                                    class="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 text-gray-700"
                                    disabled
                                >
                                    <option>Português (Brasil)</option>
                                    <option>English</option>
                                </select>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Notificações
                                </p>
                                <div class="mt-3 space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="checkbox"
                                            class="rounded border-gray-300 text-[#1fa67e] focus:ring-[#1fa67e]"
                                            checked
                                            disabled
                                        >
                                        Receber alertas por e-mail
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="checkbox"
                                            class="rounded border-gray-300 text-[#1fa67e] focus:ring-[#1fa67e]"
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
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-white hover:bg-red-500"
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
                <h2 class="text-lg font-semibold text-gray-900">Alterar senha</h2>

                <template v-if="etapaSenha === 'solicitar'">
                    <p class="mt-2 text-sm text-gray-600">
                        Enviaremos um código de confirmação para
                        <strong>{{ usuario.email }}</strong>.
                        Use esse código para definir uma nova senha.
                    </p>

                    <div class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
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
                    <p class="text-sm text-gray-600">
                        Digite o código recebido por e-mail e a nova senha.
                    </p>

                    <div>
                        <label for="codigo" class="block text-sm font-medium text-gray-700">Código</label>
                        <input
                            id="codigo"
                            v-model="formularioSenha.codigo"
                            type="text"
                            maxlength="6"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            class="mt-1 block w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            required
                        >
                        <p v-if="formularioSenha.errors.codigo" class="mt-2 text-sm text-red-600">
                            {{ formularioSenha.errors.codigo }}
                        </p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Nova senha</label>
                        <input
                            id="password"
                            v-model="formularioSenha.password"
                            type="password"
                            autocomplete="new-password"
                            class="mt-1 block w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            required
                        >
                        <p v-if="formularioSenha.errors.password" class="mt-2 text-sm text-red-600">
                            {{ formularioSenha.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            Confirmar nova senha
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="formularioSenha.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="mt-1 block w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            required
                        >
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
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
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <span class="material-symbols-outlined text-[22px]">warning</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Tem certeza que deseja excluir a conta?
                        </h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Você perderá todos os registros feitos e esta ação é irreversível.
                            Digite sua senha para confirmar.
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <label for="password_exclusao" class="block text-sm font-medium text-gray-700">Senha</label>
                    <input
                        id="password_exclusao"
                        v-model="formularioExclusao.password"
                        type="password"
                        class="mt-1 block w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500"
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
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        :disabled="formularioExclusao.processing"
                        @click="fecharModalExclusao"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60"
                        :disabled="formularioExclusao.processing"
                    >
                        {{ formularioExclusao.processing ? 'Excluindo...' : 'Excluir conta' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AutenticadoLayout>
</template>
