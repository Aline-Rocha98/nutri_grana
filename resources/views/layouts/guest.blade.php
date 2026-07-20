<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NutriGrana') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            window.authRoutes = {
                login: @json(route('login')),
                register: @json(route('register')),
                recover: @json(route('password.request')),
                home: @json(route('home')),
            };
            window.authMessages = {
                emailJaCadastrado: @json(__('validation.usuario.email.unique')),
                linkRecuperacaoEnviado: @json(__('passwords.sent')),
            };
        </script>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#0c2e24] to-[#1fa67e] px-4">
            <div class="w-full flex justify-center">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
