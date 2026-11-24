<script setup>
import { computed, ref } from 'vue';
import { notificarErro, notificarSucesso } from '@/Helpers/notificacao';
import { useContagemRegressiva } from '@/Composables/useContagemRegressiva';

const props = defineProps({
    codigo: {
        type: String,
        required: true,
    },
    nomeConvidado: {
        type: String,
        default: '',
    },
    expiraEm: {
        type: String,
        default: '',
    },
    processandoReenvio: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['reenviar']);

const copiado = ref(false);
const { tempoRestanteFormatado, expirou } = useContagemRegressiva(() => props.expiraEm);

const textoInstrucao = computed(() => {
    const destinatario = props.nomeConvidado || 'a pessoa convidada';

    return `Passe este código para ${destinatario}. Ele vale por 15 minutos.`;
});

async function copiarCodigoConvite() {
    try {
        await navigator.clipboard.writeText(props.codigo);
        copiado.value = true;
        notificarSucesso('Código copiado.');
    } catch {
        notificarErro('Não foi possível copiar. Selecione o código e copie manualmente.');
    }
}
</script>

<template>
    <div class="ng-card-muted space-y-3 rounded-xl p-5 text-center">
        <template v-if="!expirou">
            <p class="text-xs font-medium text-ng-ink-muted">{{ textoInstrucao }}</p>
            <p class="select-all font-mono text-3xl font-bold tracking-[0.3em] text-ng-brand">{{ codigo }}</p>
            <p class="inline-flex items-center gap-1 text-xs font-semibold text-ng-ink">
                <span class="material-symbols-outlined text-[16px]">timer</span>
                Expira em {{ tempoRestanteFormatado }}
            </p>
            <p class="text-[11px] text-ng-ink-subtle">
                A pessoa vai precisar informar o seu e-mail e este código. Por segurança, ele não será exibido novamente.
            </p>
            <button
                type="button"
                class="inline-flex items-center gap-1 text-sm font-semibold text-ng-brand transition hover:text-ng-brand-hover"
                @click="copiarCodigoConvite"
            >
                <span class="material-symbols-outlined text-[18px]">{{ copiado ? 'check' : 'content_copy' }}</span>
                {{ copiado ? 'Copiado' : 'Copiar código' }}
            </button>
        </template>

        <template v-else>
            <span class="material-symbols-outlined text-[28px] text-ng-ink-muted">timer_off</span>
            <p class="text-sm font-semibold text-ng-ink">Código expirado</p>
            <p class="text-xs text-ng-ink-muted">Envie um novo convite para gerar outro código.</p>
            <button
                type="button"
                class="ng-btn-primary px-4 py-2 text-xs normal-case tracking-normal"
                :disabled="processandoReenvio"
                @click="emit('reenviar')"
            >
                Enviar novo convite
            </button>
        </template>
    </div>
</template>
