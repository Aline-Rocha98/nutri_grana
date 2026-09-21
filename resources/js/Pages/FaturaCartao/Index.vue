<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    cartao: { type: Object, required: true },
    faturas: { type: Array, default: () => [] },
    contasBancarias: { type: Array, default: () => [] },
    urlVoltar: { type: String, required: true },
});

const modalAberto = ref(false);
const faturaAtual = ref(null);

const form = useForm({
    id_conta_bancaria: props.contasBancarias[0]?.id ?? null,
    data_pagamento: new Date().toISOString().slice(0, 10),
});

const abertas = computed(() => props.faturas.filter((f) => f.situacao !== 'paga'));
const pagas = computed(() => props.faturas.filter((f) => f.situacao === 'paga'));

function abrirPagar(fatura) {
    faturaAtual.value = fatura;
    form.id_conta_bancaria = props.contasBancarias[0]?.id ?? null;
    form.data_pagamento = new Date().toISOString().slice(0, 10);
    form.clearErrors();
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    faturaAtual.value = null;
}

function confirmarPagamento() {
    if (!faturaAtual.value) {
        return;
    }

    form.post(faturaAtual.value.url_baixar, {
        preserveScroll: true,
        onSuccess: () => fecharModal(),
    });
}
</script>

<template>
    <Head :title="`Faturas · ${cartao.nome}`" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div>
                <Link :href="urlVoltar" class="text-sm text-[#1fa67e] hover:underline">
                    ← Voltar aos cartões
                </Link>
                <h2 class="font-semibold text-xl text-ng-ink leading-tight mt-1">
                    Faturas · {{ cartao.nome }}
                </h2>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line">
                <div class="px-6 py-4 border-b border-ng-line">
                    <h3 class="text-base font-semibold text-ng-ink">Em aberto</h3>
                </div>
                <div class="divide-y divide-ng-line">
                    <div
                        v-for="fatura in abertas"
                        :key="fatura.id"
                        class="flex items-center gap-4 px-6 py-4"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ng-ink">{{ fatura.competencia }}</p>
                            <p class="text-sm text-ng-ink-muted">
                                Fecha {{ fatura.data_fechamento_formatada }}
                                · Vence {{ fatura.data_vencimento_formatada }}
                                ·
                                <span
                                    :class="{
                                        'ng-badge ng-badge--brand': fatura.situacao === 'paga',
                                        'ng-badge ng-badge--amber': fatura.situacao === 'fechada',
                                        'ng-badge ng-badge--sky': fatura.situacao === 'aberta',
                                    }"
                                >
                                    {{ fatura.situacao_rotulo }}
                                </span>
                            </p>
                        </div>
                        <p class="font-semibold text-red-600 shrink-0">R$ {{ fatura.valor_total }}</p>
                        <div class="flex items-center gap-2 shrink-0">
                            <Link
                                :href="fatura.url_detalhe"
                                class="rounded-lg px-3 py-1.5 text-sm text-ng-ink-muted hover:bg-ng-brand-soft"
                            >
                                Detalhe
                            </Link>
                            <button
                                v-if="fatura.pode_baixar"
                                type="button"
                                class="rounded-lg bg-[#1fa67e] px-3 py-1.5 text-sm font-semibold text-white hover:bg-[#198a68]"
                                @click="abrirPagar(fatura)"
                            >
                                Pagar
                            </button>
                        </div>
                    </div>
                    <div
                        v-if="abertas.length === 0"
                        class="px-6 py-10 text-center text-sm text-ng-ink-muted"
                    >
                        Nenhuma fatura em aberto.
                    </div>
                </div>
            </div>

            <div
                v-if="pagas.length > 0"
                class="bg-ng-card overflow-hidden shadow-sm rounded-2xl border border-ng-line opacity-90"
            >
                <div class="px-6 py-4 border-b border-ng-line">
                    <h3 class="text-base font-semibold text-ng-ink">Pagas</h3>
                </div>
                <div class="divide-y divide-ng-line">
                    <div
                        v-for="fatura in pagas"
                        :key="fatura.id"
                        class="flex items-center gap-4 px-6 py-4"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ng-ink">{{ fatura.competencia }}</p>
                            <p class="text-sm text-ng-ink-muted">
                                <span class="ng-badge ng-badge--brand">{{ fatura.situacao_rotulo }}</span>
                            </p>
                        </div>
                        <p class="font-semibold text-ng-ink-muted">R$ {{ fatura.valor_total }}</p>
                        <Link
                            :href="fatura.url_detalhe"
                            class="rounded-lg px-3 py-1.5 text-sm text-ng-ink-muted hover:bg-ng-brand-soft"
                        >
                            Detalhe
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <Modal :aberto="modalAberto" max-largura="md">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-ng-ink">Pagar fatura</h3>
                <p v-if="faturaAtual" class="mt-1 text-sm text-ng-ink-muted">
                    {{ faturaAtual.competencia }} · R$ {{ faturaAtual.valor_total }}
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
                        <p v-if="form.errors.id_conta_bancaria" class="mt-1 text-sm text-red-600">
                            {{ form.errors.id_conta_bancaria }}
                        </p>
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
