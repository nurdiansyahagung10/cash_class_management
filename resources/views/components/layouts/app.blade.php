@props(['title'])
@php
    $user = auth()->user();
    $links = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'show' => true],
        [
            'route' => 'reports.payments', 'group' => 'Keuangan',
            'label' => 'Laporan pembayaran',
            'icon' => 'billing',
            'show' => $user->canManageMembers(),
        ],
        [
            'route' => 'payments.history',
            'label' => 'Riwayat pembayaran',
            'icon' => 'review',
            'show' => ! $user->isAdmin(),
        ],
        ['route' => 'expenses.index', 'group' => 'Keuangan', 'label' => 'Pengeluaran kas', 'icon' => 'expense', 'show' => true],
        ['route' => 'ledger', 'group' => 'Keuangan', 'label' => 'Ledger publik', 'icon' => 'ledger', 'show' => true],
        [
            'route' => 'members.index', 'group' => 'Kelola',
            'label' => 'Kelola anggota',
            'icon' => 'members',
            'show' => $user->isAdmin(),
        ],
        [
            'route' => 'periods.index', 'group' => 'Kelola',
            'label' => 'Tagihan kelas',
            'icon' => 'billing',
            'show' => $user->canManageMembers(),
        ],
        [
            'route' => 'categories.index', 'group' => 'Kelola',
            'label' => 'Kategori tagihan',
            'icon' => 'category',
            'show' => $user->canManageMembers(),
        ],
        [
            'route' => 'payments.review', 'group' => 'Kelola',
            'label' => 'Review pembayaran',
            'icon' => 'review',
            'show' => $user->canManageMembers(),
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Kas Kelas 1A</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|geist-mono:400,500" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.default.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/filepond@4.32.7/dist/filepond.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.12/dist/filepond-plugin-image-preview.min.css">

    @include('components.layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="bg-canvas font-sans text-ink antialiased">
    @include('components.layouts.navbar')
    {{-- Main Content Column --}}
    <div class="flex min-w-0 flex-1 flex-col">
    @include('components.layouts.panel-topbar', ['title' => $title])
    <main id="konten-utama" tabindex="-1"
        class="mx-auto flex w-full min-w-0 max-w-[1400px] flex-1 flex-col gap-6 px-4 pb-10 pt-4 sm:gap-8 sm:px-6 lg:px-10 lg:pt-8 focus-visible:outline-none">
        @if (session('status'))
            <div role="status"
                class="flex items-center gap-3 rounded-xl border border-line bg-surface-2 px-4 py-3 text-sm font-medium text-success">
                <svg class="size-5 shrink-0 text-success" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div role="alert"
                class="flex items-center gap-3 rounded-xl border border-line bg-surface-2 px-4 py-3 text-sm font-medium text-danger">
                <svg class="size-5 shrink-0 text-danger" fill="none" viewBox="0 0 24 24" stroke-width="2"
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
    </div>
    @include('components.layouts.backdrop')

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-type@1.2.9/dist/filepond-plugin-file-validate-type.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-size@2.2.8/dist/filepond-plugin-file-validate-size.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-image-exif-orientation@1.0.11/dist/filepond-plugin-image-exif-orientation.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.12/dist/filepond-plugin-image-preview.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond@4.32.7/dist/filepond.min.js" defer></script>
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
</body>

</html>
