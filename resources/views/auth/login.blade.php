<x-guest-layout>
    <div class="bg-white/90 backdrop-blur-xl shadow-2xl rounded-2xl w-full max-w-md p-8 border border-white/20">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#1fa67e] tracking-wide">
                NutriGrana
            </h1>
            <p class="text-gray-500 text-sm mt-2">
                Acesse sua conta e continue evoluindo
            </p>
        </div>

        <form id="form-login" method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-600">
                    Email
                </label>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       data-required
                       autofocus
                       autocomplete="username"
                       class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-600">
                    Senha
                </label>
                <input id="password"
                       type="password"
                       name="password"
                       data-required
                       autocomplete="current-password"
                       class="mt-1 w-full rounded-lg border-gray-200 focus:border-[#1fa67e] focus:ring-[#1fa67e] shadow-sm">
            </div>

            <div class="flex items-center justify-between">
                <label for="remember_me" class="flex items-center text-sm text-gray-600">
                    <input id="remember_me"
                           type="checkbox"
                           name="remember"
                           class="rounded border-gray-300 text-[#1fa67e] focus:ring-[#1fa67e] shadow-sm">
                    <span class="ml-2">
                        Me manter conectado
                    </span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-sm text-[#1fa67e] hover:underline">
                        Esqueceu?
                    </a>
                @endif
            </div>

            <button type="submit"
                    class="btn-auth-submit w-full bg-[#1fa67e] hover:bg-[#188f6b] text-white font-semibold py-2.5 rounded-lg shadow-lg transition duration-300">
                Entrar
            </button>

            <div class="text-center text-sm text-gray-500 mt-4">
                Ainda não tem conta?
                <a href="{{ route('register') }}"
                   class="text-[#1fa67e] font-semibold hover:underline">
                    Criar conta
                </a>
            </div>
        </form>
    </div>
</x-guest-layout>
