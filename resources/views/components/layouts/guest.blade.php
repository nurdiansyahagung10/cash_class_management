@props(['title', 'lead'])
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
<main class="mx-auto flex min-h-screen max-w-md flex-col justify-center gap-6 px-4 py-12">
    <div class="flex items-center gap-3">
        <div class="flex size-10 items-center justify-center rounded-xl bg-navy text-sm font-bold text-white shadow-xs" aria-hidden="true">
            K
        </div>
        <div class="flex flex-col leading-tight">
            <span class="text-base font-bold tracking-tight text-black">Kas Kelas 1A</span>
            <span class="text-xs font-medium text-muted">D3 Bahasa Inggris</span>
        </div>
    </div>

    <header class="flex flex-col gap-2">
        <h1 class="text-3xl font-bold tracking-tight text-black">{{ $title }}</h1>
        <p class="font-serif text-base leading-relaxed text-muted">{{ $lead }}</p>
    </header>

    @if (session('status'))
        <div role="status" class="flex items-center gap-2.5 rounded-2xl border border-[#dcfce7] bg-[#f1fbf4] px-4 py-3 text-sm font-medium text-[#14532d] shadow-xs">
            <svg class="size-4 shrink-0 text-[#14532d]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <div class="rounded-2xl border border-line bg-white p-6 shadow-xs sm:p-7">
        {{ $slot }}
    </div>
</main>
</body>
</html>
