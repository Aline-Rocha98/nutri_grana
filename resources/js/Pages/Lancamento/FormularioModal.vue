<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import ModalNotificacao from '@/Components/ModalNotificacao.vue';
import { aoDigitarMoeda } from '@/Helpers/mascaraMoeda';

const props = defineProps({
    aberto: { type: Boolean, default: false },
    lancamento: { type: Object, default: null },
    ano: { type: Number, required: true },
    mes: { type: Number, required: true },
    contasBancarias: { type: Array, default: () => [] },
    cartoesCredito: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
    tipos: { type: Array, default: () => [] },
    formasPagamento: { type: Array, default: () => [] },
    situacoes: { type: Array, default: () => [] },
    frequencias: { type: Array, default: () => [] },
    urlCriar: { type: String, required: true },
});

const emit = defineEmits(['fechar']);

const editando = computed(() => Boolean(props.lancamento));
const modalOrcamentoAberto = ref(false);
const mensagemOrcamento = ref('');

function dataDoMes() {
    const hoje = new Date();
    if (hoje.getFullYear() === props.ano && hoje.getMonth() + 1 === props.mes) {
        return hoje.toISOString().slice(0, 10);
    }

    return `${props.ano}-${String(props.mes).padStart(2, '0')}-01`;
}

const formulario = useForm({
    descricao: '',
    valor: '0,00',
    data_vencimento: dataDoMes(),
    tipo: 'despesa',
    forma_pagamento: 'conta_bancaria',
    id_conta_bancaria: null,
    id_cartao_credito: null,
    situacao: 'pendente',
    id_categoria_principal: null,
    id_subcategoria: null,
    observacao: '',
    recorrente: false,
    frequencia_recorrencia: 'mensal',
    total_parcelas: 1,
    confirmar_ultrapassagem_orcamento: false,
});

const estadoUi = reactive({
    urlAtualizar: '',
});

watch(
    () => formulario.errors.confirmar_ultrapassagem_orcamento,
    (mensagem) => {
        if (!mensagem) {
            return;
        }

        mensagemOrcamento.value = mensagem;
        modalOrcamentoAberto.value = true;
    },
);

const categoriasPai = computed(() => {
    const tipoCat = formulario.tipo === 'receita' ? 'entrada' : 'saida';

    return props.categorias.filter((c) => c.tipo === tipoCat && c.arquivada !== 'S');
});

const subcategorias = computed(() => {
    if (!formulario.id_categoria_principal) {
        return [];
    }

    const pai = categoriasPai.value.find((c) => Number(c.id) === Number(formulario.id_categoria_principal));

    return (pai?.subcategorias || []).filter((s) => s.arquivada !== 'S');
});

const mostrarRecorrenciaParcelas = computed(() => {
    if (!editando.value) {
        return true;
    }

    return formulario.recorrente || Number(formulario.total_parcelas) > 1;
});

watch(
    () => [props.aberto, props.lancamento],
    () => {
        if (!props.aberto) {
            return;
        }

        formulario.clearErrors();
        formulario.confirmar_ultrapassagem_orcamento = false;
        modalOrcamentoAberto.value = false;
        mensagemOrcamento.value = '';

        if (props.lancamento) {
            const item = props.lancamento;
            formulario.descricao = item.descricao ?? '';
            formulario.valor = item.valor ?? '0,00';
            formulario.data_vencimento = item.data_vencimento ?? dataDoMes();
            formulario.tipo = item.tipo ?? 'despesa';
            formulario.forma_pagamento = item.forma_pagamento ?? 'conta_bancaria';
            formulario.id_conta_bancaria = item.id_conta_bancaria ?? null;
            formulario.id_cartao_credito = item.id_cartao_credito ?? null;
            formulario.situacao = item.situacao ?? 'pendente';
            formulario.id_categoria_principal = item.id_categoria_principal ?? null;
            formulario.id_subcategoria = item.id_subcategoria ?? null;
            formulario.observacao = item.observacao ?? '';
            formulario.recorrente = Boolean(item.eh_recorrente);
            formulario.frequencia_recorrencia = item.frequencia_recorrencia ?? 'mensal';
            formulario.total_parcelas = item.total_parcelas > 1 ? item.total_parcelas : 1;
            estadoUi.urlAtualizar = item.url_atualizar ?? '';
        } else {
            formulario.reset();
            formulario.descricao = '';
            formulario.valor = '0,00';
            formulario.data_vencimento = dataDoMes();
            formulario.tipo = 'despesa';
            formulario.forma_pagamento = 'conta_bancaria';
            formulario.id_conta_bancaria = props.contasBancarias.find((c) => c.padrao_desconto === 'S')?.id
                ?? props.contasBancarias[0]?.id
                ?? null;
            formulario.id_cartao_credito = props.cartoesCredito.find((c) => c.padrao === 'S')?.id
                ?? props.cartoesCredito[0]?.id
                ?? null;
            formulario.situacao = 'pendente';
            formulario.id_categoria_principal = null;
            formulario.id_subcategoria = null;
            formulario.observacao = '';
            formulario.recorrente = false;
            formulario.frequencia_recorrencia = 'mensal';
            formulario.total_parcelas = 1;
            estadoUi.urlAtualizar = '';
        }
    },
);

