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

            @include('reports._actions')
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
        @include('reports._matrix')
        @include('reports._signoff')
    @elseif ($activeTab === 'bku')
        {{-- ========================================================================= --}}
        {{-- TAB 2: BUKU KAS UMUM (BKU KELAS - STANDAR AKUNTANSI SEKOLAH INDONESIA)    --}}
        {{-- ========================================================================= --}}
        @include('reports._bku')
        @include('reports._signoff')
    @else
        @if ($periodSummaries->isNotEmpty())
            <section aria-labelledby="rekap-periode-heading"
                class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
                <div class="flex flex-col gap-1.5 border-b border-line px-4 py-5 sm:px-6">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <h2 id="rekap-periode-heading" class="text-base font-bold text-black sm:text-lg">Ringkasan per
                            Periode Tagihan</h2>
                        <span
                            class="inline-flex items-center rounded-full border border-brand/20 bg-brand-soft px-3 py-1 text-xs font-semibold tabular-nums text-brand">
                            {{ $periodSummaries->count() }} periode
                        </span>
                    </div>
                    <p class="max-w-xl text-xs leading-relaxed text-muted">Perbandingan target, dana masuk (termasuk
                        cicilan), dan status tenggat tiap periode.</p>
                </div>
                <div class="divide-y divide-black/5">
                    @foreach ($periodSummaries as $item)
                        @php
                            $p = $item['period'];
                            $pct = $item['target'] > 0 ? min(100, (int) round(($item['collected'] * 100) / $item['target'])) : 0;
                        @endphp
                        <div class="flex flex-col gap-3 px-4 py-4 transition-colors hover:bg-black/1.5 sm:px-6">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                <div class="flex min-w-0 flex-col gap-1.5">
                                    <span class="text-sm font-bold text-black wrap-break-word">{{ $p->title }}</span>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span
                                            class="rounded-full border border-brand/20 bg-brand-soft px-2.5 py-0.5 text-[11px] font-semibold text-brand">{{ $p->category->name ?? 'Uang Kas' }}</span>
                                        @if ($item['paid_count'] === $item['total_count'] && $item['total_count'] > 0)
                                            <span
                                                class="rounded-full border border-[#bbf7d0] bg-[#dcfce7] px-2.5 py-0.5 text-[11px] font-semibold text-[#14532d]">Lunas Semua</span>
                                        @elseif ($item['is_overdue'])
                                            <span
                                                class="rounded-full border border-[#fecaca] bg-[#fde4e0] px-2.5 py-0.5 text-[11px] font-semibold text-[#8f1d0e]">Lewat Tenggat</span>
                                        @else
                                            <span
                                                class="rounded-full border border-[#fed7aa] bg-[#fff1d6] px-2.5 py-0.5 text-[11px] font-semibold text-[#7a4a00]">Masih Bisa Dicicil</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-col sm:items-end sm:text-right">
                                    <span class="text-sm font-bold tabular-nums text-[#14532d]">
                                        Rp{{ number_format($item['collected'], 0, ',', '.') }}
                                        <span class="font-medium text-muted">/
                                            Rp{{ number_format($item['target'], 0, ',', '.') }}</span>
                                    </span>
                                    <span class="text-[11px] tabular-nums text-muted">Sisa
                                        Rp{{ number_format($item['remaining'], 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-black/5" role="progressbar"
                                    aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"
                                    aria-label="Terkumpul {{ $pct }}%">
                                    <div class="h-full rounded-full bg-[#16a34a]" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="w-10 shrink-0 text-right text-[11px] font-semibold tabular-nums text-black">{{ $pct }}%</span>
                            </div>

                            <p class="text-xs leading-relaxed text-muted">
                                Tenggat {{ $p->due_date->format('d M Y') }} ·
                                <span class="font-medium text-black">{{ $item['paid_count'] }}</span> lunas,
                                <span class="font-medium text-black">{{ $item['partial_count'] }}</span> menyicil,
                                <span class="font-medium text-black">{{ $item['unpaid_count'] }}</span> belum bayar
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif


        {{-- Filter & Daftar Detail Pembayaran Anggota --}}
        <section aria-labelledby="detail-laporan-heading"
            class="flex flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-xs">

            {{-- Header & Filter --}}
            <div class="flex flex-col gap-4 border-b border-line px-4 py-5 sm:px-6">

                {{-- Heading --}}
                <div class="flex flex-col gap-1.5">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <h2 id="detail-laporan-heading" class="text-base font-bold text-black sm:text-lg">
                            Detail Pembayaran & Cicilan Anggota
                        </h2>

                        <span
                            class="inline-flex items-center rounded-full border border-brand/20 bg-brand-soft px-3 py-1 text-xs font-semibold tabular-nums text-brand">
                            {{ $invoices->total() }} pembayaran
                        </span>
                    </div>

                    <p class="max-w-xl text-xs leading-relaxed text-muted">
                        Pantau nominal terbayar, sisa cicilan, dan kondisi jatuh tempo per anggota.
                    </p>
                </div>

                {{-- Filter --}}
                <form method="GET" action="{{ route('reports.payments') }}"
                    class="grid grid-cols-2 gap-2 xl:flex xl:flex-wrap xl:items-center print:hidden">

                    <input type="hidden" name="tab" value="detail">

                    @if ($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif

                    <select name="period_id" aria-label="Filter periode"
                        class="min-h-10 w-full min-w-0 rounded-xl border border-black/15 bg-white px-3 text-xs font-medium text-black focus:border-brand focus:outline-none xl:w-auto xl:min-w-40">
                        <option value="">Semua Periode</option>

                        @foreach ($periods as $p)
                            <option value="{{ $p->id }}" @selected((string) $selectedPeriodId === (string) $p->id)>
                                {{ $p->title }}
                            </option>
                        @endforeach
                    </select>

                    <select name="category_id" aria-label="Filter kategori"
                        class="min-h-10 w-full min-w-0 rounded-xl border border-black/15 bg-white px-3 text-xs font-medium text-black focus:border-brand focus:outline-none xl:w-auto xl:min-w-40">
                        <option value="">Semua Kategori</option>

                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) $selectedCategoryId === (string) $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>

                    <div class="col-span-2 flex min-w-0 gap-2 xl:ml-auto xl:w-auto">
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

                @php
                    $statusChips = [
                        ['', 'Semua', $totalCount],
                        ['paid', 'Lunas', $paidCount],
                        ['partial', 'Menyicil', $partialCount],
                        ['unpaid', 'Belum Bayar', $unpaidCount],
                        ['overdue', 'Lewat Tenggat', $overdueCount],
                    ];
                @endphp

                <div class="flex flex-wrap items-center gap-2 text-xs print:hidden" role="group"
                    aria-label="Filter status pembayaran">
                    @foreach ($statusChips as [$key, $label, $count])
                        <a href="{{ route('reports.payments', $key === '' ? $baseQuery : array_merge($baseQuery, ['status' => $key])) }}"
                            @if ($statusFilter === $key) aria-current="true" @endif
                            @class([
                                'inline-flex min-h-9 items-center gap-1.5 whitespace-nowrap rounded-full border px-3.5 font-semibold transition-colors',
                                'border-black bg-black text-white' => $statusFilter === $key,
                                'border-black/10 bg-white text-muted hover:bg-canvas hover:text-black' =>
                                    $statusFilter !== $key,
                            ])>
                            {{ $label }}
                            <span @class([
                                'rounded-full px-1.5 text-[11px] tabular-nums',
                                'bg-white/20' => $statusFilter === $key,
                                'bg-black/5' => $statusFilter !== $key,
                            ])>{{ $count }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            {{-- Daftar Invoice --}}
            <div class="divide-y divide-black/5">

                @forelse ($invoices as $invoice)
                    @php
                        $paidAmt = $invoice->paidAmount();
                        $remAmt = $invoice->remainingAmount();

                        $pct = $invoice->amount > 0 ? (int) round(($paidAmt * 100) / $invoice->amount) : 0;

                        $pct = min(100, max(0, $pct));

                        $paidInstallmentsCount = $invoice->payments->where('status', 'paid')->count();

                        $isOverdue = $invoice->isOverdue();
                        $isPaid = $invoice->isPaid();
                    @endphp

                    <div
                        class="flex flex-col gap-4 px-4 py-4 transition-colors hover:bg-black/[0.015] sm:px-6 md:flex-row md:items-center md:justify-between">

                        {{-- Identitas Anggota --}}
                        <div class="flex min-w-0 flex-1 items-start gap-4">

                            <span
                                class="inline-flex min-w-16 shrink-0 items-center justify-center rounded-lg bg-canvas px-2.5 py-1 font-mono text-xs font-bold tabular-nums text-muted">
                                {{ $invoice->user->student_no ?? '—' }}
                            </span>

                            <div class="flex min-w-0 flex-col gap-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="break-words text-sm font-bold text-black">
                                        {{ $invoice->user->name ?? 'Anggota tidak ditemukan' }}
                                    </span>

                                    <span class="text-muted">·</span>

                                    <span class="rounded-full py-0.5 text-[10px] font-semibold text-muted">
                                        {{ $invoice->period->category->name ?? 'Uang Kas' }}
                                    </span>
                                </div>

                                <span class="text-xs leading-relaxed text-muted">
                                    {{ $invoice->period->title ?? 'Periode tidak tersedia' }}
                                    · Tenggat
                                    {{ $invoice->period?->due_date?->format('d M Y') ?? '—' }}

                                    @if ($paidInstallmentsCount > 0)
                                        · {{ $paidInstallmentsCount }}x pembayaran masuk
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Progress Pembayaran --}}
                        <div class="flex w-full flex-col gap-1 md:w-52 md:shrink-0">

                            <div class="flex items-center justify-between gap-2 text-xs tabular-nums">
                                <span class="font-bold text-[#14532d]">
                                    Rp{{ number_format($paidAmt, 0, ',', '.') }}
                                </span>

                                <span class="text-muted">/</span>

                                <span class="font-semibold text-black">
                                    Rp{{ number_format($invoice->amount, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-black/10">
                                <div class="h-full rounded-full transition-all
                            {{ $isPaid ? 'bg-[#14532d]' : ($isOverdue ? 'bg-[#8f1d0e]' : 'bg-brand') }}"
                                    style="width: {{ $pct }}%">
                                </div>
                            </div>

                            @if ($remAmt > 0)
                                <span
                                    class="text-right text-[11px] font-medium tabular-nums
                            {{ $isOverdue ? 'text-[#8f1d0e]' : 'text-muted' }}">
                                    Sisa: Rp{{ number_format($remAmt, 0, ',', '.') }}
                                </span>
                            @endif

                        </div>

                        {{-- Status Pembayaran --}}
                        <div class="flex flex-wrap items-center gap-2 md:w-60 md:shrink-0 md:justify-end">
                            @if ($isPaid)
                                <span
                                    class="rounded-full border border-[#bbf7d0] bg-[#dcfce7] px-2.5 py-0.5 text-xs font-semibold text-[#14532d]">Lunas</span>
                            @elseif ($invoice->isPartial())
                                <span
                                    class="rounded-full border border-[#fed7aa] bg-[#fff1d6] px-2.5 py-0.5 text-xs font-semibold text-[#7a4a00]">Menyicil ({{ $pct }}%)</span>
                            @else
                                <span
                                    class="rounded-full border border-[#fecaca] bg-[#fde4e0] px-2.5 py-0.5 text-xs font-semibold text-[#8f1d0e]">Belum Bayar</span>
                            @endif

                            @if ($isOverdue)
                                <span
                                    class="rounded-full bg-[#8f1d0e] px-2.5 py-0.5 text-[11px] font-semibold text-white">Terlambat {{ $invoice->overdueDays() }} hari · Wajib Lunas Penuh</span>
                            @endif
                        </div>
                    </div>

                @empty
                    <div class="px-6 py-14 text-center">
                        <div class="mx-auto mb-3 flex size-11 items-center justify-center rounded-full bg-black/5">
                            <svg class="size-5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7">
                                <path d="M9 12l2 2 4-4M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z" />
                            </svg>
                        </div>

                        <p class="text-sm font-semibold text-black">
                            Tidak ada data pembayaran
                        </p>

                        <p class="mt-1 text-xs text-muted">
                            Tidak ada data laporan pembayaran yang sesuai filter.
                        </p>
                    </div>
                @endforelse

            </div>

            {{-- Pagination --}}
            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-line px-4 py-4 sm:px-6">

                <div class="text-xs text-muted">
                    Menampilkan
                    <span class="font-semibold text-black">
                        {{ $invoices->firstItem() ?? 0 }}–{{ $invoices->lastItem() ?? 0 }}
                    </span>
                    dari
                    <span class="font-semibold text-black">
                        {{ $invoices->total() }}
                    </span>
                    pembayaran
                </div>

                <div class="flex items-center gap-3">

                    <span class="text-xs font-semibold tabular-nums text-black">
                        {{ $invoices->currentPage() }} / {{ max(1, $invoices->lastPage()) }}
                    </span>

                    <a href="{{ $invoices->previousPageUrl() ?? '#' }}" @class([
                        'flex size-9 items-center justify-center rounded-lg border border-line transition',
                        'pointer-events-none opacity-30' => !$invoices->previousPageUrl(),
                        'hover:bg-black/5' => $invoices->previousPageUrl(),
                    ])
                        aria-label="Halaman sebelumnya">

                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                    </a>

                    <a href="{{ $invoices->nextPageUrl() ?? '#' }}" @class([
                        'flex size-9 items-center justify-center rounded-lg border border-line transition',
                        'pointer-events-none opacity-30' => !$invoices->nextPageUrl(),
                        'hover:bg-black/5' => $invoices->nextPageUrl(),
                    ])
                        aria-label="Halaman berikutnya">

                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </a>

                </div>
            </div>

        </section>

    @endif


</x-layouts.app>
