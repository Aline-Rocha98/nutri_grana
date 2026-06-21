<x-guest-layout>
    <div class="bg-white/95 backdrop-blur-xl shadow-2xl rounded-2xl w-full max-w-lg p-8 border border-white/20">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#1fa67e]">
                NutriGrana
            </h1>
            <p class="text-gray-500 text-sm mt-2">
                Crie sua conta e organize suas finanças
            </p>
        </div>

        <form id="form-register" method="POST" action="{{ route('register') }}" class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="nome" class="block text-sm font-medium text-gray-600">
                    Nome
                </label>
                <input id="nome"
                       type="text"
                       name="nome"
                       value="{{ old('nome') }}"
                       data-required
                       autofocus
                       autocomplete="name"
                       class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-600">
                    Email
                </label>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       data-required
                       autocomplete="username"
                       class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm">
            </div>

            <div>
                <label for="data_nascimento" class="block text-sm font-medium text-gray-600">
                    Data de nascimento
                </label>
                <input id="data_nascimento"
                       type="date"
                       name="data_nascimento"
                       value="{{ old('data_nascimento') }}"
                       data-required
                       class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm">
            </div>

            <div>
                <label for="motivo_controle_financeiro" class="block text-sm font-medium text-gray-600">
                    Por que deseja controlar suas finanças?
                </label>
                <select id="motivo_controle_financeiro"
                        name="motivo_controle_financeiro"
                        data-required
                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm">
                    <option value="">Selecione uma opção</option>
                    @foreach ($motivos as $motivo)
                        <option value="{{ $motivo['value'] }}"
                                @selected(old('motivo_controle_financeiro') === $motivo['value'])>
                            {{ $motivo['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-600">
                    Senha
                </label>
                <input id="password"
                       type="password"
                       name="password"
                       data-required
                       autocomplete="new-password"
                       class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-600">
                    Confirmar senha
                </label>
                <input id="password_confirmation"
                       type="password"
                       name="password_confirmation"
                       data-required
                       autocomplete="new-password"
                       class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm">
            </div>

            <button type="submit"
                    class="btn-auth-submit w-full bg-[#1fa67e] hover:bg-[#188f6b] text-white font-semibold py-2.5 rounded-lg shadow-lg transition duration-300">
                Criar conta
            </button>

            <div class="text-center text-sm text-gray-500 mt-4">
                Já tem conta?
                <a href="{{ route('login') }}"
                   class="text-[#1fa67e] font-semibold hover:underline">
                    Entrar
                </a>
            </div>
        </form>
    </div>
</x-guest-layout>
