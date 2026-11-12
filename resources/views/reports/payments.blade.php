<x-layouts.app title="Laporan pembayaran uang kas">
    {{-- Header & Kop Laporan Resmi Kas Kelas Sekolah --}}
    <header class="flex flex-col gap-4 border-b border-line pb-6">
        <div class="flex items-center gap-2 text-xs font-semibold text-muted">
            <span class="hover:text-black transition-colors">KK 1A</span>
            <span class="text-black/20">/</span>
            <span class="text-black">Pembayaran</span>
            <span class="text-black/20">·</span>
            <span class="font-normal">{{ now()->translatedFormat('l, d F Y') }}</span>
        </div>

        {{-- Bar Tombol Aksi & Ekspor Dokumen --}}
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 print:hidden">
            {{-- Tab Navigasi Format Laporan Kas Sekolah --}}
            <nav aria-label="Format Laporan"
                class="flex flex-wrap items-center gap-1.5 rounded-2xl border border-line bg-black/3 p-1.5 shadow-2xs">
                @php
                    $tabQuery = request()->except('tab');
                @endphp
                <a href="{{ route('reports.payments', array_merge($tabQuery, ['tab' => 'detail'])) }}"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold transition-all md:w-auto w-full {{ $activeTab === 'detail' ? 'bg-white text-brand shadow-xs border border-line font-bold' : 'text-muted hover:text-black hover:bg-white/60' }}">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    Rincian Tagihan & Cicilan
                </a>
                <a href="{{ route('reports.payments', array_merge($tabQuery, ['tab' => 'matrix'])) }}"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold md:w-auto w-full transition-all {{ $activeTab === 'matrix' ? 'bg-white text-brand shadow-xs border border-line font-bold' : 'text-muted hover:text-black hover:bg-white/60' }}">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Matriks Iuran Siswa (Buku Pembantu)
                </a>
                <a href="{{ route('reports.payments', array_merge($tabQuery, ['tab' => 'bku'])) }}"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold lg:w-auto w-full transition-all {{ $activeTab === 'bku' ? 'bg-white text-brand shadow-xs border border-line font-bold' : 'text-muted hover:text-black hover:bg-white/60' }}">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Buku Kas Umum (BKU Kelas)
                </a>
            </nav>

            {{-- Dropdown / Tombol Unduh & Cetak --}}
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('reports.payments.export', request()->query()) }}"
                    class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black shadow-xs hover:bg-black/5 transition-colors">
                    <svg class="size-3.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV Rincian
                </a>
                <a href="{{ route('reports.payments.export-matrix') }}"
                    class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black shadow-xs hover:bg-black/5 transition-colors">
                    <svg class="size-3.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV Matriks Iuran
                </a>
                <a href="{{ route('reports.payments.export-bku') }}"
                    class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black shadow-xs hover:bg-black/5 transition-colors">
                    <svg class="size-3.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV BKU
                </a>
                <button type="button" onclick="window.print()"
                    class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-navy px-4 text-xs font-semibold text-white shadow-xs hover:bg-black transition-colors">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Laporan
                </button>
            </div>
        </div>
    </header>


    {{-- Ringkasan Statistik Finansial Kas Kelas --}}
    <section aria-label="Ringkasan laporan pembayaran" class="relative min-w-0">

        @php
            $stats = [
                [
                    'Saldo Kas di Tangan',
                    'Rp' . number_format($realCashBalance, 0, ',', '.'),
                    'Kas masuk bersih setelah pengeluaran',
                ],
                [
                    'Total Iuran Masuk',
                    'Rp' . number_format($totalCollected, 0, ',', '.'),
                    $collectionRate . '% dari target Rp' . number_format($totalExpected, 0, ',', '.'),
                ],
                [
                    'Pengeluaran Kas',
                    'Rp' . number_format($totalExpense, 0, ',', '.'),
                    'Belanja keperluan kelas terverifikasi',
                ],
                [
                    'Sisa Piutang / Tunggakan',
                    'Rp' . number_format($totalRemaining, 0, ',', '.'),
                    $unpaidCount . ' belum bayar · ' . $partialCount . ' menyicil',
                ],
                [
                    'Lewat Jatuh Tempo',
                    $overdueCount . ' tagihan',
                    'Tertunggak Rp' . number_format($overdueRemaining, 0, ',', '.') . ' (Cicilan dikunci)',
                ],
            ];
        @endphp

        {{-- Carousel Header --}}
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold tracking-tight text-black">
                    Ringkasan Finansial
                </h2>
                <p class="mt-1 text-xs text-muted">
                    Ikhtisar kondisi keuangan kas kelas
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button"
                    class="financial-prev flex h-8 w-8 items-center justify-center rounded-full border border-line bg-white text-black transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="Statistik sebelumnya">
                    <span aria-hidden="true">&larr;</span>
                </button>

                <button type="button"
                    class="financial-next flex h-8 w-8 items-center justify-center rounded-full border border-line bg-white text-black transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="Statistik berikutnya">
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </div>

        {{-- Swiper --}}
        <div class="swiper financial-swiper min-w-0">
            <div class="swiper-wrapper">

                @foreach ($stats as $index => [$label, $value, $detail])
                    <div class="swiper-slide !h-auto">
                        <article
                            class="flex h-full min-h-32 flex-col justify-between rounded-2xl border p-5 shadow-xs transition-all hover:shadow-sm
                        {{ $index === 4 && $overdueCount > 0
                            ? 'border-[#fecaca] bg-[#fff5f5]'
                            : 'border-line bg-white hover:border-black/20' }}">

                            <h3
                                class="text-[11px] font-semibold uppercase tracking-wider
                            {{ $index === 4 && $overdueCount > 0 ? 'text-[#8f1d0e]' : 'text-muted' }}">
                                {{ $label }}
                            </h3>

                            <p
                                class="my-2 break-words text-2xl font-bold tracking-tight tabular-nums sm:text-3xl
                            {{ $index === 4 && $overdueCount > 0 ? 'text-[#8f1d0e]' : 'text-black' }}">
                                {{ $value }}
                            </p>

                            <p
                                class="text-[11px]
                            {{ $index === 4 && $overdueCount > 0 ? 'text-[#8f1d0e]' : 'text-muted' }}">
                                {{ $detail }}
                            </p>

                        </article>
                    </div>
                @endforeach

            </div>
        </div>

    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const element = document.querySelector('.financial-swiper');

            if (!element || typeof Swiper === 'undefined') return;

            new Swiper(element, {
                slidesPerView: 1.15,
                spaceBetween: 16,
                speed: 500,
                grabCursor: true,

                navigation: {
                    prevEl: '.financial-prev',
                    nextEl: '.financial-next',
                },

                breakpoints: {
                    640: {
                        slidesPerView: 2,
                    },
                    1024: {
                        slidesPerView: 3,
                    },
                    1280: {
                        slidesPerView: 4,
                    },
                },
            });
        });
    </script>

    {{-- KONTEN UTAMA BERDASARKAN TAB --}}

    @if ($activeTab === 'matrix')
        {{-- ========================================================================= --}}
        {{-- TAB 1: MATRIKS IURAN SISWA (BUKU PEMBANTU IURAN KAS KELAS)               --}}
        {{-- ========================================================================= --}}
        <section aria-labelledby="heading-matriks"
            class="flex flex-col rounded-2xl border border-line bg-white shadow-xs overflow-hidden">
            <div class="flex flex-col gap-1 border-b border-line px-6 py-5 bg-canvas/30">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="heading-matriks" class="text-lg font-bold text-black flex items-center gap-2">
                            <span>Matriks Rekapitulasi Iuran Kas Siswa</span>
                            <span
                                class="rounded-full bg-brand-soft px-2.5 py-0.5 text-xs font-semibold text-brand border border-brand/20">
                                Buku Pembantu Kas
                            </span>
                        </h2>
                        <p class="text-xs text-muted">Rekapitulasi setoran kas setiap anggota per periode tagihan,
                            total dana masuk, dan sisa tunggakan.</p>
                    </div>
                    <span class="text-xs font-semibold text-muted font-mono">
                        Total Anggota: {{ count($matrixData['rows']) }} Siswa
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-line bg-canvas/60 text-muted">
                        <tr>
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] w-12 text-center">No
                            </th>
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] min-w-28 text-center">
                                No. Absen / NIM</th>
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] min-w-44">Nama Siswa
                                / Anggota</th>
                            @foreach ($matrixData['periods'] as $period)
                                <th scope="col"
                                    class="px-3 py-3.5 font-bold uppercase tracking-wider text-[11px] text-center min-w-36">
                                    <div class="flex flex-col items-center">
                                        <span
                                            class="text-black font-semibold truncate max-w-32">{{ $period->title }}</span>
                                        <span
                                            class="text-[10px] text-muted font-normal">Rp{{ number_format($period->amount, 0, ',', '.') }}</span>
                                    </div>
                                </th>
                            @endforeach
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] text-right min-w-28 text-[#14532d]">
                                Total Setor</th>
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] text-right min-w-28 text-[#8f1d0e]">
                                Tunggakan</th>
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] text-center min-w-24">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse ($matrixData['rows'] as $row)
                            <tr class="hover:bg-black/[0.015] transition-colors">
                                <td class="px-4 py-3 text-center font-mono text-muted">{{ $row['no'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex min-w-16 shrink-0 items-center justify-center rounded-lg border border-black/10 bg-canvas px-2.5 py-1 font-mono text-xs font-bold text-muted tabular-nums">
                                        {{ $row['user']->student_no ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-semibold text-black">
                                    <div class="flex flex-col">
                                        <span>{{ $row['user']->name }}</span>
                                        <span
                                            class="text-[10px] text-muted capitalize">{{ $row['user']->role->value }}</span>
                                    </div>
                                </td>
                                @foreach ($matrixData['periods'] as $period)
                                    @php
                                        $cell = $row['cells'][$period->id] ?? null;
                                    @endphp
                                    <td class="px-3 py-3 text-center tabular-nums">
                                        @if (!$cell || $cell['status'] === 'none')
                                            <span class="text-muted/60">—</span>
                                        @elseif ($cell['status'] === 'paid')
                                            <span
                                                class="inline-flex flex-col items-center rounded-lg bg-[#dcfce7] px-2 py-0.5 text-[11px] font-semibold text-[#14532d] border border-[#bbf7d0]">
                                                <span>Lunas</span>
                                                <span
                                                    class="text-[10px] font-mono">Rp{{ number_format($cell['paid'], 0, ',', '.') }}</span>
                                            </span>
                                        @elseif ($cell['status'] === 'partial')
                                            <div class="inline-flex flex-col items-center gap-0.5">
                                                <span
                                                    class="inline-flex flex-col items-center rounded-lg bg-[#fff1d6] px-2 py-0.5 text-[11px] font-semibold text-[#7a4a00] border border-[#fed7aa]">
                                                    <span>Cicil
                                                        Rp{{ number_format($cell['paid'], 0, ',', '.') }}</span>
                                                    <span class="text-[10px] text-[#8f1d0e]">Sisa
                                                        Rp{{ number_format($cell['remaining'], 0, ',', '.') }}</span>
                                                </span>
                                                @if (auth()->user()?->isStaff() && !empty($cell['invoice_id']))
                                                    <a href="{{ route('payments.create', $cell['invoice_id']) }}"
                                                        class="text-[10px] font-semibold text-brand hover:underline print:hidden">Bantu
                                                        bayar</a>
                                                @endif
                                            </div>
                                        @else
                                            <div class="inline-flex flex-col items-center gap-0.5">
                                                <span
                                                    class="inline-flex flex-col items-center rounded-lg bg-[#fde4e0] px-2 py-0.5 text-[11px] font-semibold text-[#8f1d0e] border border-[#fecaca]">
                                                    <span>Belum Bayar</span>
                                                    @if ($cell['is_overdue'])
                                                        <span
                                                            class="text-[9px] uppercase tracking-tight text-[#8f1d0e] font-bold">Telat</span>
                                                    @endif
                                                </span>
                                                @if (auth()->user()?->isStaff() && !empty($cell['invoice_id']))
                                                    <a href="{{ route('payments.create', $cell['invoice_id']) }}"
                                                        class="text-[10px] font-semibold text-brand hover:underline print:hidden">Bantu
                                                        bayar</a>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-right font-bold text-[#14532d] tabular-nums">
                                    Rp{{ number_format($row['total_paid'], 0, ',', '.') }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-bold tabular-nums {{ $row['total_remaining'] > 0 ? 'text-[#8f1d0e]' : 'text-muted' }}">
                                    Rp{{ number_format($row['total_remaining'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($row['status'] === 'Lunas')
                                        <span
                                            class="inline-flex rounded-full bg-[#dcfce7] px-2.5 py-0.5 text-[11px] font-semibold text-[#14532d] border border-[#bbf7d0]">Lunas</span>
                                    @elseif ($row['status'] === 'Menyicil')
                                        <span
                                            class="inline-flex rounded-full bg-[#fff1d6] px-2.5 py-0.5 text-[11px] font-semibold text-[#7a4a00] border border-[#fed7aa]">Menyicil</span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-[#fde4e0] px-2.5 py-0.5 text-[11px] font-semibold text-[#8f1d0e] border border-[#fecaca]">Menunggak</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($matrixData['periods']) + 6 }}"
                                    class="px-6 py-12 text-center text-xs text-muted">
                                    Belum ada data anggota kelas terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-black/10 bg-canvas/60 font-bold text-xs">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right uppercase tracking-wider text-[11px]">Total
                                Kas Masuk & Tunggakan:</td>
                            @foreach ($matrixData['periods'] as $period)
                                @php
                                    $colCollected = $period->invoices->sum(fn($i) => $i->paidAmount());
                                @endphp
                                <td class="px-3 py-3 text-center font-mono text-[11px] text-[#14532d]">
                                    Rp{{ number_format($colCollected, 0, ',', '.') }}
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-right font-mono text-sm text-[#14532d]">
                                Rp{{ number_format($matrixData['totalPaid'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-[#8f1d0e]">
                                Rp{{ number_format($matrixData['totalRemaining'], 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    @elseif ($activeTab === 'bku')
        {{-- ========================================================================= --}}
        {{-- TAB 2: BUKU KAS UMUM (BKU KELAS - STANDAR AKUNTANSI SEKOLAH INDONESIA)    --}}
        {{-- ========================================================================= --}}
        <section aria-labelledby="heading-bku"
            class="flex flex-col rounded-2xl border border-line bg-white shadow-xs overflow-hidden">
            <div class="flex flex-col gap-1 border-b border-line px-6 py-5 bg-canvas/30">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="heading-bku" class="text-lg font-bold text-black flex items-center gap-2">
                            <span>Buku Kas Umum (BKU) Kas Kelas 1A</span>
                            <span
                                class="rounded-full bg-navy/10 px-2.5 py-0.5 text-xs font-semibold text-navy border border-navy/20">
                                Standar Akuntansi Kas
                            </span>
                        </h2>
                        <p class="text-xs text-muted">Jurnal kronologis setiap penerimaan iuran kas, pengeluaran
                            belanja kelas, serta mutasi saldo berjalan.</p>
                    </div>
                    <span class="text-xs font-semibold text-muted font-mono">
                        Total Catatan: {{ count($bkuData['entries']) }} Transaksi
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-line bg-canvas/60 text-muted">
                        <tr>
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] w-12 text-center">No
                            </th>
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] min-w-24">Tanggal
                            </th>
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] min-w-28">No. Bukti /
                                Ref</th>
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] min-w-64">Uraian
                                Transaksi</th>
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] text-right min-w-32 text-[#14532d]">
                                Penerimaan (Debet)</th>
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] text-right min-w-32 text-[#8f1d0e]">
                                Pengeluaran (Kredit)</th>
                            <th scope="col"
                                class="px-4 py-3.5 font-bold uppercase tracking-wider text-[11px] text-right min-w-32 text-black">
                                Saldo Kas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse ($bkuData['entries'] as $entry)
                            <tr class="hover:bg-black/[0.015] transition-colors">
                                <td class="px-4 py-3 text-center font-mono text-muted">{{ $entry['no'] }}</td>
                                <td class="px-4 py-3 font-mono text-muted whitespace-nowrap">{{ $entry['date'] }}</td>
                                <td class="px-4 py-3 font-mono text-xs font-bold text-muted whitespace-nowrap">
                                    <span class="rounded bg-black/5 px-2 py-0.5">{{ $entry['ref'] }}</span>
                                </td>
                                <td class="px-4 py-3 font-medium text-black">
                                    {{ $entry['description'] }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-mono font-semibold tabular-nums {{ $entry['debit'] > 0 ? 'text-[#14532d]' : 'text-muted/40' }}">
                                    {{ $entry['debit'] > 0 ? 'Rp' . number_format($entry['debit'], 0, ',', '.') : '—' }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-mono font-semibold tabular-nums {{ $entry['credit'] > 0 ? 'text-[#8f1d0e]' : 'text-muted/40' }}">
                                    {{ $entry['credit'] > 0 ? 'Rp' . number_format($entry['credit'], 0, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-black tabular-nums">
                                    Rp{{ number_format($entry['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-xs text-muted">
                                    Belum ada transaksi kas kelas yang tercatat dalam Buku Kas Umum.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-black/10 bg-canvas/60 font-bold text-xs">
                        <tr>
                            <td colspan="4" class="px-4 py-3.5 text-right uppercase tracking-wider text-[11px]">
                                Rekapitulasi Total Buku Kas Umum:</td>
                            <td class="px-4 py-3.5 text-right font-mono text-sm text-[#14532d]">
                                Rp{{ number_format($bkuData['totalDebit'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-sm text-[#8f1d0e]">
                                Rp{{ number_format($bkuData['totalCredit'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-sm text-brand">
                                Rp{{ number_format($bkuData['endingBalance'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    @else
        {{-- ========================================================================= --}}
        {{-- TAB 3: DETAIL TAGIHAN & CICILAN (DEFAULT VIEW)                            --}}
        {{-- ========================================================================= --}}

        {{-- Info Aturan Cicilan & Pengkondisian Tenggat --}}
        {{-- Rekap per Periode --}}
        @if ($periodSummaries->isNotEmpty())
            <section aria-labelledby="rekap-periode-heading"
                class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
                <div class="border-b border-line px-6 py-4.5">
                    <h2 id="rekap-periode-heading" class="text-base font-bold text-black">Ringkasan per Periode
                        Tagihan</h2>
                    <p class="text-xs text-muted">Perbandingan target, dana masuk (termasuk cicilan), dan status
                        tenggat tiap periode.</p>
                </div>
                <div class="divide-y divide-black/5">
                    @foreach ($periodSummaries as $item)
                        @php
                            $p = $item['period'];
                            $pct = $item['target'] > 0 ? (int) round(($item['collected'] * 100) / $item['target']) : 0;
                        @endphp
                        <div
                            class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 hover:bg-black/[0.015] transition-colors">
                            <div class="flex min-w-0 flex-1 basis-56 flex-col gap-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-bold text-black">{{ $p->title }}</span>
                                    <div class="flex-nowrap gap-2 flex">
                                        <span
                                            class="rounded-full bg-brand-soft px-2.5 py-0.5 text-[11px] font-semibold text-brand border border-brand/20">
                                            {{ $p->category->name ?? 'Uang Kas' }}
                                        </span>
                                        @if ($item['paid_count'] === $item['total_count'] && $item['total_count'] > 0)
                                            <span
                                                class="rounded-full bg-[#dcfce7] px-2 py-0.5 text-[11px] font-semibold text-[#14532d] border border-[#bbf7d0]">Lunas
                                                Semua</span>
                                        @elseif ($item['is_overdue'])
                                            <span
                                                class="rounded-full bg-[#fde4e0] px-2 py-0.5 text-[11px] font-semibold text-[#8f1d0e] border border-[#fecaca]">Lewat
                                                Tenggat</span>
                                        @else
                                            <span
                                                class="rounded-full bg-[#fff1d6] px-2 py-0.5 text-[11px] font-semibold text-[#7a4a00] border border-[#fed7aa]">Masih
                                                Bisa Dicicil</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-xs text-muted">
                                    Tenggat: {{ $p->due_date->format('d M Y') }} · {{ $item['paid_count'] }} lunas,
                                    {{ $item['partial_count'] }} menyicil, {{ $item['unpaid_count'] }} belum bayar
                                </span>
                            </div>

                            <div class="flex  items-center gap-4 text-center md:w-auto w-full md:text-right">
                                <div class="flex flex-col md:w-auto w-full">
                                    <span class="text-xs font-bold text-[#14532d] tabular-nums">
                                        Rp{{ number_format($item['collected'], 0, ',', '.') }} /
                                        Rp{{ number_format($item['target'], 0, ',', '.') }}
                                    </span>
                                    <span class="text-[11px] text-muted tabular-nums">
                                        Terkumpul {{ $pct }}% · Sisa
                                        Rp{{ number_format($item['remaining'], 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Filter & Daftar Detail Pembayaran Anggota --}}
        <section aria-labelledby="detail-laporan-heading"
            class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">

            <div class="flex flex-col gap-4 border-b border-line px-4 py-5 sm:px-6">

                {{-- Heading --}}
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h2 id="detail-laporan-heading" class="text-base font-bold text-black sm:text-lg">
                            Detail Pembayaran & Cicilan Anggota
                        </h2>
                        <p class="mt-1 max-w-xl text-xs leading-relaxed text-muted">
                            Pantau nominal terbayar, sisa cicilan, dan kondisi jatuh tempo per anggota.
                        </p>
                    </div>
                </div>

                {{-- Filter --}}
                <form method="GET" action="{{ route('reports.payments') }}"
                    class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:flex xl:flex-wrap xl:items-center print:hidden">

                    <input type="hidden" name="tab" value="detail">

                    @if ($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif

                    <select name="period_id" aria-label="Filter periode"
                        class="min-h-10 w-full min-w-0 rounded-xl border border-black/15 bg-white px-3 text-xs font-medium text-black focus:border-brand focus:outline-none xl:w-auto xl:min-w-40">
                        <option value="">Semua Periode</option>
                        @foreach ($periods as $p)
                            <option value="{{ $p->id }}" @selected($selectedPeriodId === $p->id)>
                                {{ $p->title }}
                            </option>
                        @endforeach
                    </select>

                    <select name="category_id" aria-label="Filter kategori"
                        class="min-h-10 w-full min-w-0 rounded-xl border border-black/15 bg-white px-3 text-xs font-medium text-black focus:border-brand focus:outline-none xl:w-auto xl:min-w-40">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($selectedCategoryId === $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>

                    <div class="flex min-w-0 gap-2 sm:col-span-2 xl:ml-auto xl:w-auto">
                        <input type="search" name="q" value="{{ $search }}"
                            placeholder="Cari nama / NIM..."
                            class="min-h-10 min-w-0 flex-1 rounded-xl border border-black/15 bg-canvas/30 px-3 text-xs text-black placeholder:text-muted/60 focus:border-brand focus:bg-white focus:outline-none xl:w-48 xl:flex-none">

                        <button type="submit"
                            class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-xl bg-brand px-4 text-xs font-semibold text-white shadow-xs transition hover:bg-brand-dark">
                            Filter
                        </button>
                    </div>
                </form>

                {{-- Status Filter --}}
                @php
                    $baseQuery = array_filter(
                        [
                            'tab' => 'detail',
                            'period_id' => $selectedPeriodId,
                            'category_id' => $selectedCategoryId,
                            'q' => $search,
                        ],
                        fn($value) => $value !== null && $value !== '',
                    );
                @endphp

                <div class="-mx-4 overflow-x-auto px-4 sm:-mx-6 sm:px-6 print:hidden">
                    <div class="flex w-max items-center gap-1.5 text-xs">
                        <a href="{{ route('reports.payments', $baseQuery) }}" @class([
                            'whitespace-nowrap rounded-xl px-3 py-2 font-semibold transition-colors',
                            'bg-black text-white' => $statusFilter === '',
                            'text-muted hover:bg-canvas' => $statusFilter !== '',
                        ])>
                            Semua ({{ $totalCount }})
                        </a>

                        <a href="{{ route('reports.payments', array_merge($baseQuery, ['status' => 'paid'])) }}"
                            @class([
                                'whitespace-nowrap rounded-xl px-3 py-2 font-semibold transition-colors',
                                'bg-black text-white' => $statusFilter === 'paid',
                                'text-muted hover:bg-canvas' => $statusFilter !== 'paid',
                            ])>
                            Lunas ({{ $paidCount }})
                        </a>

                        <a href="{{ route('reports.payments', array_merge($baseQuery, ['status' => 'partial'])) }}"
                            @class([
                                'whitespace-nowrap rounded-xl px-3 py-2 font-semibold transition-colors',
                                'bg-black text-white' => $statusFilter === 'partial',
                                'text-muted hover:bg-canvas' => $statusFilter !== 'partial',
                            ])>
                            Menyicil ({{ $partialCount }})
                        </a>

                        <a href="{{ route('reports.payments', array_merge($baseQuery, ['status' => 'unpaid'])) }}"
                            @class([
                                'whitespace-nowrap rounded-xl px-3 py-2 font-semibold transition-colors',
                                'bg-black text-white' => $statusFilter === 'unpaid',
                                'text-muted hover:bg-canvas' => $statusFilter !== 'unpaid',
                            ])>
                            Belum Bayar ({{ $unpaidCount }})
                        </a>

                        <a href="{{ route('reports.payments', array_merge($baseQuery, ['status' => 'overdue'])) }}"
                            @class([
                                'whitespace-nowrap rounded-xl px-3 py-2 font-semibold transition-colors',
                                'bg-black text-white' => $statusFilter === 'overdue',
                                'text-muted hover:bg-canvas' => $statusFilter !== 'overdue',
                            ])>
                            Lewat Tenggat ({{ $overdueCount }})
                        </a>
                    </div>
                </div>
            </div>


            <div class="divide-y divide-black/5">
                @forelse ($invoices as $invoice)
                    @php
                        $paidAmt = $invoice->paidAmount();
                        $remAmt = $invoice->remainingAmount();
                        $pct = $invoice->amount > 0 ? (int) round(($paidAmt * 100) / $invoice->amount) : 0;
                        $paidInstallmentsCount = $invoice->payments->where('status', 'paid')->count();
                        $isOverdue = $invoice->isOverdue();
                    @endphp
                    <div
                        class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 hover:bg-black/[0.015] transition-colors">
                        <div class="flex min-w-0 flex-1 basis-60 items-start gap-4">
                            <span
                                class="inline-flex min-w-16 shrink-0 items-center justify-center rounded-lg  px-2.5 py-1 font-mono text-xs font-bold text-muted tabular-nums">{{ $invoice->user->student_no ?? '—' }}</span>
                            <div class="flex flex-col gap-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-bold text-black">{{ $invoice->user->name }}</span>
                                    ·
                                    <span class="rounded-full text-muted  py-0.5 text-[10px] font-semibold ">
                                        {{ $invoice->period->category->name ?? 'Uang Kas' }}
                                    </span>
                                </div>
                                <span class="text-xs text-muted">
                                    {{ $invoice->period->title }} · Tenggat
                                    {{ $invoice->period->due_date->format('d M Y') }}
                                    @if ($paidInstallmentsCount > 0)
                                        · {{ $paidInstallmentsCount }}x pembayaran masuk
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col items-end md:w-auto w-full gap-1 min-w-44">
                            <div class="flex items-center  gap-2 text-xs tabular-nums">
                                <span
                                    class="font-bold text-[#14532d]">Rp{{ number_format($paidAmt, 0, ',', '.') }}</span>
                                <span class="text-muted ">/</span>
                                <span
                                    class="font-semibold text-black">Rp{{ number_format($invoice->amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="h-1.5 w-36 md:w-auto w-full overflow-hidden rounded-full bg-black/10">
                                <div class="h-full rounded-full {{ $invoice->isPaid() ? 'bg-[#14532d]' : ($isOverdue ? 'bg-[#8f1d0e]' : 'bg-brand') }}"
                                    style="width: {{ $pct }}%"></div>
                            </div>
                            @if ($remAmt > 0)
                                <span
                                    class="text-[11px] font-medium {{ $isOverdue ? 'text-[#8f1d0e]' : 'text-muted' }} tabular-nums">
                                    Sisa: Rp{{ number_format($remAmt, 0, ',', '.') }}
                                </span>
                            @endif
                        </div>

                    </div>
            </div>
        @empty
            <p class="px-6 py-12 text-center text-xs text-muted">Tidak ada data laporan pembayaran yang sesuai
                filter.</p>
    @endforelse
    </div>
    </section>
    @endif


</x-layouts.app>
