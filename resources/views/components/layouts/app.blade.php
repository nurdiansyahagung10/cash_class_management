@props(['title'])
@php
    $user = auth()->user();
    $links = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'show' => true],
        ['route' => 'expenses.index', 'label' => 'Pengeluaran kas', 'icon' => 'expense', 'show' => true],
        ['route' => 'ledger', 'label' => 'Ledger publik', 'icon' => 'ledger', 'show' => true],
        ['route' => 'members.index', 'label' => 'Kelola anggota', 'icon' => 'members', 'show' => $user->canManageMembers()],
        ['route' => 'periods.index', 'label' => 'Tagihan kelas', 'icon' => 'billing', 'show' => $user->canManageMembers()],
        ['route' => 'categories.index', 'label' => 'Kategori tagihan', 'icon' => 'category', 'show' => $user->canManageMembers()],
        ['route' => 'payments.review', 'label' => 'Review pembayaran', 'icon' => 'review', 'show' => $user->isAdmin()],
    ];
@endphp
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
<div class="flex min-h-screen flex-col md:flex-row">
    <a href="#konten-utama" class="sr-only rounded-lg bg-white px-3 py-2 text-sm font-medium text-brand focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand shadow-md">Lewati ke konten utama</a>

    {{-- Sidebar Navigation (Desktop & Mobile) --}}
    <nav aria-label="Navigasi utama" class="flex shrink-0 flex-col gap-1 border-b border-line bg-canvas/80 backdrop-blur-md p-3 md:sticky md:top-0 md:h-screen md:w-64 md:overflow-y-auto md:border-r md:border-b-0 md:p-5">
        {{-- Brand Header --}}
        <div class="flex items-center justify-between px-2 py-2 md:pb-6">
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-xl bg-navy text-sm font-bold text-white shadow-xs" aria-hidden="true">
                    K
                </div>
                <div class="flex flex-col leading-tight">
                    <span class="text-sm font-bold tracking-tight text-black">Kas Kelas 1A</span>
                    <span class="text-[11px] font-medium text-muted">D3 Bahasa Inggris</span>
                </div>
            </div>
            <span class="hidden rounded-full border border-black/10 bg-white px-2 py-0.5 text-[10px] font-semibold text-muted md:inline-flex">
                v1.0
            </span>
        </div>

        {{-- Nav Links --}}
        <div class="grid grid-cols-2 gap-1 sm:grid-cols-3 md:flex md:flex-col md:gap-1">
            @foreach ($links as $link)
                @if ($link['show'])
                    @php $isActive = request()->routeIs($link['route']); @endphp
                    <a href="{{ route($link['route']) }}"
                       @if ($isActive) aria-current="page" @endif
                       class="group flex min-h-11 min-w-0 items-center gap-2.5 rounded-xl px-3 py-2 text-xs sm:text-[13px] font-medium transition-all duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand {{ $isActive ? 'bg-white text-brand shadow-xs border border-line font-semibold' : 'text-muted hover:bg-white/60 hover:text-black border border-transparent' }}">
                        @switch($link['icon'])
                            @case('home') <x-icons.home class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" /> @break
                            @case('expense') <x-icons.expense class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" /> @break
                            @case('ledger') <x-icons.ledger class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" /> @break
                            @case('members') <x-icons.members class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" /> @break
                            @case('billing') <x-icons.billing class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" /> @break
                            @case('category') <x-icons.category class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" /> @break
                            @case('review') <x-icons.review class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" /> @break
                        @endswitch
                        <span class="truncate">{{ $link['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>

        {{-- User Profile Footer --}}
        <div class="mt-3 flex flex-col gap-2.5 rounded-2xl border border-line bg-white p-3.5 shadow-xs md:mt-auto">
            <div class="flex items-center gap-2.5">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-xs font-bold text-brand border border-brand/20" aria-hidden="true">
                    {{ \Illuminate\Support\Str::of($user->name)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}
                </div>
                <div class="flex min-w-0 flex-1 flex-col">
                    <span class="truncate text-[13px] font-semibold text-black">{{ $user->name }}</span>
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block size-1.5 rounded-full {{ $user->isAdmin() ? 'bg-navy' : ($user->canManageMembers() ? 'bg-amber' : 'bg-[#14532d]') }}"></span>
                        <span class="text-[11px] font-medium text-muted capitalize">{{ $user->role->value }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between border-t border-black/5 pt-2.5 text-xs">
                <a href="{{ route('password.edit') }}" class="font-medium text-muted transition-colors hover:text-black focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Ganti password</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="font-semibold text-brand transition-colors hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Keluar</button>
                </form>
            </div>
        </div>
    </nav>

    {{-- Main Content Column --}}
    <main id="konten-utama" tabindex="-1" class="flex min-w-0 flex-1 flex-col gap-8 px-4 py-6 sm:px-8 sm:py-8 lg:px-14 lg:py-10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
        @if (session('status'))
            <div role="status" class="flex items-center gap-3 rounded-2xl border border-[#dcfce7] bg-[#f1fbf4] px-4 py-3.5 text-sm font-medium text-[#14532d] shadow-xs">
                <svg class="size-5 shrink-0 text-[#14532d]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="flex items-center gap-3 rounded-2xl border border-[#fde4e0] bg-[#fff5f5] px-4 py-3.5 text-sm font-medium text-[#8f1d0e] shadow-xs">
                <svg class="size-5 shrink-0 text-[#8f1d0e]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{ $slot }}
    </main>
</div>
</body>
</html>
