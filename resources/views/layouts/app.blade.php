<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'SNSU FMO') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="overflow-x-hidden font-sans antialiased text-gray-900">
        <div class="min-h-screen bg-gray-50" x-data="{ sidebarOpen: false }">
            @include('layouts.navigation')
            <div class="md:pl-64">
                <header class="border-b border-gray-200 bg-white">
                    <div class="mx-auto flex min-h-20 max-w-screen-2xl items-center px-4 py-4 sm:px-6 lg:px-8">
                        <button type="button" @click="sidebarOpen = true" class="mr-3 rounded-md border border-gray-300 p-2 text-gray-600 md:hidden" aria-label="Open navigation">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                        @isset($header){{ $header }}@endisset
                    </div>
                </header>
                <main class="mx-auto max-w-screen-2xl px-4 py-7 sm:px-6 lg:px-8">
                    @if (session('status'))
                        <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Please review the highlighted fields and try again.</div>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>