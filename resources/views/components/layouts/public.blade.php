@props(['title'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Kas Kelas 1A</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|source-serif-4:400,600" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas font-sans text-black antialiased selection:bg-brand-soft selection:text-brand">
<div class="mx-auto flex min-h-screen max-w-5xl flex-col gap-6 px-4 py-8 sm:py-12">
    <nav aria-label="Navigasi publik" class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
            <span class="flex size-10 items-center justify-center rounded-xl bg-navy text-sm font-bold text-white shadow-xs" aria-hidden="true">K</span>
            <span class="flex flex-col leading-tight">
                <span class="text-base font-bold tracking-tight text-black">Kas Kelas 1A</span>
                <span class="text-xs font-medium text-muted">D3 Bahasa Inggris</span>
            </span>
        </a>
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach (['dashboard' => 'Dashboard', 'expenses.index' => 'Pengeluaran', 'ledger' => 'Ledger'] as $routeName => $label)
                <a href="{{ route($routeName) }}" @if (request()->routeIs($routeName)) aria-current="page" @endif
                   class="inline-flex min-h-11 items-center rounded-xl px-3.5 text-sm font-semibold transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand {{ request()->routeIs($routeName) ? 'text-black' : 'text-muted' }}">
                    {{ $label }}
                </a>
            @endforeach
            <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-5 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Masuk
            </a>
        </div>
    </nav>

    <main class="flex flex-col gap-6">
        {{ $slot }}
    </main>
</div>
</body>
</html>
