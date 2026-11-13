<script setup>
import { computed, watch } from 'vue';
import CampoMoeda from '@/Components/Formulario/CampoMoeda.vue';
import CampoPercentual from '@/Components/Formulario/CampoPercentual.vue';
import CampoSelecao from '@/Components/Formulario/CampoSelecao.vue';
import LinhaAlternancia from '@/Components/Formulario/LinhaAlternancia.vue';
import SeletorCor from '@/Components/Formulario/SeletorCor.vue';
import BotaoIcone from '@/Components/Ui/BotaoIcone.vue';
import GrupoSegmentado from '@/Components/Ui/GrupoSegmentado.vue';
import PopoverInformativo from '@/Components/Ui/PopoverInformativo.vue';
import RegraSeparacaoDoSaldo from '@/Components/PlanoFinanceiro/RegraSeparacaoDoSaldo.vue';
import { TIPO_CALCULO_PERCENTUAL } from '@/Helpers/planoFinanceiro';

const props = defineProps({
    idPrefixo: {
        type: String,
        required: true,
    },
    cores: {
        type: Array,
        default: () => [],
    },
    tiposCalculo: {
        type: Array,
        default: () => [],
    },
    opcoesContasBancarias: {
        type: Array,
        default: () => [],
    },
    opcoesObjetivos: {
        type: Array,
        default: () => [],
    },
    opcoesAlocacoes: {
        type: Array,
        default: () => [],
    },
    urlAlocacoes: {
        type: String,
        default: '',
    },
    erros: {
        type: Object,
        default: () => ({}),
    },
    podeRemover: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['remover']);

const nome = defineModel('nome', { type: String, default: '' });
const descricao = defineModel('descricao', { type: String, default: '' });
const cor = defineModel('cor', { type: String, default: null });
const tipoCalculo = defineModel('tipoCalculo', { type: String, default: TIPO_CALCULO_PERCENTUAL });
const valorPercentual = defineModel('valorPercentual', { type: [String, Number], default: '' });
const valorFixo = defineModel('valorFixo', { type: String, default: '0,00' });
const separarDoSaldo = defineModel('separarDoSaldo', { type: Boolean, default: false });
const idContaBancariaDestino = defineModel('idContaBancariaDestino', { type: [String, Number], default: null });
const idObjetivo = defineModel('idObjetivo', { type: [String, Number], default: null });
const idAlocacao = defineModel('idAlocacao', { type: [String, Number], default: null });

const ehPercentual = computed(() => tipoCalculo.value === TIPO_CALCULO_PERCENTUAL);

watch(idAlocacao, (idAlocacaoSelecionada) => {
    if (nome.value.trim() !== '' || !idAlocacaoSelecionada) {
        return;
    }

    const alocacaoSelecionada = props.opcoesAlocacoes.find((opcao) => String(opcao.valor) === String(idAlocacaoSelecionada));
    nome.value = alocacaoSelecionada?.rotulo ?? '';
});

const mensagensErroDaLinha = computed(() => ['nome', 'cor', 'tipo_calculo', 'valor', 'descricao']
    .map((campo) => props.erros[campo])
    .filter(Boolean));

const descricaoSeparacao = computed(() => (separarDoSaldo.value
    ? 'Guardado na conta destino quando a renda for confirmada.'
    : 'Apenas orientação: não desconta do saldo.'));
</script>

<template>
    <article class="ng-card-muted space-y-3 p-4">
        <div class="flex flex-wrap items-center gap-3">
            <SeletorCor v-model="cor" :opcoes="cores" rotulo="" class="shrink-0" />

            <input
                :id="`${idPrefixo}-nome`"
                v-model="nome"
                type="text"
                maxlength="120"
                placeholder="Nome da categoria"
                aria-label="Nome da categoria"
                class="ng-input min-w-[10rem] flex-1 rounded-lg text-sm font-medium"
                :class="erros.nome ? '!border-red-500' : ''"
            >

            <div class="flex items-center gap-2">
                <GrupoSegmentado v-model="tipoCalculo" :opcoes="tiposCalculo" rotulo="Tipo de cálculo" />
                <div class="w-32">
                    <CampoPercentual
                        v-if="ehPercentual"
                        :id="`${idPrefixo}-valor`"
                        v-model="valorPercentual"
                    />
                    <CampoMoeda
                        v-else
                        :id="`${idPrefixo}-valor`"
                        v-model="valorFixo"
                    />
                </div>
            </div>

            <BotaoIcone
                icone="delete"
                rotulo="Remover categoria"
                variante="perigo"
                :desabilitado="!podeRemover"
                @click="emit('remover')"
            />
        </div>

        <input
            :id="`${idPrefixo}-descricao`"
            v-model="descricao"
            type="text"
            maxlength="255"
            placeholder="Descrição curta (opcional)"
            aria-label="Descrição da categoria"
            class="ng-input block w-full rounded-lg text-xs text-ng-ink-muted"
        >

        <ul v-if="mensagensErroDaLinha.length" class="space-y-0.5">
            <li v-for="mensagem in mensagensErroDaLinha" :key="mensagem" class="text-xs text-red-500">{{ mensagem }}</li>
        </ul>

        <LinhaAlternancia
            v-model="separarDoSaldo"
            :valor-ativo="true"
            :valor-inativo="false"
            titulo="Separar do saldo"
            :descricao="descricaoSeparacao"
            icone="savings"
        >
            <template #informacao>
                <PopoverInformativo titulo="Como funciona o desconto" rotulo="Regra de desconto do saldo" posicao="top">
                    <RegraSeparacaoDoSaldo />
                </PopoverInformativo>
            </template>
        </LinhaAlternancia>

        <div v-if="separarDoSaldo" class="grid gap-3 sm:grid-cols-3">
            <div class="space-y-1">
                <CampoSelecao
                    :id="`${idPrefixo}-alocacao`"
                    v-model="idAlocacao"
                    rotulo="Alocação"
                    obrigatorio
                    placeholder="Selecione a alocação"
                    :opcoes="opcoesAlocacoes"
                    :erro="erros.id_alocacao"
                />
                <a
                    v-if="urlAlocacoes && opcoesAlocacoes.length === 0"
                    :href="urlAlocacoes"
                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-ng-brand transition hover:text-ng-brand-hover"
                >
                    <span class="material-symbols-outlined text-[14px]">add</span>
                    Criar alocação
                </a>
            </div>
            <CampoSelecao
                :id="`${idPrefixo}-conta-destino`"
                v-model="idContaBancariaDestino"
                rotulo="Conta destino"
                obrigatorio
                placeholder="Selecione a conta"
                :opcoes="opcoesContasBancarias"
                :erro="erros.id_conta_bancaria_destino"
            />
            <CampoSelecao
                :id="`${idPrefixo}-objetivo`"
                v-model="idObjetivo"
                rotulo="Objetivo (opcional)"
                placeholder="Sem objetivo"
                placeholder-selecionavel
                :opcoes="opcoesObjetivos"
                :erro="erros.id_objetivo"
            />
        </div>
    </article>
</template>
