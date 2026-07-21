<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Contas bancárias
            </h2>
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg bg-[#1fa67e] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#198a68] transition"
                x-data
                x-on:click="$dispatch('abrir-criar-conta-bancaria')"
            >
                <span class="material-symbols-outlined text-base leading-none">add</span>
                Adicionar conta
            </button>
        </div>
    </x-slot>

    <div
        class="p-6 lg:p-8 space-y-6"
        x-data="{
            tipos: @js($tipos),
            formulario: {
                modo: 'criar',
                nome: @js(old('nome', '')),
                saldo_inicial: @js(old('saldo_inicial', '0,00')),
                tipo: @js(old('tipo', $tipos[0]['valor'] ?? 'corrente')),
                urlAtualizar: '',
            },
            abrirCriar() {
                this.formulario = {
                    modo: 'criar',
                    nome: '',
                    saldo_inicial: '0,00',
                    tipo: this.tipos[0]?.valor ?? 'corrente',
                    urlAtualizar: '',
                };
                this.$dispatch('open-modal', 'formulario-conta-bancaria');
                this.$dispatch('abrir-select2-conta', { valor: this.formulario.tipo });
            },
            abrirEditar(conta) {
                this.formulario = {
                    modo: 'editar',
                    nome: conta.nome,
                    saldo_inicial: conta.saldo_inicial,
                    tipo: conta.tipo,
                    urlAtualizar: conta.url_atualizar,
                };
                this.$dispatch('open-modal', 'formulario-conta-bancaria');
                this.$dispatch('abrir-select2-conta', { valor: this.formulario.tipo });
            },
        }"
        x-on:abrir-criar-conta-bancaria.window="abrirCriar()"
        x-on:tipo-conta-bancaria-alterado="formulario.tipo = $event.detail.valor"
        x-on:abrir-select2-conta.window="
            setTimeout(() => window.NutriGranaContasBancarias?.inicializarSelect2Tipo($event.detail?.valor ?? formulario.tipo), 80)
        "
    >
        @if (session('sucesso'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('sucesso') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $contasAtivas = $contasBancarias->where('arquivada', false);
            $contasArquivadas = $contasBancarias->where('arquivada', true);
            $saldoGeral = $contasAtivas->sum(fn ($conta) => (float) $conta->saldo_inicial);
        @endphp

        <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
            <p class="text-sm font-medium text-gray-500">Saldo geral</p>
            <p class="mt-1 text-2xl font-bold text-[#1fa67e]">
                R$ {{ number_format($saldoGeral, 2, ',', '.') }}
            </p>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Minhas contas</h3>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($contasAtivas as $conta)
                    @include('contas-bancarias.partials.linha-conta', ['conta' => $conta])
                @empty
                    <div class="px-6 py-10 text-center text-sm text-gray-500">
                        Nenhuma conta cadastrada. Clique em <strong>Adicionar conta</strong> para começar.
                    </div>
                @endforelse
            </div>
        </div>

        @if ($contasArquivadas->isNotEmpty())
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 opacity-80">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Arquivadas</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($contasArquivadas as $conta)
                        @include('contas-bancarias.partials.linha-conta', ['conta' => $conta])
                    @endforeach
                </div>
            </div>
        @endif

        <x-modal name="formulario-conta-bancaria" :show="$errors->any()" focusable>
            <form
                method="POST"
                class="p-6"
                x-bind:action="formulario.modo === 'editar' ? formulario.urlAtualizar : @js(route('contas-bancarias.criar'))"
            >
                @csrf
                <input type="hidden" name="_method" value="PUT" x-bind:disabled="formulario.modo !== 'editar'">

                <h2
                    class="text-lg font-semibold text-gray-900"
                    x-text="formulario.modo === 'editar' ? 'Editar conta' : 'Nova conta'"
                ></h2>
                <p class="mt-1 text-sm text-gray-500">Preencha os dados da conta manual.</p>

                <div class="mt-6 space-y-4">
                    <div>
                        <x-input-label for="nome" value="Nome" class="!text-gray-500 dark:!text-gray-500 font-medium" />
                        <x-text-input
                            id="nome"
                            name="nome"
                            type="text"
                            class="mt-1 block w-full bg-white text-gray-900 dark:bg-white dark:text-gray-900 dark:border-gray-300"
                            x-model="formulario.nome"
                            required
                        />
                        <x-input-error :messages="$errors->get('nome')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="saldo_inicial" value="Saldo inicial" class="!text-gray-500 dark:!text-gray-500 font-medium" />
                        <x-text-input
                            id="saldo_inicial"
                            name="saldo_inicial"
                            type="text"
                            class="mt-1 block w-full bg-white text-gray-900 dark:bg-white dark:text-gray-900 dark:border-gray-300"
                            x-model="formulario.saldo_inicial"
                            placeholder="0,00"
                            required
                        />
                        <x-input-error :messages="$errors->get('saldo_inicial')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tipo-conta-bancaria" value="Categoria" class="!text-gray-500 dark:!text-gray-500 font-medium" />
                        <select
                            id="tipo-conta-bancaria"
                            name="tipo"
                            class="mt-1 block w-full"
                            required
                        >
                            @foreach ($tipos as $opcaoTipo)
                                <option
                                    value="{{ $opcaoTipo['valor'] }}"
                                    @selected(old('tipo', $tipos[0]['valor'] ?? 'corrente') === $opcaoTipo['valor'])
                                >
                                    {{ $opcaoTipo['rotulo'] }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">
                        Cancelar
                    </x-secondary-button>
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 rounded-md bg-[#1fa67e] text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#198a68] transition"
                    >
                        Salvar
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
