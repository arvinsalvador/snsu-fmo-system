<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SNSU FMO') }}</title>
    @unless (app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endunless
</head>
<body class="bg-zinc-50 text-zinc-950 antialiased">
    <div class="min-h-screen">
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('dashboard') }}" class="text-base font-semibold tracking-tight">SNSU FMO</a>
                <nav class="flex items-center gap-2 text-sm font-medium text-zinc-600">
                    <a href="{{ route('maintenance-schedules.index') }}" class="rounded-md px-3 py-2 hover:bg-zinc-100 hover:text-zinc-950">Maintenance</a>
                    <a href="{{ route('maintenance-schedules.create') }}" class="rounded-md bg-zinc-950 px-3 py-2 text-white hover:bg-zinc-800">New Schedule</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