watch(
    () => formulario.tipo,
    () => {
        if (!props.aberto) {
            return;
        }

        const paiOk = categoriasPai.value.some((c) => Number(c.id) === Number(formulario.id_categoria_principal));
        if (!paiOk) {
            formulario.id_categoria_principal = null;
            formulario.id_subcategoria = null;
        }
    },
);

watch(
    () => formulario.id_categoria_principal,
    () => {
        const subOk = subcategorias.value.some((s) => Number(s.id) === Number(formulario.id_subcategoria));
        if (!subOk) {
            formulario.id_subcategoria = null;
        }
    },
);

watch(
    () => formulario.forma_pagamento,
    (forma) => {
        if (forma === 'conta_bancaria') {
            formulario.id_cartao_credito = null;
            if (!formulario.id_conta_bancaria && props.contasBancarias.length) {
                formulario.id_conta_bancaria = props.contasBancarias[0].id;
            }
        }
        if (forma === 'cartao_credito') {
            formulario.id_conta_bancaria = null;
            if (!formulario.id_cartao_credito && props.cartoesCredito.length) {
                formulario.id_cartao_credito = props.cartoesCredito[0].id;
            }
        }
    },
);

watch(
    () => formulario.recorrente,
    (ativo) => {
        if (ativo) {
            formulario.total_parcelas = 1;
        }
    },
);

function fechar() {
    emit('fechar');
}

function montarDadosEnvio() {
    const dados = { ...formulario.data() };

    dados.id_categoria = dados.id_subcategoria || dados.id_categoria_principal || null;
    delete dados.id_categoria_principal;
    delete dados.id_subcategoria;

    if (!dados.recorrente) {
        delete dados.frequencia_recorrencia;
    }

    if (Number(dados.total_parcelas) <= 1) {
        dados.total_parcelas = 1;
    }

    if (editando.value) {
        delete dados.recorrente;
        delete dados.frequencia_recorrencia;
        delete dados.total_parcelas;
    }

    return dados;
}

function enviarLancamento() {
    const dados = montarDadosEnvio();
    const opcoes = {
        preserveScroll: true,
        onSuccess: () => {
            modalOrcamentoAberto.value = false;
            fechar();
        },
    };

    if (editando.value) {
        formulario.transform(() => dados).put(estadoUi.urlAtualizar, opcoes);
        return;
    }

    formulario.transform(() => dados).post(props.urlCriar, opcoes);
}

function salvar() {
    formulario.confirmar_ultrapassagem_orcamento = false;
    enviarLancamento();
}

function cancelarUltrapassagemOrcamento() {
    modalOrcamentoAberto.value = false;
    formulario.confirmar_ultrapassagem_orcamento = false;
    formulario.clearErrors('confirmar_ultrapassagem_orcamento');
}

function confirmarUltrapassagemOrcamento() {
    formulario.confirmar_ultrapassagem_orcamento = true;
    enviarLancamento();
}
</script>

