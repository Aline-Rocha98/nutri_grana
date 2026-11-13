<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    nomeUsuario: {
        type: String,
        required: true,
    },
    urlDashboard: {
        type: String,
        default: '/dashboard',
    },
    urlRegistrarPagamento: {
        type: String,
        default: '/lancamentos',
    },
    urlAporteRapido: {
        type: String,
        default: '/objetivos',
    },
});
</script>

<template>
    <section class="ng-card relative overflow-hidden p-6">
        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-br from-ng-brand-soft via-transparent to-transparent"
            aria-hidden="true"
        />

        <div class="relative flex flex-wrap items-center justify-between gap-6">
            <div class="min-w-0 max-w-2xl">
                <span class="ng-selecionavel--ativo inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-semibold text-ng-brand">
                    <span class="material-symbols-outlined text-[14px]">auto_awesome</span>
                    Central do Dia • Visão Operacional
                </span>

                <h2 class="mt-3 text-xl font-bold text-ng-ink">Olá, {{ nomeUsuario }}! O que temos para hoje?</h2>
                
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <Link :href="urlRegistrarPagamento" class="ng-btn-primary">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Registrar Pagamento
                </Link>
                <Link :href="urlAporteRapido" class="ng-btn-suave">
                    <span class="material-symbols-outlined text-[18px]">savings</span>
                    Aporte Rápido
                </Link>
            </div>
        </div>
    </section>
</template>
