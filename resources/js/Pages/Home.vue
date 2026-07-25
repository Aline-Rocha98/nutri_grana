<script setup>
import { Head } from '@inertiajs/vue3';
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
                <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center">
                    <h4 class="font-semibold text-gray-800">Meses</h4>
                    <p class="mt-2 text-sm text-gray-500">Gerencie seus meses financeiros</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center">
                    <h4 class="font-semibold text-gray-800">Lançamentos</h4>
                    <p class="mt-2 text-sm text-gray-500">Visualize todos os seus lançamentos</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6 text-center">
                    <h4 class="font-semibold text-gray-800">Categorias</h4>
                    <p class="mt-2 text-sm text-gray-500">Gerencie suas categorias</p>
                </div>
            </div>
        </div>
    </AutenticadoLayout>
</template>
