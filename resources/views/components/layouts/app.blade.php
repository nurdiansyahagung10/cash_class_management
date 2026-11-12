@props(['title'])
@php
    $user = auth()->user();
    $links = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'show' => true],
        [
            'route' => 'reports.payments',
            'label' => 'Laporan pembayaran',
            'icon' => 'billing',
            'show' => $user->canManageMembers(),
        ],
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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="bg-canvas font-sans text-black antialiased selection:bg-brand-soft selection:text-brand">
    @include('components.layouts.navbar')
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
    @include('components.layouts.backdrop')
</body>

</html>
<script nonce="{{ Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.querySelector('nav[aria-label="Navigasi utama"]');
        if (!sidebar) return;

        const backdrop = document.querySelector('[data-sidebar-backdrop]');
        const toggles = document.querySelectorAll('[data-sidebar-toggle]');
        const mobileMq = window.matchMedia('(max-width: 1023px)');
        const CLOSED = '-left-64!';
        let lastToggle = null; // tombol terakhir yang dipakai, buat balikin fokus

        const isOpen = () => !sidebar.classList.contains(CLOSED);

        function setOpen(open, {
            focus = false
        } = {}) {
            const modal = open && mobileMq.matches;

            // Keluarin fokus dulu sebelum sidebar jadi inert, biar nggak "hilang" ke <body>
            if (!open && sidebar.contains(document.activeElement)) {
                (lastToggle ?? toggles[0])?.focus({
                    preventScroll: true
                });
            }

            sidebar.classList.toggle(CLOSED, !open);
            sidebar.inert = !open;
            toggles.forEach(btn => btn.setAttribute('aria-expanded', String(open)));

            backdrop?.classList.toggle('opacity-0', !modal);
            backdrop?.classList.toggle('pointer-events-none', !modal);
            document.documentElement.classList.toggle('overflow-hidden', modal);

            // Dibuka di mobile: fokus masuk ke sidebar (keyboard / screen reader)
            if (modal && focus) {
                sidebar.querySelector('a[href], button:not([disabled])')?.focus({
                    preventScroll: true
                });
            }
        }

        function withoutTransition(fn) {
            sidebar.style.transition = 'none';
            fn();
            void sidebar.offsetWidth; // paksa reflow
            sidebar.style.transition = '';
        }

        toggles.forEach(btn => btn.addEventListener('click', () => {
            lastToggle = btn;
            setOpen(!isOpen(), {
                focus: true
            });
        }));

        backdrop?.addEventListener('click', () => setOpen(false));

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && mobileMq.matches && isOpen()) setOpen(false);
        });

        // Klik link di sidebar (mobile): tutup, jadi pas "back" nggak balik ke state kebuka + scroll terkunci
        sidebar.addEventListener('click', (e) => {
            if (mobileMq.matches && e.target.closest('a[href]')) setOpen(false);
        });

        // Lewat breakpoint: masuk mobile = tutup, masuk desktop = buka
        mobileMq.addEventListener('change', () => withoutTransition(() => setOpen(!mobileMq.matches)));

        // State awal tanpa animasi
        withoutTransition(() => setOpen(mobileMq.matches ? false : isOpen()));
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
