@php
    /** @var \App\Models\ContasBancarias\ContaBancaria $conta */
    $temLancamentos = $conta->total_lancamentos > 0;
    $classeSaldo = (float) $conta->saldo_inicial >= 0 ? 'text-[#1fa67e]' : 'text-red-600';
@endphp

<div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/80 transition">
    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#e8f7f1] text-[#1fa67e]">
        <span class="material-symbols-outlined text-[22px]">
            {{ $conta->tipo->value === 'poupanca' ? 'savings' : 'account_balance' }}
        </span>
    </div>

    <div class="min-w-0 flex-1">
        <p class="truncate font-semibold text-gray-900">{{ $conta->nome }}</p>
        <p class="text-sm text-gray-500">{{ $conta->tipo->rotulo() }}</p>
    </div>

    <div class="text-right shrink-0">
        <p class="font-semibold {{ $classeSaldo }}">
            R$ {{ number_format((float) $conta->saldo_inicial, 2, ',', '.') }}
        </p>
    </div>

    <div class="flex items-center gap-1 shrink-0">
        <button
            type="button"
            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
            title="Editar"
            x-on:click="abrirEditar({
                nome: @js($conta->nome),
                saldo_inicial: @js(number_format((float) $conta->saldo_inicial, 2, ',', '.')),
                tipo: @js($conta->tipo->value),
                url_atualizar: @js(route('contas-bancarias.atualizar', $conta)),
            })"
        >
            <span class="material-symbols-outlined text-[20px]">edit</span>
        </button>

        <form method="POST" action="{{ route('contas-bancarias.atualizar', $conta) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="nome" value="{{ $conta->nome }}">
            <input type="hidden" name="saldo_inicial" value="{{ $conta->saldo_inicial }}">
            <input type="hidden" name="tipo" value="{{ $conta->tipo->value }}">
            <input type="hidden" name="arquivada" value="{{ $conta->arquivada ? 0 : 1 }}">
            <button
                type="submit"
                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                title="{{ $conta->arquivada ? 'Desarquivar' : 'Arquivar' }}"
            >
                <span class="material-symbols-outlined text-[20px]">
                    {{ $conta->arquivada ? 'unarchive' : 'archive' }}
                </span>
            </button>
        </form>

        @if ($temLancamentos)
            <button
                type="button"
                class="rounded-lg p-2 text-gray-300 cursor-not-allowed"
                title="Não é possível excluir: há lançamentos vinculados"
                disabled
            >
                <span class="material-symbols-outlined text-[20px]">delete</span>
            </button>
        @else
            <form
                method="POST"
                action="{{ route('contas-bancarias.excluir', $conta) }}"
                onsubmit="return confirm('Excluir esta conta bancária?')"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600"
                    title="Excluir"
                >
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                </button>
            </form>
        @endif
    </div>
</div>
