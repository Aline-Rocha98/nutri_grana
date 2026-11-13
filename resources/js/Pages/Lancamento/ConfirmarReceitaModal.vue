<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import AvisoInformativo from '@/Components/Formulario/AvisoInformativo.vue';
import CampoMoeda from '@/Components/Formulario/CampoMoeda.vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import LancamentoVisual from '@/Components/Lancamento/LancamentoVisual.vue';
import { varianteSituacaoLancamento } from '@/Helpers/badge';
import { formatarNumeroParaMoeda, moedaParaNumero } from '@/Helpers/mascaraMoeda';
import { distribuirValoresDoPlano } from '@/Helpers/planoFinanceiro';
import { formatarDataIsoParaBrasileira } from '@/Helpers/periodo';

const props = defineProps({
    aberto: {
        type: Boolean,
        default: false,
    },
    lancamento: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['fechar']);

const formulario = useForm({
    valor_recebido: '',
    data_recebimento: '',
});

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.valor_recebido = props.lancamento?.valor ?? props.lancamento?.valor_previsto ?? '0,00';
    formulario.data_recebimento = props.lancamento?.data_vencimento ?? new Date().toISOString().slice(0, 10);
}

watch(
    () => [props.aberto, props.lancamento],
    () => {
        if (props.aberto) {
            reiniciarFormulario();
        }
    },
);

const valorPrevistoFormatado = computed(() =>
    props.lancamento?.valor_previsto ?? formatarNumeroParaMoeda(props.lancamento?.valor_numero),
);

const itensPlanoQueSeraoSeparados = computed(() => distribuirValoresDoPlano(
    props.lancamento?.itens_plano_financeiro ?? [],
    moedaParaNumero(formulario.valor_recebido),
).filter((item) => item.valor_separado > 0));

const totalGuardadoPrevisto = computed(() => itensPlanoQueSeraoSeparados.value
    .reduce((total, item) => total + item.valor_separado, 0));

const rotuloGuardadoPrevisto = computed(() => (props.lancamento?.plano_financeiro_nome
    ? `Será guardado pelo plano ${props.lancamento.plano_financeiro_nome}:`
    : 'Será guardado automaticamente:'));

const nomeOrigem = computed(() =>
    props.lancamento?.conta_bancaria_nome ?? props.lancamento?.cartao_credito_nome ?? '',
);

function fechar() {
    emit('fechar');
}

function confirmarRecebimentoReceita() {
    if (!props.lancamento?.url_confirmar_receita) {
        return;
    }

    formulario.patch(props.lancamento.url_confirmar_receita, {
        preserveScroll: true,
        onSuccess: () => fechar(),
    });
}
</script>

<template>
    <Modal
        :aberto="aberto"
        max-largura="lg"
        icone="south_west"
        titulo="Confirmar receita"
        subtitulo="Informe o valor realmente recebido para atualizar o lançamento."
        texto-confirmar="Confirmar recebimento"
        :processando="formulario.processing"
        @confirmar="confirmarRecebimentoReceita"
        @fechar="fechar"
    >
        <div class="space-y-5">
            <LancamentoVisual
                tipo="receita"
                :descricao="lancamento?.descricao ?? ''"
                :valor="formulario.valor_recebido"
                :data="formatarDataIsoParaBrasileira(formulario.data_recebimento)"
                :categoria="lancamento?.categoria_nome ?? ''"
                :situacao-rotulo="lancamento?.situacao_rotulo ?? ''"
                :situacao-variante="varianteSituacaoLancamento(lancamento?.situacao)"
                situacao-icone="schedule"
                :origem="nomeOrigem"
            />

            <AvisoInformativo
                variante="sky"
                rotulo="Valor previsto em Rendas:"
                :valor="`R$ ${valorPrevistoFormatado}`"
                complemento="serve apenas como referência"
            />

            <div v-if="itensPlanoQueSeraoSeparados.length > 0" class="space-y-2">
                <AvisoInformativo
                    variante="sky"
                    :rotulo="rotuloGuardadoPrevisto"
                    :valor="`R$ ${formatarNumeroParaMoeda(totalGuardadoPrevisto)}`"
                    complemento="não entra no saldo disponível"
                />
                <ul class="space-y-1 px-1 text-xs text-ng-ink-muted">
                    <li
                        v-for="item in itensPlanoQueSeraoSeparados"
                        :key="item.id"
                        class="flex items-center justify-between gap-3"
                    >
                        <span class="flex min-w-0 items-center gap-1.5 truncate">
                            <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: item.cor_hexadecimal }" />
                            {{ item.nome }} ({{ item.regra_formatada }})
                            <span v-if="item.conta_bancaria_destino_nome">→ {{ item.conta_bancaria_destino_nome }}</span>
                        </span>
                        <span class="shrink-0 font-mono text-sky-500">R$ {{ formatarNumeroParaMoeda(item.valor_separado) }}</span>
                    </li>
                </ul>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <CampoMoeda
                    id="receita-valor-recebido"
                    v-model="formulario.valor_recebido"
                    rotulo="Valor recebido"
                    obrigatorio
                    :erro="formulario.errors.valor_recebido"
                />
                <CampoTexto
                    id="receita-data-recebimento"
                    v-model="formulario.data_recebimento"
                    tipo="date"
                    rotulo="Data de recebimento"
                    obrigatorio
                    :erro="formulario.errors.data_recebimento"
                />
            </div>
        </div>
    </Modal>
</template>
