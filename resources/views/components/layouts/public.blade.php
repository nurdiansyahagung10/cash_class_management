@props(['title'])
@php
    $user = auth()->user();
    $links = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'show' => true],
        ['route' => 'expenses.index', 'group' => 'Keuangan', 'label' => 'Pengeluaran kas', 'icon' => 'expense', 'show' => true],
        ['route' => 'ledger', 'group' => 'Keuangan', 'label' => 'Ledger publik', 'icon' => 'ledger', 'show' => true],
    ];
@endphp

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <title>{{ $title }} · Kas Kelas 1A</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|geist-mono:400,500" rel="stylesheet">

    @include('components.layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-canvas font-sans text-ink antialiased">
    @include('components.layouts.navbar')

    <div class="flex min-w-0 flex-1 flex-col lg:h-dvh">
        @include('components.layouts.panel-topbar', ['title' => $title])
        {{-- Same viewport-height panel as the app layout: the frame clips, only the inner element scrolls. --}}
        <div class="flex min-h-0 min-w-0 flex-1 flex-col bg-panel lg:mb-2 lg:mr-2 lg:overflow-hidden lg:rounded-xl lg:border lg:border-line lg:shadow-xs lg:[contain:paint]">
            <div data-content-scroll class="flex min-h-0 flex-1 flex-col lg:overflow-y-auto lg:overscroll-contain lg:[scrollbar-width:thin] lg:[scrollbar-color:var(--color-line-strong)_transparent]">
                <main id="konten-utama" class="mx-auto flex w-full min-w-0 max-w-[1400px] flex-1 flex-col gap-6 px-4 pb-10 pt-4 sm:gap-8 sm:px-6 lg:px-10 lg:pt-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
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

