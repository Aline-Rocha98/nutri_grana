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
    </head>
    <body class="font-sans antialiased bg-[#0c2e24]">
        <div class="flex min-h-screen gap-3 p-3">
            <x-barra-lateral />

            <div class="flex min-w-0 flex-1 flex-col">
                @isset($header)
                    <header class="mb-3 rounded-2xl bg-white/95 px-6 py-4 shadow-sm border border-white/20">
                        {{ $header }}
                    </header>
                @endisset

                <main class="flex-1 rounded-[2rem] bg-gray-100 shadow-inner overflow-auto">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
