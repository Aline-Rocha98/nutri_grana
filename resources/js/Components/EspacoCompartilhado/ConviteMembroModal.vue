<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';
import CodigoConviteExibicao from '@/Components/EspacoCompartilhado/CodigoConviteExibicao.vue';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    urlEnviarConvite: {
        type: String,
        required: true,
    },
    convitesPendentes: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['fechar']);

const pagina = usePage();
const formularioConvite = useForm({ email: '' });
const codigoConviteExibido = ref(null);
const chaveConviteRevogando = ref(null);
const emailReenviando = ref(null);

const processando = computed(() => formularioConvite.processing || emailReenviando.value !== null);
const textoConfirmar = computed(() => (codigoConviteExibido.value ? 'Concluir' : 'Enviar convite'));

watch(
    () => pagina.props.flash?.codigoConvite,
    (codigoConvite) => {
        if (codigoConvite && props.aberto) {
            codigoConviteExibido.value = codigoConvite;
        }
    },
);

watch(
    () => props.aberto,
    (aberto) => {
        if (!aberto) {
            return;
        }

        codigoConviteExibido.value = null;
        formularioConvite.reset();
        formularioConvite.clearErrors();
    },
);

function fechar() {
    emit('fechar');
}

function enviarConviteParaUsuario() {
    formularioConvite.post(props.urlEnviarConvite, {
        preserveScroll: true,
        onSuccess: () => formularioConvite.reset(),
    });
}

function confirmarModalDeConvite() {
    if (codigoConviteExibido.value) {
        fechar();
        return;
    }

    enviarConviteParaUsuario();
}

function reenviarConviteParaEmail(email) {
    if (!email || emailReenviando.value) {
        return;
    }

    emailReenviando.value = email;

    router.post(props.urlEnviarConvite, { email }, {
        preserveScroll: true,
        onFinish: () => {
            emailReenviando.value = null;
        },
    });
}

function revogarConvite(convite) {
    if (chaveConviteRevogando.value) {
        return;
    }

    chaveConviteRevogando.value = convite.chave;

    router.patch(convite.url_revogar, {}, {
        preserveScroll: true,
        onFinish: () => {
            chaveConviteRevogando.value = null;
        },
    });
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        icone="person_add"
        titulo="Convidar para o espaço"
        subtitulo="Quem entrar verá apenas os lançamentos deste espaço."
        :texto-confirmar="textoConfirmar"
        :processando="processando"
        @confirmar="confirmarModalDeConvite"
        @fechar="fechar"
    >
        <div class="space-y-5">
            <CodigoConviteExibicao
                v-if="codigoConviteExibido"
                :codigo="codigoConviteExibido.codigo"
                :nome-convidado="codigoConviteExibido.nome_convidado"
                :expira-em="codigoConviteExibido.expira_em"
                :processando-reenvio="emailReenviando !== null"
                @reenviar="reenviarConviteParaEmail(codigoConviteExibido.email_convidado)"
            />

            <CampoTexto
                v-else
                id="convite-email"
                v-model="formularioConvite.email"
                tipo="email"
                rotulo="E-mail da pessoa"
                placeholder="nome@email.com"
                obrigatorio
                :maxlength="100"
                ajuda="A pessoa precisa ter uma conta no NutriGrana com este e-mail. Depois de enviar, você receberá um código válido por 15 minutos para passar a ela."
                :erro="formularioConvite.errors.email"
                foco-automatico
            />

            <div v-if="convitesPendentes.length > 0" class="space-y-2">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-ng-ink-subtle">Convites pendentes</span>
                <ul class="ng-card-muted divide-y divide-ng-line rounded-xl">
                    <li
                        v-for="convite in convitesPendentes"
                        :key="convite.chave"
                        class="flex items-center gap-3 px-4 py-3"
                    >
                        <span class="material-symbols-outlined text-[18px] text-ng-ink-muted">mail</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-ng-ink">{{ convite.nome_convidado ?? convite.email_convidado }}</p>
                            <p class="truncate text-[11px] text-ng-ink-muted">{{ convite.email_convidado }}</p>
                        </div>
                        <BadgeStatus
                            v-if="convite.esta_expirado"
                            texto="Expirado"
                            variante="red"
                            icone="timer_off"
                            compacto
                        />
                        <BotaoIcone
                            icone="refresh"
                            rotulo="Reenviar convite com novo código"
                            :desabilitado="emailReenviando !== null"
                            @click="reenviarConviteParaEmail(convite.email_convidado)"
                        />
                        <BotaoIcone
                            v-if="convite.url_revogar"
                            icone="block"
                            rotulo="Revogar convite"
                            variante="perigo"
                            :desabilitado="chaveConviteRevogando === convite.chave"
                            @click="revogarConvite(convite)"
                        />
                    </li>
                </ul>
            </div>
        </div>
    </Modal>
</template>
