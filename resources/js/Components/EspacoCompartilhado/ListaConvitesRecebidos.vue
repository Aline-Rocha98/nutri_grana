<script setup>
import { ref } from 'vue';
import BadgeStatus from '@/Components/Ui/BadgeStatus.vue';
import ResponderConviteModal from '@/Components/EspacoCompartilhado/ResponderConviteModal.vue';

defineProps({
    convites: {
        type: Array,
        default: () => [],
    },
});

const conviteEmResposta = ref(null);

function abrirRespostaDoConvite(convite) {
    conviteEmResposta.value = convite;
}

function fecharRespostaDoConvite() {
    conviteEmResposta.value = null;
}
</script>

<template>
    <section v-if="convites.length > 0" class="ng-card overflow-hidden rounded-2xl">
        <header class="flex items-center gap-2 border-b border-ng-line px-5 py-4">
            <span class="material-symbols-outlined text-[18px] text-ng-brand">mail</span>
            <h3 class="text-sm font-semibold text-ng-ink">Convites recebidos</h3>
        </header>

        <ul class="divide-y divide-ng-line">
            <li
                v-for="convite in convites"
                :key="convite.chave"
                class="flex flex-wrap items-center justify-between gap-3 px-5 py-4"
            >
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-ng-ink">{{ convite.nome_espaco }}</p>
                    <p class="text-xs text-ng-ink-muted">
                        {{ convite.nome_convidante ?? 'Um membro' }} convidou você para este espaço
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <BadgeStatus
                        v-if="convite.esta_expirado"
                        texto="Expirado – peça um novo convite"
                        variante="red"
                        icone="timer_off"
                        compacto
                    />
                    <button
                        type="button"
                        class="ng-btn-primary px-4 py-2 text-xs normal-case tracking-normal"
                        @click="abrirRespostaDoConvite(convite)"
                    >
                        Responder
                    </button>
                </div>
            </li>
        </ul>
    </section>

    <ResponderConviteModal
        :aberto="conviteEmResposta !== null"
        :convite="conviteEmResposta"
        @fechar="fecharRespostaDoConvite"
    />
</template>
