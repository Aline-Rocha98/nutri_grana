<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AutenticadoLayout from '@/Layouts/AutenticadoLayout.vue';

defineProps({
    usuario: {
        type: Object,
        required: true,
    },
    dataHoje: {
        type: String,
        required: true,
    },
});

const agora = new Date();
const urlLancamentos = `/lancamentos/${agora.getFullYear()}/${agora.getMonth() + 1}`;
</script>

<template>
    <Head title="Home" />

    <AutenticadoLayout>
        <template #cabecalho>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Home</h2>
                <span class="text-sm text-gray-500">{{ dataHoje }}</span>
            </div>
        </template>

        <div class="p-6 lg:p-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-[#1fa67e]">
                    Olá, {{ usuario.nome }}!
                </h3>
                <p class="mt-2 text-gray-600">
                    Pequenas ações geram grandes resultados. Organize suas finanças pelo menu lateral.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Link
                    :href="urlLancamentos"
                    class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center hover:border-[#1fa67e]/40 transition"
                >
                    <h4 class="font-semibold text-gray-800">Lançamentos</h4>
                    <p class="mt-2 text-sm text-gray-500">Receitas e despesas do mês</p>
                </Link>
                <Link
                    href="/contas-bancarias"
                    class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center hover:border-[#1fa67e]/40 transition"
                >
                    <h4 class="font-semibold text-gray-800">Contas</h4>
                    <p class="mt-2 text-sm text-gray-500">Saldos e contas bancárias</p>
                </Link>
                <Link
                    href="/categorias"
                    class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center hover:border-[#1fa67e]/40 transition"
                >
                    <h4 class="font-semibold text-gray-800">Categorias</h4>
                    <p class="mt-2 text-sm text-gray-500">Organize suas categorias</p>
                </Link>
            </div>
        </div>
    </AutenticadoLayout>
</template>
