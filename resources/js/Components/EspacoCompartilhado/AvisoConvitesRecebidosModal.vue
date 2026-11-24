<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import ResponderConviteModal from '@/Components/EspacoCompartilhado/ResponderConviteModal.vue';

const pagina = usePage();
const convitesDoAviso = ref([]);
const avisoAberto = ref(false);
const conviteEmResposta = ref(null);
const chaveConviteRecusando = ref(null);

const subtituloDoAviso = computed(() => (convitesDoAviso.value.length === 1
    ? 'Você recebeu um convite para participar de um espaço compartilhado.'
    : `Você recebeu ${convitesDoAviso.value.length} convites para espaços compartilhados.`));

watch(
    () => pagina.props.convitesPendentesAoEntrar,
    (convites) => {
        if (!Array.isArray(convites) || convites.length === 0) {
            return;
        }

        convitesDoAviso.value = convites;
        avisoAberto.value = true;
    },
    { immediate: true },
);

function fecharAviso() {
    avisoAberto.value = false;
}

function removerConviteDoAviso(convite) {
    convitesDoAviso.value = convitesDoAviso.value.filter((item) => item.chave !== convite.chave);
}

function abrirRespostaDoConvite(convite) {
    avisoAberto.value = false;
    conviteEmResposta.value = convite;
}

function fecharRespostaDoConvite() {
    conviteEmResposta.value = null;
    avisoAberto.value = convitesDoAviso.value.length > 0;
}

function recusarConviteDoAviso(convite) {
    if (chaveConviteRecusando.value) {
        return;
    }

    chaveConviteRecusando.value = convite.chave;

    router.post(convite.url_recusar, {}, {
        preserveScroll: true,
        onSuccess: () => {
            removerConviteDoAviso(convite);
            avisoAberto.value = convitesDoAviso.value.length > 0;
        },
        onFinish: () => {
            chaveConviteRecusando.value = null;
        },
    });
}
</script>

<template>
    <Modal
        :aberto="avisoAberto"
        max-largura="md"
        icone="mail"
        titulo="Convites recebidos"
        :subtitulo="subtituloDoAviso"
        :processando="chaveConviteRecusando !== null"
        @fechar="fecharAviso"
    >
        <ul class="ng-card-muted divide-y divide-ng-line rounded-xl">
            <li
                v-for="convite in convitesDoAviso"
                :key="convite.chave"
                class="space-y-3 px-4 py-4"
            >
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined mt-0.5 text-[20px] text-ng-brand">group_add</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-ng-ink">
                            <span class="font-semibold">{{ convite.nome_convidante ?? 'Um membro' }}</span>
                            convidou você para o espaço
                            <span class="font-semibold">{{ convite.nome_espaco }}</span>.
                        </p>
                        <BadgeStatus
                            v-if="convite.esta_expirado"
                            class="mt-2"
                            texto="Expirado – peça um novo convite"
                            variante="red"
                            icone="timer_off"
                            compacto
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-xs font-semibold text-ng-ink-muted transition hover:text-ng-ink disabled:opacity-60"
                        :disabled="chaveConviteRecusando !== null"
                        @click="recusarConviteDoAviso(convite)"
                    >
                        Recusar
                    </button>
                    <button
                        v-if="!convite.esta_expirado"
                        type="button"
                        class="ng-btn-primary px-4 py-2 text-xs normal-case tracking-normal"
                        :disabled="chaveConviteRecusando !== null"
                        @click="abrirRespostaDoConvite(convite)"
                    >
                        Responder
                    </button>
                </div>
            </li>
        </ul>

        <template #rodape="{ fechar }">
            <button
                type="button"
                class="rounded-lg px-4 py-2.5 text-sm font-medium text-ng-ink-muted transition hover:text-ng-ink"
                @click="fechar"
            >
                Ver depois
            </button>
        </template>
    </Modal>

    <ResponderConviteModal
        :aberto="conviteEmResposta !== null"
        :convite="conviteEmResposta"
        @recusado="removerConviteDoAviso"
        @fechar="fecharRespostaDoConvite"
    />
</template>
