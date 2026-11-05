<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UI Kit · KSP Mulatama</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ksp-surface text-ksp-ink antialiased" style="font-family: Inter, ui-sans-serif, system-ui, sans-serif;">
<main class="mx-auto flex max-w-6xl flex-col gap-ksp-4xl px-4 py-10 sm:px-8">

    <header class="ksp-enter flex flex-col gap-ksp-md">
        <p class="text-ksp-eyebrow text-ksp-ink-muted">UI Kit · KSP Mulatama</p>
        <h1 class="text-4xl font-bold tracking-tight sm:text-6xl lg:text-ksp-page-title">Komponen</h1>
        <p class="max-w-2xl text-ksp-body text-ksp-ink-muted">Semua kelas memakai awalan <code class="font-mono">ksp-</code>, jadi tidak bentrok dengan utility Tailwind yang sudah dipakai di halaman lain.</p>
    </header>

    {{-- Tombol --}}
    <section aria-labelledby="judul-tombol" class="flex flex-col gap-ksp-xl">
        <h2 id="judul-tombol" class="text-ksp-section-title">Tombol</h2>
        <div class="ksp-panel">
            <div class="flex flex-wrap gap-ksp-4xl p-ksp-3xl">
                <div class="flex flex-col gap-ksp-lg">
                    <span class="text-ksp-group-label uppercase tracking-wider text-ksp-ink-muted">Primary · default / hover / disabled</span>
                    <div class="flex flex-wrap items-center gap-ksp-lg">
                        <a href="#judul-chip" class="ksp-btn ksp-btn-primary">Tambah anggota</a>
                        <button type="button" class="ksp-btn ksp-btn-primary" disabled>Tambah anggota</button>
                    </div>
                </div>
                <div class="flex flex-col gap-ksp-lg">
                    <span class="text-ksp-group-label uppercase tracking-wider text-ksp-ink-muted">Secondary</span>
                    <div class="flex flex-wrap items-center gap-ksp-lg">
                        <a href="#judul-tabel" class="ksp-btn ksp-btn-secondary">Ekspor</a>
                        <button type="button" class="ksp-btn ksp-btn-secondary" onclick="window.print()">Cetak PDF</button>
                    </div>
                </div>
                <div class="flex flex-col gap-ksp-lg">
                    <span class="text-ksp-group-label uppercase tracking-wider text-ksp-ink-muted">Icon 44×44</span>
                    <div class="flex flex-wrap items-center gap-ksp-lg">
                        <button type="button" class="ksp-btn-icon" aria-label="Pengaturan" onclick="document.getElementById('pesan')?.focus()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.14.37.43.67.8.85"/></svg>
                        </button>
                        <span class="ksp-avatar" aria-hidden="true">AN</span>
                    </div>
                </div>
            </div>
            <p class="px-ksp-3xl pb-ksp-3xl text-ksp-caption text-ksp-ink-muted">Satu tombol primer per header, paling kanan. Ikon wajib punya aria-label.</p>
        </div>
    </section>

    {{-- Chip --}}
    <section aria-labelledby="judul-chip" class="flex flex-col gap-ksp-xl">
        <h2 id="judul-chip" class="text-ksp-section-title">Chip</h2>
        <div class="ksp-panel">
            <div class="flex flex-wrap gap-ksp-4xl p-ksp-3xl">
                <div class="flex min-w-0 flex-col gap-ksp-lg">
                    <span class="text-ksp-group-label uppercase tracking-wider text-ksp-ink-muted">Status</span>
                    <div class="flex flex-wrap gap-ksp-md">
                        <span class="ksp-chip ksp-chip-success">Selesai</span>
                        <span class="ksp-chip ksp-chip-success">Lunas</span>
                        <span class="ksp-chip ksp-chip-warning">Menunggu Dropping</span>
                        <span class="ksp-chip ksp-chip-warning">Belum Setor Tunai</span>
                        <span class="ksp-chip ksp-chip-danger">Macet Belum Lunas</span>
                        <span class="ksp-chip ksp-chip-danger">Ditolak</span>
                    </div>
                </div>
                <div class="flex min-w-0 flex-col gap-ksp-lg">
                    <span class="text-ksp-group-label uppercase tracking-wider text-ksp-ink-muted">Tipe &amp; kode</span>
                    <div class="flex flex-wrap gap-ksp-md">
                        <span class="ksp-chip">Mingguan</span>
                        <span class="ksp-chip ksp-chip-info">Bulanan</span>
                        <span class="ksp-chip ksp-chip-code">W02-C03</span>
                    </div>
                </div>
            </div>
            <p class="px-ksp-3xl pb-ksp-3xl text-ksp-caption text-ksp-ink-muted">Hijau = selesai, kuning = menunggu/gejala, merah = macet/ditolak. Kata tetap ditulis lengkap.</p>
        </div>
    </section>

    {{-- Kartu statistik --}}
    <section aria-labelledby="judul-stat" class="flex flex-col gap-ksp-xl">
        <h2 id="judul-stat" class="text-ksp-section-title">Kartu statistik</h2>
        <div class="ksp-stagger grid grid-cols-1 gap-ksp-xl sm:grid-cols-2 lg:grid-cols-4">
            <div class="ksp-stat ksp-stat-mustard"><span class="ksp-stat-value">12.542</span><span class="ksp-stat-label">Total tagihan</span></div>
            <div class="ksp-stat ksp-stat-green"><span class="ksp-stat-value">26</span><span class="ksp-stat-label">Cabang</span></div>
            <div class="ksp-stat"><span class="ksp-stat-value">202</span><span class="ksp-stat-label">Total pengguna</span></div>
            <div class="ksp-stat ksp-stat-ink"><span class="ksp-stat-value">80</span><span class="ksp-stat-label">PDL terdaftar</span></div>
        </div>
    </section>

    {{-- Navigasi & input --}}
    <section aria-labelledby="judul-nav" class="flex flex-col gap-ksp-xl">
        <h2 id="judul-nav" class="text-ksp-section-title">Navigasi &amp; input</h2>
        <div class="ksp-panel">
            <div class="flex flex-wrap gap-ksp-4xl p-ksp-3xl">
                <div class="flex min-w-0 flex-col gap-ksp-lg">
                    <span class="text-ksp-group-label uppercase tracking-wider text-ksp-ink-muted">Page tabs</span>
                    <div class="ksp-tabs" role="tablist" aria-label="Halaman arus kas">
                        <button type="button" class="ksp-tab" role="tab" aria-selected="false">Setor Tunai</button>
                        <button type="button" class="ksp-tab" role="tab" aria-selected="false">Kasbon</button>
                        <button type="button" class="ksp-tab" role="tab" aria-selected="true">Storting</button>
                        <button type="button" class="ksp-tab" role="tab" aria-selected="false">Titipan</button>
                    </div>
                </div>
                <div class="flex min-w-0 flex-col gap-ksp-lg">
                    <span class="text-ksp-group-label uppercase tracking-wider text-ksp-ink-muted">Segmented</span>
                    <div class="ksp-segmented" role="tablist" aria-label="Saring status">
                        <button type="button" class="ksp-segment" role="tab" aria-selected="true">Semua</button>
                        <button type="button" class="ksp-segment" role="tab" aria-selected="false">Lancar</button>
                        <button type="button" class="ksp-segment" role="tab" aria-selected="false">Gejala</button>
                        <button type="button" class="ksp-segment" role="tab" aria-selected="false">Macet</button>
                    </div>
                </div>
                <div class="flex min-w-0 flex-col gap-ksp-lg">
                    <span class="text-ksp-group-label uppercase tracking-wider text-ksp-ink-muted">Filter &amp; cari</span>
                    <div class="flex flex-wrap gap-ksp-lg">
                        <label class="ksp-field">
                            <span class="sr-only">Cari nama anggota</span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input id="pesan" type="search" class="ksp-input" placeholder="Cari nama">
                        </label>
                    </div>
                </div>
            </div>
            <p class="px-ksp-3xl pb-ksp-3xl text-ksp-caption text-ksp-ink-muted">Fokus keyboard: cincin 2px green, jarak 2px, di semua kontrol. Coba tekan Tab.</p>
        </div>
    </section>

    {{-- Tabel & pagination --}}
    <section aria-labelledby="judul-tabel" class="flex flex-col gap-ksp-xl">
        <h2 id="judul-tabel" class="text-ksp-section-title">Tabel &amp; pagination</h2>
        <div class="ksp-panel">
            <div class="ksp-panel-head">
                <div class="flex flex-col gap-ksp-xs">
                    <span class="text-ksp-section-title">Tagihan anggota</span>
                    <span class="text-ksp-caption text-ksp-ink-muted">Panel daftar: judul, kontrol, tabel, footer.</span>
                </div>
            </div>
            <div class="ksp-scroll">
                <table class="ksp-table">
                    <caption class="sr-only">Daftar tagihan anggota</caption>
                    <thead><tr><th scope="col">Status</th><th scope="col">Anggota</th><th scope="col" class="ksp-num">Tagihan</th><th scope="col" class="ksp-num">Sisa</th></tr></thead>
                    <tbody class="ksp-stagger-rows">
                        <tr><td><span class="ksp-chip ksp-chip-warning">Gejala Macet Belum Lunas</span></td><td><span class="block font-semibold">Ulfa Fitriyah</span><span class="text-ksp-caption text-ksp-ink-muted">No. 00000029</span></td><td class="ksp-num">Rp 1.100.000</td><td class="ksp-num font-semibold">Rp 290.000</td></tr>
                        <tr><td><span class="ksp-chip ksp-chip-danger">Macet Belum Lunas</span></td><td><span class="block font-semibold">Tina Agustina</span><span class="text-ksp-caption text-ksp-ink-muted">No. 00000091</span></td><td class="ksp-num">Rp 8.800.000</td><td class="ksp-num font-semibold">Rp 5.680.000</td></tr>
                        <tr><td><span class="ksp-chip ksp-chip-success">Lunas</span></td><td><span class="block font-semibold">Rismawati</span><span class="text-ksp-caption text-ksp-ink-muted">No. 00000047</span></td><td class="ksp-num">Rp 1.650.000</td><td class="ksp-num font-semibold">Rp 0</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="ksp-panel-foot">
                <span>Menampilkan 1&ndash;3 dari 12.542 data</span>
                <nav class="flex items-center gap-ksp-md" aria-label="Pagination">
                    <button type="button" class="ksp-btn-icon" aria-label="Halaman sebelumnya" disabled>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <span class="ksp-page-dot" aria-current="page">1</span>
                    <span>dari 1.255</span>
                    <a href="#judul-sidebar" class="ksp-btn-icon" aria-label="Halaman berikutnya">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </nav>
            </div>
        </div>
    </section>

    {{-- Sidebar --}}
    <section aria-labelledby="judul-sidebar" class="flex flex-col gap-ksp-xl">
        <h2 id="judul-sidebar" class="text-ksp-section-title">Sidebar <span class="text-ksp-caption font-normal text-ksp-ink-muted">floating · 272px</span></h2>
        <div class="ksp-panel">
            <div class="flex flex-wrap items-start gap-ksp-4xl p-ksp-3xl">
                <nav class="ksp-rail" aria-label="Navigasi contoh">
                    <div class="flex items-center gap-ksp-lg px-ksp-lg py-ksp-lg">
                        <span class="flex size-9 items-center justify-center rounded-ksp-sm bg-ksp-rail-active-ink font-bold text-ksp-mustard-ink" aria-hidden="true">K</span>
                        <span class="font-semibold text-ksp-on-dark">KSP Mulatama</span>
                    </div>
                    <span class="ksp-rail-group">Main</span>
                    <a href="#judul-tombol" class="ksp-rail-item">Dashboard</a>
                    <a href="#judul-chip" class="ksp-rail-item">Anggota</a>
                    <span class="ksp-rail-group">Manajemen arus kas</span>
                    <button type="button" class="ksp-rail-item justify-between" aria-expanded="true" aria-controls="submenu-mingguan">
                        <span>Mingguan</span>
                        <span class="flex items-center gap-ksp-md text-ksp-caption">5
                            <svg class="ksp-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </span>
                    </button>
                    <div class="ksp-rail-sub" id="submenu-mingguan">
                        <a href="#judul-tabel" class="ksp-rail-subitem">Setor Tunai</a>
                        <a href="#judul-tabel" class="ksp-rail-subitem">Kasbon</a>
                        <a href="#judul-tabel" class="ksp-rail-subitem" aria-current="page">Storting</a>
                        <a href="#judul-tabel" class="ksp-rail-subitem">Titipan</a>
                    </div>
                </nav>
                <ul class="flex min-w-60 flex-1 flex-col gap-ksp-xl text-ksp-body">
                    <li><strong class="block">Default</strong><span class="text-ksp-ink-muted">Teks rail-ink, ikon 22px stroke 1.6.</span></li>
                    <li><strong class="block">Hover</strong><span class="text-ksp-ink-muted">Latar rail-hover, teks putih, 150ms.</span></li>
                    <li><strong class="block">Aktif</strong><span class="text-ksp-ink-muted">rail-active + rail-active-ink. Grupnya otomatis terbuka.</span></li>
                    <li><strong class="block">Submenu aktif</strong><span class="text-ksp-ink-muted">Pil 38px di dalam garis submenu.</span></li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Kosong & error --}}
    <section aria-labelledby="judul-kosong" class="flex flex-col gap-ksp-xl">
        <h2 id="judul-kosong" class="text-ksp-section-title">Kosong &amp; error</h2>
        <div class="grid grid-cols-1 gap-ksp-xl md:grid-cols-2">
            <div class="ksp-empty">
                <span class="ksp-well" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 7h16M4 12h10M4 17h6"/></svg>
                </span>
                <strong class="text-ksp-body-strong">Belum ada bon panjer</strong>
                <span class="text-ksp-body text-ksp-ink-muted">Bon panjer yang dibuat pegawai akan tampil di sini.</span>
            </div>
            <div class="ksp-error">
                <span class="ksp-well ksp-well-danger" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                </span>
                <strong class="text-ksp-body-strong">Pengaturan umum belum bisa dibuka</strong>
                <span class="text-ksp-body text-ksp-ink-muted">Data lain tetap aman. Coba lagi atau kembali ke dashboard.</span>
                <pre class="ksp-code"><code>ViewException: Attempt to read property "url" on null</code></pre>
            </div>
        </div>
    </section>

</main>

<script>
    // Tab dan segmented: satu yang aktif per grup.
    document.querySelectorAll('[role="tablist"]').forEach((list) => {
        list.addEventListener('click', (event) => {
            const tab = event.target.closest('[role="tab"]');
            if (!tab || !list.contains(tab)) return;
            list.querySelectorAll('[role="tab"]').forEach((t) => t.setAttribute('aria-selected', String(t === tab)));
        });
    });

    // Grup sidebar buka/tutup.
    document.querySelectorAll('.ksp-rail-item[aria-controls]').forEach((button) => {
        button.addEventListener('click', () => {
            const open = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', String(!open));
            const panel = document.getElementById(button.getAttribute('aria-controls'));
            if (panel) panel.hidden = open;
        });
    });
</script>
</body>
</html>
