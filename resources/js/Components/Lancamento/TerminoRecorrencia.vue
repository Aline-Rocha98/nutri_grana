<script setup>
import { computed } from 'vue';
import CampoTexto from '@/Components/Formulario/CampoTexto.vue';
import LinhaCampoNumero from '@/Components/Formulario/LinhaCampoNumero.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';
import { dataIsoEhValida, formatarDataIsoParaBrasileira, ultimoDiaDoMes } from '@/Helpers/periodo';
import {
    MAXIMO_OCORRENCIAS,
    MINIMO_OCORRENCIAS,
    OPCOES_TERMINO_RECORRENCIA,
    TERMINO_ATE_MES,
    TERMINO_POR_QUANTIDADE,
    calcularDataDaUltimaOcorrencia,
    contarOcorrenciasAteData,
    limitarQuantidadeOcorrencias,
} from '@/Helpers/recorrencia';

const modo = defineModel('modo', { type: String, required: true });
const quantidade = defineModel('quantidade', { type: Number, default: MINIMO_OCORRENCIAS });
const mesFinal = defineModel('mesFinal', { type: String, default: '' });

const props = defineProps({
    id: {
        type: String,
        default: 'termino-recorrencia',
    },
    dataInicial: {
        type: String,
        default: '',
    },
    frequencia: {
        type: String,
        required: true,
    },
    erro: {
        type: String,
        default: '',
    },
    desabilitado: {
        type: Boolean,
        default: false,
    },
});

function pluralizarLancamentos(total) {
    return total === 1 ? '1 lançamento' : `${total} lançamentos`;
}

const resumoDoTermino = computed(() => {
    if (!dataIsoEhValida(props.dataInicial)) {
        return { texto: 'Informe a data do lançamento para ver o término.', alerta: false };
    }

    if (modo.value === TERMINO_POR_QUANTIDADE) {
        const quantidadeValida = limitarQuantidadeOcorrencias(quantidade.value);
        const dataDaUltimaOcorrencia = calcularDataDaUltimaOcorrencia(props.dataInicial, props.frequencia, quantidadeValida);

        return {
            texto: `${pluralizarLancamentos(quantidadeValida)}, o último em ${formatarDataIsoParaBrasileira(dataDaUltimaOcorrencia)}.`,
            alerta: false,
        };
    }

    if (modo.value === TERMINO_ATE_MES) {
        const dataFinal = ultimoDiaDoMes(mesFinal.value);

        if (!dataFinal) {
            return { texto: 'Escolha o mês do último lançamento.', alerta: false };
        }

        const totalDeOcorrencias = contarOcorrenciasAteData(props.dataInicial, props.frequencia, dataFinal);

        if (totalDeOcorrencias === 0) {
            return { texto: 'O mês final deve ser igual ou posterior à data do lançamento.', alerta: true };
        }

        return {
            texto: `${pluralizarLancamentos(totalDeOcorrencias)} até ${formatarDataIsoParaBrasileira(dataFinal)}.`,
            alerta: false,
        };
    }

    return { texto: 'Os lançamentos serão gerados até você cancelar a recorrência.', alerta: false };
});
</script>

<template>
    <div class="space-y-3">
        <div class="space-y-1.5">
            <span class="block text-sm font-medium text-ng-ink">Término</span>
            <GrupoSegmentado
                v-model="modo"
                :opcoes="OPCOES_TERMINO_RECORRENCIA"
                rotulo="Término da recorrência"
                :desabilitado="desabilitado"
                expandido
            />
        </div>

        <LinhaCampoNumero
            v-if="modo === TERMINO_POR_QUANTIDADE"
            :id="`${id}-quantidade`"
            v-model="quantidade"
            rotulo="Quantidade de lançamentos"
            dica="Contando com o primeiro lançamento"
            :minimo="MINIMO_OCORRENCIAS"
            :maximo="MAXIMO_OCORRENCIAS"
            :desabilitado="desabilitado"
        />

        <CampoTexto
            v-else-if="modo === TERMINO_ATE_MES"
            :id="`${id}-mes-final`"
            v-model="mesFinal"
            tipo="month"
            rotulo="Mês do último lançamento"
        />

        <p
            class="flex items-start gap-1.5 text-xs"
            :class="resumoDoTermino.alerta ? 'text-red-500' : 'text-ng-ink-muted'"
        >
            <span class="material-symbols-outlined text-[16px]">
                {{ resumoDoTermino.alerta ? 'error' : 'event_repeat' }}
            </span>
            {{ resumoDoTermino.texto }}
        </p>

        <p v-if="erro" class="text-xs text-red-500">{{ erro }}</p>
    </div>
</template>
