<script setup>
import { computed, reactive, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { aoDigitarMoeda } from '@/Helpers/mascaraMoeda';

const props = defineProps({
    aberto: { type: Boolean, default: false },
    orcamento: { type: Object, default: null },
    categorias: { type: Array, default: () => [] },
    contasBancarias: { type: Array, default: () => [] },
    cartoesCredito: { type: Array, default: () => [] },
    formasPagamento: { type: Array, default: () => [] },
    modalidadesPagamento: { type: Array, default: () => [] },
});

const emit = defineEmits(['fechar']);

const pagina = usePage();
const urlCriar = computed(() => pagina.props.rotas.orcamentosServicoCriar);
const editando = computed(() => Boolean(props.orcamento));

const formulario = useForm({
    descricao: '',
    fornecedor: '',
    valor: '0,00',
    data_orcamento: '',
    data_validade: '',
    observacao: '',
    id_categoria: null,
    id_subcategoria: null,
    modalidade_pagamento: 'a_vista',
    forma_pagamento: 'conta_bancaria',
    id_conta_bancaria: null,
    id_cartao_credito: null,
});

const estadoUi = reactive({ urlAtualizar: '' });

const parcelado = computed(() => formulario.modalidade_pagamento === 'parcelado');
const pagamentoConta = computed(() => formulario.forma_pagamento === 'conta_bancaria');
const pagamentoCartao = computed(() => formulario.forma_pagamento === 'cartao_credito');

const contaSelecionada = computed(() => props.contasBancarias.find(
    (conta) => Number(conta.id) === Number(formulario.id_conta_bancaria),
) ?? null);

const cartaoSelecionado = computed(() => props.cartoesCredito.find(
    (cartao) => Number(cartao.id) === Number(formulario.id_cartao_credito),
) ?? null);

function normalizarId(valor) {
    if (valor === null || valor === undefined || valor === '') {
        return null;
    }

    const numero = Number(valor);

    return Number.isNaN(numero) ? null : numero;
}

const subcategoriasDisponiveis = computed(() => {
    const categoria = props.categorias.find((c) => c.id === formulario.id_categoria);
    return categoria?.subcategorias?.filter((s) => s.arquivada !== 'S') ?? [];
});

function contaPadrao() {
    return props.contasBancarias.find((c) => c.padrao_desconto === 'S')?.id
        ?? props.contasBancarias[0]?.id
        ?? null;
}

function cartaoPadrao() {
    return props.cartoesCredito.find((c) => c.padrao === 'S')?.id
        ?? props.cartoesCredito[0]?.id
        ?? null;
}

function dataHoje() {
    return new Date().toISOString().slice(0, 10);
}

function reiniciarFormulario() {
    formulario.clearErrors();
    formulario.descricao = props.orcamento?.descricao ?? '';
    formulario.fornecedor = props.orcamento?.fornecedor ?? '';
    formulario.valor = props.orcamento?.valor ?? '0,00';
    formulario.data_orcamento = props.orcamento?.data_orcamento ?? dataHoje();
    formulario.data_validade = props.orcamento?.data_validade ?? '';
    formulario.observacao = props.orcamento?.observacao ?? '';
    formulario.id_categoria = props.orcamento?.id_categoria ?? null;
    formulario.id_subcategoria = props.orcamento?.id_subcategoria ?? null;
    formulario.modalidade_pagamento = props.orcamento?.modalidade_pagamento ?? 'a_vista';
    formulario.forma_pagamento = props.orcamento?.forma_pagamento ?? 'conta_bancaria';
    formulario.id_conta_bancaria = normalizarId(props.orcamento?.id_conta_bancaria) ?? contaPadrao();
    formulario.id_cartao_credito = normalizarId(props.orcamento?.id_cartao_credito) ?? cartaoPadrao();
    estadoUi.urlAtualizar = props.orcamento?.url_atualizar ?? '';
}

watch(() => [props.aberto, props.orcamento], () => {
    if (props.aberto) {
        reiniciarFormulario();
    }
});

watch(() => formulario.id_categoria, () => {
    if (!subcategoriasDisponiveis.value.some((s) => s.id === formulario.id_subcategoria)) {
        formulario.id_subcategoria = null;
    }
});

watch(() => formulario.forma_pagamento, (forma) => {
    if (forma === 'conta_bancaria' && !formulario.id_conta_bancaria) {
        formulario.id_conta_bancaria = contaPadrao();
    }
    if (forma === 'cartao_credito' && !formulario.id_cartao_credito) {
        formulario.id_cartao_credito = cartaoPadrao();
    }
});

function fechar() {
    emit('fechar');
}

function salvar() {
    const opcoes = { preserveScroll: true, onSuccess: () => fechar() };

    if (editando.value) {
        formulario.put(estadoUi.urlAtualizar, opcoes);
        return;
    }

    formulario.post(urlCriar.value, opcoes);
}
</script>

<template>
    <Modal :aberto="aberto" max-largura="lg">
        <form class="p-6" @submit.prevent="salvar">
            <h2 class="text-lg font-semibold text-gray-900">
                {{ editando ? 'Editar cotação' : 'Nova cotação' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Registre a proposta e a forma de pagamento prevista para simular o impacto no fluxo.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <label for="descricao-cotacao" class="block text-sm font-medium text-gray-500">Descrição</label>
                    <input
                        id="descricao-cotacao"
                        v-model="formulario.descricao"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="Ex.: Pintura do apartamento"
                    >
                    <p v-if="formulario.errors.descricao" class="mt-2 text-sm text-red-600">{{ formulario.errors.descricao }}</p>
                </div>

                <div>
                    <label for="fornecedor-cotacao" class="block text-sm font-medium text-gray-500">Fornecedor</label>
                    <input
                        id="fornecedor-cotacao"
                        v-model="formulario.fornecedor"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="Ex.: Loja de móveis XYZ"
                    >
                </div>

                <div>
                    <label for="valor-cotacao" class="block text-sm font-medium text-gray-500">Valor estimado</label>
                    <input
                        id="valor-cotacao"
                        :value="formulario.valor"
                        type="text"
                        inputmode="numeric"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="0,00"
                        @input="formulario.valor = aoDigitarMoeda($event)"
                    >
                    <p v-if="formulario.errors.valor" class="mt-2 text-sm text-red-600">{{ formulario.errors.valor }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="data-cotacao" class="block text-sm font-medium text-gray-500">Data da cotação</label>
                        <input
                            id="data-cotacao"
                            v-model="formulario.data_orcamento"
                            type="date"
                            class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                        <p v-if="formulario.errors.data_orcamento" class="mt-2 text-sm text-red-600">{{ formulario.errors.data_orcamento }}</p>
                    </div>
                    <div>
                        <label for="validade-cotacao" class="block text-sm font-medium text-gray-500">Validade</label>
                        <input
                            id="validade-cotacao"
                            v-model="formulario.data_validade"
                            type="date"
                            class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                        <p v-if="formulario.errors.data_validade" class="mt-2 text-sm text-red-600">{{ formulario.errors.data_validade }}</p>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 space-y-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Pagamento previsto</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Modalidade</label>
                            <select
                                v-model="formulario.modalidade_pagamento"
                                class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            >
                                <option v-for="opcao in modalidadesPagamento" :key="opcao.valor" :value="opcao.valor">
                                    {{ opcao.rotulo }}
                                </option>
                            </select>
                            <p v-if="formulario.errors.modalidade_pagamento" class="mt-1 text-sm text-red-600">
                                {{ formulario.errors.modalidade_pagamento }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Forma</label>
                            <select
                                v-model="formulario.forma_pagamento"
                                class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            >
                                <option v-for="opcao in formasPagamento" :key="opcao.valor" :value="opcao.valor">
                                    {{ opcao.valor === 'conta_bancaria' ? 'PIX' : opcao.rotulo }}
                                </option>
                            </select>
                            <p v-if="formulario.errors.forma_pagamento" class="mt-1 text-sm text-red-600">
                                {{ formulario.errors.forma_pagamento }}
                            </p>
                        </div>
                    </div>

                    <div v-if="pagamentoConta">
                        <label class="block text-sm font-medium text-gray-500">Conta bancária</label>
                        <select
                            v-model.number="formulario.id_conta_bancaria"
                            class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                            <option v-for="conta in contasBancarias" :key="conta.id" :value="Number(conta.id)">
                                {{ conta.nome }}
                            </option>
                        </select>
                        <p v-if="contaSelecionada" class="mt-1 text-xs text-gray-500">
                            Saldo disponível nesta conta: R$ {{ contaSelecionada.saldo_atual }}
                        </p>
                        <p v-if="formulario.errors.id_conta_bancaria" class="mt-1 text-sm text-red-600">
                            {{ formulario.errors.id_conta_bancaria }}
                        </p>
                    </div>

                    <div v-if="pagamentoCartao">
                        <label class="block text-sm font-medium text-gray-500">Cartão de crédito</label>
                        <select
                            v-model.number="formulario.id_cartao_credito"
                            class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                            <option v-for="cartao in cartoesCredito" :key="cartao.id" :value="Number(cartao.id)">
                                {{ cartao.nome }}
                            </option>
                        </select>
                        <p v-if="cartaoSelecionado" class="mt-1 text-xs text-gray-500">
                            Limite disponível: R$ {{ cartaoSelecionado.limite_disponivel }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            O limite do cartão é usado apenas na validação, não entra como saldo em conta.
                        </p>
                        <p v-if="formulario.errors.id_cartao_credito" class="mt-1 text-sm text-red-600">
                            {{ formulario.errors.id_cartao_credito }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="categoria-cotacao" class="block text-sm font-medium text-gray-500">Categoria</label>
                        <select
                            id="categoria-cotacao"
                            v-model="formulario.id_categoria"
                            class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                            <option :value="null">Selecione...</option>
                            <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="subcategoria-cotacao" class="block text-sm font-medium text-gray-500">Subcategoria</label>
                        <select
                            id="subcategoria-cotacao"
                            v-model="formulario.id_subcategoria"
                            class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            :disabled="!formulario.id_categoria"
                        >
                            <option :value="null">Selecione...</option>
                            <option v-for="sub in subcategoriasDisponiveis" :key="sub.id" :value="sub.id">{{ sub.nome }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="observacao-cotacao" class="block text-sm font-medium text-gray-500">Observação</label>
                    <textarea
                        id="observacao-cotacao"
                        v-model="formulario.observacao"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-200 bg-white text-gray-900 focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        placeholder="Condições, detalhes da proposta..."
                    />
                </div>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                    @click="fechar"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 rounded-md bg-[#1fa67e] text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#198a68] transition"
                    :disabled="formulario.processing"
                >
                    Salvar cotação
                </button>
            </div>
        </form>
    </Modal>
</template>
