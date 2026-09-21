<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    fatura: { type: Object, required: true },
    lancamentos: { type: Array, default: () => [] },
    contasBancarias: { type: Array, default: () => [] },
    urlVoltar: { type: String, required: true },
});

const modalAberto = ref(false);

const form = useForm({
    id_conta_bancaria: props.contasBancarias[0]?.id ?? null,
    data_pagamento: new Date().toISOString().slice(0, 10),
});

function abrirPagar() {
    form.clearErrors();
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
}

function confirmarPagamento() {
    form.post(props.fatura.url_baixar, {
        preserveScroll: true,
        onSuccess: () => fecharModal(),
    });
}
</script>

<template>
    <Head :title="`Fatura ${fatura.competencia}`" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <Link :href="urlVoltar" class="text-sm text-[#1fa67e] hover:underline">
                        ← Voltar às faturas
                    </Link>
                    <h2 class="font-semibold text-xl text-ng-ink leading-tight mt-1">
                        Fatura {{ fatura.competencia }} · {{ fatura.cartao_nome }}
                    </h2>
                </div>
                <button
                    v-if="fatura.pode_baixar"
                    type="button"
                    class="rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#198a68]"
                    @click="abrirPagar"
                >
                    Pagar fatura
                </button>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-ng-card rounded-2xl border border-ng-line p-4 shadow-sm">
                    <p class="text-sm text-ng-ink-muted">Total</p>
                    <p class="mt-1 text-xl font-bold text-red-600">R$ {{ fatura.valor_total }}</p>
                </div>
                <div class="bg-ng-card rounded-2xl border border-ng-line p-4 shadow-sm">
                    <p class="text-sm text-ng-ink-muted">Fechamento</p>
                    <p class="mt-1 text-lg font-semibold text-ng-ink">{{ fatura.data_fechamento_formatada }}</p>
                </div>
                <div class="bg-ng-card rounded-2xl border border-ng-line p-4 shadow-sm">
                    <p class="text-sm text-ng-ink-muted">Vencimento</p>
                    <p class="mt-1 text-lg font-semibold text-ng-ink">
                        {{ fatura.data_vencimento_formatada }} · {{ fatura.situacao_rotulo }}
                    </p>
                </div>
            </div>

            <div class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line">
                <div class="px-6 py-4 border-b border-ng-line">
                    <h3 class="text-base font-semibold text-ng-ink">Lançamentos</h3>
                </div>
                <div class="divide-y divide-ng-line">
                    <div
                        v-for="item in lancamentos"
                        :key="item.id"
                        class="flex items-center gap-4 px-6 py-4"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ng-ink">{{ item.descricao }}</p>
                            <p class="text-sm text-ng-ink-muted">
                                {{ item.data_vencimento_formatada }}
                                <span v-if="item.categoria_nome"> · {{ item.categoria_nome }}</span>
                                · {{ item.situacao_rotulo }}
                            </p>
                        </div>
                        <p class="font-semibold text-red-600">R$ {{ item.valor }}</p>
                    </div>
                    <div
                        v-if="lancamentos.length === 0"
                        class="px-6 py-10 text-center text-sm text-ng-ink-muted"
                    >
                        Nenhum lançamento nesta fatura.
                    </div>
                </div>
            </div>
        </div>

        <Modal :aberto="modalAberto" max-largura="md">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-ng-ink">Pagar fatura</h3>
                <p class="mt-1 text-sm text-ng-ink-muted">
                    R$ {{ fatura.valor_total }} será debitado da conta.
                </p>
                <form class="mt-4 space-y-4" @submit.prevent="confirmarPagamento">
                    <div>
                        <label class="block text-sm font-medium text-ng-ink-secondary">Conta para débito</label>
                        <select
                            v-model="form.id_conta_bancaria"
                            class="mt-1 w-full rounded-lg border-ng-input-border focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                            required
                        >
                            <option v-for="c in contasBancarias" :key="c.id" :value="c.id">{{ c.nome }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ng-ink-secondary">Data do pagamento</label>
                        <input
                            v-model="form.data_pagamento"
                            type="date"
                            class="mt-1 w-full rounded-lg border-ng-input-border focus:border-[#1fa67e] focus:ring-[#1fa67e]"
                        >
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm text-ng-ink-muted hover:bg-ng-brand-soft"
                            @click="fecharModal"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#198a68] disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AutenticadoLayout>
</template>