<template>
    <Modal :aberto="aberto" max-largura="lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900">
                {{ editando ? 'Editar lançamento' : 'Novo lançamento' }}
            </h3>

            <form class="mt-4 space-y-4" @submit.prevent="salvar">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Descrição</label>
                    <input
                        v-model="formulario.descricao"
                        type="text"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        required
                    >
                    <p v-if="formulario.errors.descricao" class="mt-1 text-sm text-red-600">
                        {{ formulario.errors.descricao }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Valor</label>
                        <input
                            :value="formulario.valor"
                            type="text"
                            inputmode="numeric"
                            class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            required
                            @input="formulario.valor = aoDigitarMoeda($event)"
                        >
                        <p v-if="formulario.errors.valor" class="mt-1 text-sm text-red-600">
                            {{ formulario.errors.valor }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data ocorrência</label>
                        <input
                            v-model="formulario.data_vencimento"
                            type="date"
                            class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            required
                        >
                        <p v-if="formulario.errors.data_vencimento" class="mt-1 text-sm text-red-600">
                            {{ formulario.errors.data_vencimento }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo</label>
                        <select
                            v-model="formulario.tipo"
                            class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                            <option v-for="t in tipos" :key="t.valor" :value="t.valor">{{ t.rotulo }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Situação</label>
                        <select
                            v-model="formulario.situacao"
                            class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                            <option v-for="s in situacoes" :key="s.valor" :value="s.valor">{{ s.rotulo }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Categoria</label>
                        <select
                            v-model="formulario.id_categoria_principal"
                            class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                            <option :value="null">Selecione</option>
                            <option v-for="c in categoriasPai" :key="c.id" :value="c.id">{{ c.nome }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subcategoria</label>
                        <select
                            v-model="formulario.id_subcategoria"
                            class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#1fa67e] focus:ring-[#1fa67e] disabled:bg-gray-100 disabled:text-gray-400"
                            :disabled="!formulario.id_categoria_principal"
                        >
                            <option :value="null">Selecione</option>
                            <option v-for="s in subcategorias" :key="s.id" :value="s.id">{{ s.nome }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Forma de cobrança</label>
                    <select
                        v-model="formulario.forma_pagamento"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                    >
                        <option v-for="f in formasPagamento" :key="f.valor" :value="f.valor">{{ f.rotulo }}</option>
                    </select>
                </div>

                <div v-if="formulario.forma_pagamento === 'conta_bancaria'">
                    <label class="block text-sm font-medium text-gray-700">Conta bancária</label>
                    <select
                        v-model="formulario.id_conta_bancaria"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        required
                    >
                        <option v-for="c in contasBancarias" :key="c.id" :value="c.id">{{ c.nome }}</option>
                    </select>
                    <p v-if="formulario.errors.id_conta_bancaria" class="mt-1 text-sm text-red-600">
                        {{ formulario.errors.id_conta_bancaria }}
                    </p>
                </div>

                <div v-if="formulario.forma_pagamento === 'cartao_credito'">
                    <label class="block text-sm font-medium text-gray-700">Cartão de crédito</label>
                    <select
                        v-model="formulario.id_cartao_credito"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        required
                    >
                        <option v-for="c in cartoesCredito" :key="c.id" :value="c.id">{{ c.nome }}</option>
                    </select>
                    <p v-if="formulario.errors.id_cartao_credito" class="mt-1 text-sm text-red-600">
                        {{ formulario.errors.id_cartao_credito }}
                    </p>
                </div>

                <div v-if="mostrarRecorrenciaParcelas" class="space-y-3 rounded-xl bg-gray-50 p-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            v-model="formulario.recorrente"
                            type="checkbox"
                            class="rounded border-gray-300 text-[#1fa67e] focus:ring-[#1fa67e]"
                            :disabled="editando"
                        >
                        Lançamento recorrente
                    </label>

                    <div v-if="formulario.recorrente" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Frequência</label>
                            <select
                                v-model="formulario.frequencia_recorrencia"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-[#1fa67e] focus:ring-[#1fa67e] disabled:bg-gray-100"
                                :disabled="editando"
                            >
                                <option v-for="f in frequencias" :key="f.valor" :value="f.valor">{{ f.rotulo }}</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="!formulario.recorrente">
                        <label class="block text-xs font-medium text-gray-500">Parcelas</label>
                        <input
                            v-model.number="formulario.total_parcelas"
                            type="number"
                            min="1"
                            max="48"
                            class="mt-1 w-32 rounded-lg border-gray-300 text-sm focus:border-[#1fa67e] focus:ring-[#1fa67e] disabled:bg-gray-100"
                            :disabled="editando"
                        >
                        <p v-if="!editando" class="mt-1 text-xs text-gray-500">
                            Ex.: 2 gera a 1ª no mês da data e a 2ª no mês seguinte.
                        </p>
                        <p v-else-if="lancamento?.parcela_atual" class="mt-1 text-xs text-gray-500">
                            Parcela {{ lancamento.parcela_atual }}/{{ lancamento.total_parcelas }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Observação</label>
                    <textarea
                        v-model="formulario.observacao"
                        rows="2"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                    />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100"
                        @click="fechar"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#198a68] disabled:opacity-60"
                        :disabled="formulario.processing"
                    >
                        {{ editando ? 'Salvar' : 'Criar' }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>

    <ModalNotificacao
        :aberto="modalOrcamentoAberto"
        titulo="Orçamento ultrapassado"
        :mensagem="mensagemOrcamento"
        texto-confirmar="Confirmar lançamento"
        texto-cancelar="Revisar"
        :processando="formulario.processing"
        @confirmar="confirmarUltrapassagemOrcamento"
        @cancelar="cancelarUltrapassagemOrcamento"
    />
</template>
