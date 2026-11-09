@props(['title'])
@php
    $user = auth()->user();
    $links = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'show' => true],
        ['route' => 'reports.payments', 'label' => 'Laporan pembayaran', 'icon' => 'billing', 'show' => true],
        ['route' => 'expenses.index', 'label' => 'Pengeluaran kas', 'icon' => 'expense', 'show' => true],
        ['route' => 'ledger', 'label' => 'Ledger publik', 'icon' => 'ledger', 'show' => true],
        [
            'route' => 'members.index',
            'label' => 'Kelola anggota',
            'icon' => 'members',
            'show' => $user->canManageMembers(),
        ],
        [
            'route' => 'periods.index',
            'label' => 'Tagihan kelas',
            'icon' => 'billing',
            'show' => $user->canManageMembers(),
        ],
        [
            'route' => 'categories.index',
            'label' => 'Kategori tagihan',
            'icon' => 'category',
            'show' => $user->canManageMembers(),
        ],
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
    <header data-topnav class="fixed md:hidden inset-x-4 top-4 z-10">
        <div
            class="flex h-14 items-center justify-between rounded-2xl bg-white/90 px-4 shadow-lg ring-1 ring-black/5 backdrop-blur">
            <a href="/" class="font-semibold">KSP Mulatama</a>

            {{-- Tombol hamburger (mobile) --}}
            <button type="button" data-sidebar-toggle aria-controls="topnav-mobile" aria-expanded="false"
                aria-label="Buka menu"
                class="group relative grid size-10 place-items-center rounded-xl hover:bg-gray-100 lg:hidden">
                <span
                    class="absolute h-0.5 w-5 -translate-y-1.5 rounded bg-current transition duration-300
                         "></span>
                <span
                    class="absolute h-0.5 w-5 rounded bg-current transition duration-200
                         "></span>
                <span
                    class="absolute h-0.5 w-5 translate-y-1.5 rounded bg-current transition duration-300
                         "></span>
            </button>
        </div>
    </header>


    <div class="flex min-h-screen md:mt-0 mt-20  flex-row">
        <a href="#konten-utama"
            class="sr-only rounded-lg bg-white px-3 py-2 text-sm font-medium text-brand focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand shadow-md">Lewati
            ke konten utama</a>

        {{-- Sidebar Navigation (Desktop & Mobile) --}}

        <nav aria-label="Navigasi utama"
            class=" border-line bg-canvas/80 backdrop-blur-md h-screen md:h-screen md:sticky fixed top-0 left-0   w-64 overflow-y-auto border-r border-b-0 z-999 overflow-hidden transition-[left] duration-300 ease-in-out ">
            <div class="p-5 flex shrink-0 flex-col gap-1 h-screen">
                {{-- Brand Header --}}
                <div class="flex items-center justify-between px-2 py-2 pb-6">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-navy text-sm font-bold text-white shadow-xs"
                            aria-hidden="true">
                            K
                        </div>
                        <div class="flex flex-col leading-tight">
                            <span class="text-sm font-bold tracking-tight text-black">Kas Kelas 1A</span>
                            <span class="text-[11px] font-medium text-muted">D3 Bahasa Inggris</span>
                        </div>
                    </div>
                    <span
                        class="  rounded-full border border-black/10 bg-white px-2 py-0.5 text-[10px] font-semibold text-muted md:inline-flex hidden">
                        v1.0
                    </span>
                    <button type="button" aria-label="Tutup menu" data-sidebar-toggle
                        class="group -mr-2.5 inline-flex h-9 w-9 items-center justify-center rounded-lg text-muted transition-colors hover:bg-black/5 hover:text-black focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand md:hidden">
                        <svg class="size-4 transition-colors group-hover:text-black" fill="none" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Nav Links --}}
                <div class=" grid-cols-2 sm:grid-cols-3 flex flex-col gap-1">
                    @foreach ($links as $link)
                        @if ($link['show'])
                            @php $isActive = request()->routeIs($link['route']); @endphp
                            <a href="{{ route($link['route']) }}"
                                @if ($isActive) aria-current="page" @endif
                                class="group flex min-h-11 min-w-0 items-center gap-2.5 rounded-xl px-3 py-2 text-xs sm:text-[13px] font-medium transition-all duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand {{ $isActive ? 'bg-white text-brand shadow-xs border border-line font-semibold' : 'text-muted hover:bg-white/60 hover:text-black border border-transparent' }}">
                                @switch($link['icon'])
                                    @case('home')
                                        <x-icons.home
                                            class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" />
                                    @break

                                    @case('expense')
                                        <x-icons.expense
                                            class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" />
                                    @break

                                    @case('ledger')
                                        <x-icons.ledger
                                            class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" />
                                    @break

                                    @case('members')
                                        <x-icons.members
                                            class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" />
                                    @break

                                    @case('billing')
                                        <x-icons.billing
                                            class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" />
                                    @break

                                    @case('category')
                                        <x-icons.category
                                            class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" />
                                    @break

                                    @case('review')
                                        <x-icons.review
                                            class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-brand' : 'text-muted group-hover:text-black' }}" />
                                    @break
                                @endswitch
                                <span class="truncate">{{ $link['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>

                {{-- User Profile Footer --}}
                <div class=" flex flex-col gap-2.5 rounded-2xl border border-line bg-white p-3.5 shadow-xs mt-auto">
                    <div class="flex items-center gap-2.5">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-xs font-bold text-brand border border-brand/20"
                            aria-hidden="true">
                            {{ \Illuminate\Support\Str::of($user->name)->explode(' ')->take(2)->map(fn($w) => mb_substr($w, 0, 1))->implode('') }}
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate text-[13px] font-semibold text-black">{{ $user->name }}</span>
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="inline-block size-1.5 rounded-full {{ $user->isAdmin() ? 'bg-navy' : ($user->canManageMembers() ? 'bg-amber' : 'bg-[#14532d]') }}"></span>
                                <span
                                    class="text-[11px] font-medium text-muted capitalize">{{ $user->role->value }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-black/5 pt-2.5 text-xs">
                        <a href="{{ route('password.edit') }}"
                            class="font-medium text-muted transition-colors hover:text-black focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Ganti
                            password</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="font-semibold text-brand transition-colors hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        {{-- Main Content Column --}}
        <main id="konten-utama" tabindex="-1"
            class="flex min-w-0 flex-1 flex-col gap-8 px-4 py-6 sm:px-8 sm:py-8 lg:px-14 lg:py-10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
            @if (session('status'))
                <div role="status"
                    class="flex items-center gap-3 rounded-2xl border border-[#dcfce7] bg-[#f1fbf4] px-4 py-3.5 text-sm font-medium text-[#14532d] shadow-xs">
                    <svg class="size-5 shrink-0 text-[#14532d]" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div role="alert"
                    class="flex items-center gap-3 rounded-2xl border border-[#fde4e0] bg-[#fff5f5] px-4 py-3.5 text-sm font-medium text-[#8f1d0e] shadow-xs">
                    <svg class="size-5 shrink-0 text-[#8f1d0e]" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
    <div data-sidebar-backdrop
        class="fixed inset-0 z-30 bg-black/40 backdrop-blur-[2px]
            opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden">
    </div>
</body>

</html>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.querySelector('nav[aria-label="Navigasi utama"]');
        const backdrop = document.querySelector('[data-sidebar-backdrop]');
        const toggles = document.querySelectorAll('[data-sidebar-toggle]');
        const mobileMq = window.matchMedia('(max-width: 1023px)');

        if (!sidebar) return;

        const isOpen = () => !sidebar.classList.contains('-left-64!');

        function setOpen(open) {
            sidebar.classList.toggle('-left-64!', !open);
            sidebar.inert = !open; // link di sidebar tertutup nggak bisa di-Tab
            toggles.forEach(btn => btn.setAttribute('aria-expanded', String(open)));

            const showBackdrop = open && mobileMq.matches;
            backdrop?.classList.toggle('opacity-0', !showBackdrop);
            backdrop?.classList.toggle('pointer-events-none', !showBackdrop);
            document.body.classList.toggle('overflow-hidden', showBackdrop); // kunci scroll di mobile
        }

        toggles.forEach(btn => btn.addEventListener('click', () => setOpen(!isOpen())));
        backdrop?.addEventListener('click', () => setOpen(false));

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && isOpen() && mobileMq.matches) setOpen(false);
        });

        // Resize desktop <-> mobile: sinkronkan backdrop & scroll lock
        mobileMq.addEventListener('change', () => setOpen(isOpen()));

        // Di mobile mulai tertutup, tanpa animasi "nutup" pas halaman load
        if (mobileMq.matches) {
            sidebar.style.transition = 'none';
            setOpen(false);
            void sidebar.offsetWidth; // paksa reflow
            sidebar.style.transition = '';
        } else {
            setOpen(isOpen());
        }
    });
</script>
