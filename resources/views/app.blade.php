<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" data-tema="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'NutriGrana') }}</title>
        <link rel="icon" href="{{ asset('images/logo-N.png') }}" type="image/png">

        <script>
            (function () {
                try {
                    var chave = 'nutrigrana.tema';
                    var salvo = localStorage.getItem(chave);
                    var tema = salvo === 'light' || salvo === 'dark' ? salvo : 'dark';
                    var escuro = tema === 'dark';
                    document.documentElement.classList.toggle('dark', escuro);
                    document.documentElement.style.colorScheme = escuro ? 'dark' : 'light';
                    document.documentElement.dataset.tema = tema;
                    if (!salvo) {
                        localStorage.setItem(chave, 'dark');
                    }
                } catch (e) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.dataset.tema = 'dark';
                }
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-ng-page text-ng-ink">
        @inertia
    </body>
</html>
