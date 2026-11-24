<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import AlertaMensagem from '@/Components/Ui/AlertaMensagem.vue';
import { formatarCodigoConvite } from '@/Helpers/espacoCompartilhado';
import { useContagemRegressiva } from '@/Composables/useContagemRegressiva';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    convite: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['fechar', 'recusado']);

const formularioResposta = useForm({ email_convidante: '', codigo: '' });
const recusandoConvite = ref(false);

const { tempoRestanteFormatado, expirou } = useContagemRegressiva(() => props.convite?.expira_em ?? null);

const nomeConvidante = computed(() => props.convite?.nome_convidante ?? 'Um membro');
const conviteExpirado = computed(() => Boolean(props.convite?.esta_expirado) || expirou.value);
const processando = computed(() => formularioResposta.processing || recusandoConvite.value);
const textoConvite = computed(
    () => `${nomeConvidante.value} convidou você para o espaço "${props.convite?.nome_espaco ?? ''}".`,
);

watch(
    () => props.aberto,
    (aberto) => {
        if (!aberto) {
            return;
        }

        formularioResposta.reset();
        formularioResposta.clearErrors();
    },
);

function fechar() {
    emit('fechar');
}

function atualizarCodigoDigitado(valor) {
    formularioResposta.codigo = formatarCodigoConvite(valor);
}

function aceitarConviteComCodigo() {
    if (!props.convite || conviteExpirado.value) {
        return;
    }

    formularioResposta.post(props.convite.url_aceitar, {
        preserveScroll: true,
        onSuccess: fechar,
        onError: () => formularioResposta.reset('codigo'),
    });
}

function recusarConvite() {
    if (!props.convite || processando.value) {
        return;
    }

    recusandoConvite.value = true;

    router.post(props.convite.url_recusar, {}, {
        preserveScroll: true,
        onSuccess: () => {
            emit('recusado', props.convite);
            fechar();
        },
        onFinish: () => {
            recusandoConvite.value = false;
        },
    });
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="md"
        icone="group_add"
        titulo="Responder convite"
        :subtitulo="textoConvite"
        texto-confirmar="Aceitar convite"
        :processando="processando"
        :desabilitar-confirmar="conviteExpirado"
        @confirmar="aceitarConviteComCodigo"
        @fechar="fechar"
    >
        <div class="space-y-4">
            <AlertaMensagem
                v-if="conviteExpirado"
                variante="warning"
                titulo="Código expirado."
                :mensagem="`Peça para ${nomeConvidante} enviar um novo convite.`"
            />

            <template v-else>
                <p class="text-xs text-ng-ink-muted">
                    Para entrar, informe o e-mail de {{ nomeConvidante }} e o código que essa pessoa passou para você.
                </p>

                <p class="inline-flex items-center gap-1 text-xs font-semibold text-ng-ink">
                    <span class="material-symbols-outlined text-[16px]">timer</span>
                    Código válido por mais {{ tempoRestanteFormatado }}
                </p>

                <CampoTexto
                    id="resposta-convite-email"
                    v-model="formularioResposta.email_convidante"
                    tipo="email"
                    rotulo="E-mail de quem convidou"
                    placeholder="nome@email.com"
                    obrigatorio
                    :maxlength="100"
                    autocomplete="off"
                    :erro="formularioResposta.errors.email_convidante"
                    foco-automatico
                />

                <CampoTexto
                    id="resposta-convite-codigo"
                    :model-value="formularioResposta.codigo"
                    rotulo="Código do convite"
                    placeholder="XXXX-XXXX"
                    alinhamento="centro"
                    mono
                    obrigatorio
                    :maxlength="9"
                    autocomplete="off"
                    :erro="formularioResposta.errors.codigo"
                    @update:model-value="atualizarCodigoDigitado"
                />
            </template>
        </div>

        <template #rodape="{ fechar: fecharModal }">
            <button
                type="button"
                class="mr-auto rounded-lg px-3 py-2.5 text-sm font-medium text-red-400 transition hover:bg-red-500/10 disabled:opacity-60"
                :disabled="processando"
                @click="recusarConvite"
            >
                Recusar
            </button>
            <button
                type="button"
                class="rounded-lg px-4 py-2.5 text-sm font-medium text-ng-ink-muted transition hover:text-ng-ink"
                :disabled="processando"
                @click="fecharModal"
            >
                Cancelar
            </button>
            <button
                type="submit"
                class="ng-btn-primary px-5 py-2.5 text-sm normal-case tracking-normal"
                :disabled="processando || conviteExpirado"
            >
                Aceitar convite
            </button>
        </template>
    </Modal>
</template>
