<x-guest-layout>
    <div class="bg-white/95 backdrop-blur-xl shadow-2xl rounded-2xl w-full max-w-md p-8 border border-white/20">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#1fa67e] tracking-wide">
                NutriGrana
            </h1>
            <p class="text-gray-500 text-sm mt-2">
                Recuperação de senha
            </p>
        </div>

        <p class="text-sm text-gray-600 mb-6 text-center">
            Informe seu e-mail cadastrado. Enviaremos um link para você redefinir sua senha.
        </p>

        <form id="form-recuperar-senha"
              method="POST"
              action="{{ route('password.email') }}"
              class="space-y-5"
              novalidate>
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-600">
                    E-mail
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

            <button type="submit"
                    class="btn-auth-submit w-full bg-[#1fa67e] hover:bg-[#188f6b] text-white font-semibold py-2.5 rounded-lg shadow-lg transition duration-300">
                Enviar link de redefinição
            </button>

            <div class="text-center text-sm text-gray-500 mt-4">
                Lembrou a senha?
                <a href="{{ route('login') }}"
                   class="text-[#1fa67e] font-semibold hover:underline">
                    Fazer login
                </a>
            </div>
        </form>
    </div>
</x-guest-layout>
