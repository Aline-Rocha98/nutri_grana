<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Home
            </h2>
            <span class="text-sm text-gray-500">{{ now()->format('d/m/Y') }}</span>
        </div>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-[#1fa67e]">
                Olá, {{ $usuario->nome }}!
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
</x-app-layout>
