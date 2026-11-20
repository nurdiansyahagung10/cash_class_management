<div class="flex flex-col gap-6 sm:gap-8">
    @php
        $tabQuery = array_diff_key($query, array_flip(['tab', 'period_page']));
        $tabs = [
            'detail' => ['Rincian tagihan', 'Rincian Tagihan & Cicilan', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
            'matrix' => ['Iuran siswa', 'Iuran Siswa per Periode & Matriks', 'M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            'bku' => ['Buku kas umum', 'Buku Kas Umum (BKU Kelas)', 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
        ];
        $stats = [
            ['Saldo kas di tangan', 'Rp' . number_format($realCashBalance, 0, ',', '.'), 'Setelah dikurangi pengeluaran', 'default', null],
            ['Total iuran masuk', 'Rp' . number_format($totalCollected, 0, ',', '.'), 'Target Rp' . number_format($totalExpected, 0, ',', '.'), 'default', $collectionRate],
            ['Pengeluaran kas', 'Rp' . number_format($totalExpense, 0, ',', '.'), 'Belanja kelas terverifikasi', 'default', null],
            ['Sisa piutang', 'Rp' . number_format($totalRemaining, 0, ',', '.'), $unpaidCount . ' belum bayar · ' . $partialCount . ' menyicil', 'default', null],
            ['Lewat jatuh tempo', $overdueCount . ' tagihan', 'Tertunggak Rp' . number_format($overdueRemaining, 0, ',', '.'), $overdueCount > 0 ? 'danger' : 'default', null],
        ];
    @endphp

    <div class="flex flex-col gap-5 border-b border-line pb-5">
        <x-ui.page-header :eyebrow="'Laporan · ' . now()->translatedFormat('l, d F Y')" title="Laporan pembayaran"
            description="Rekap iuran, cicilan, matriks per siswa, dan buku kas umum kelas.">
            <x-slot:actions>
                @include('reports._actions')
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Tab format laporan --}}
        <nav aria-label="Format Laporan" class="scroll-row -mx-4 px-4 sm:mx-0 sm:px-0 print:hidden">
            <div class="inline-flex min-w-max items-center gap-1 rounded-lg border border-line bg-canvas-2 p-1">
                @foreach ($tabs as $key => [$short, $long, $path])
                    <a href="{{ route('reports.payments', array_merge($tabQuery, ['tab' => $key])) }}"
                        wire:click.prevent="showTab('{{ $key }}')"
                        @if ($activeTab === $key) aria-current="page" @endif
                        @class([
                            'inline-flex min-h-9 items-center gap-2 whitespace-nowrap rounded-lg px-3 text-[13px] font-medium transition-colors',
                            'bg-surface text-ink shadow-xs ring-1 ring-line' => $activeTab === $key,
                            'text-muted hover:text-ink' => $activeTab !== $key,
                        ])>
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}" /></svg>
                        <span class="sm:hidden">{{ $short }}</span>
                        <span class="hidden sm:inline">{{ $long }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    </div>

    {{-- Ringkasan finansial --}}
    {{-- One summary panel; hairlines come from the 1px grid gap over a line-colored background. --}}
    <section aria-label="Ringkasan laporan pembayaran"
        class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-line bg-line shadow-xs md:grid-cols-3 xl:grid-cols-5">
        @foreach ($stats as $index => [$label, $value, $detail, $tone, $progress])
            <div @class([
                'flex min-w-0 flex-col gap-1 bg-surface px-4 py-3.5',
                'col-span-2 md:col-span-1' => $index === 0,
            ])>
                <p class="text-xs text-muted">{{ $label }}</p>
                <p @class([
                    'truncate text-lg font-semibold tracking-[-0.02em] tabular-nums sm:text-xl',
                    'text-danger' => $tone === 'danger',
                    'text-ink' => $tone !== 'danger',
                ])>{{ $value }}</p>
                @if ($progress !== null)
                    <div class="mt-0.5 h-1 w-full overflow-hidden rounded-full bg-line" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Terkumpul {{ $progress }}%">
                        <div class="h-full rounded-full bg-brand" style="width: {{ min(100, $progress) }}%"></div>
                    </div>
                @endif
                <p class="text-[11px] leading-snug text-muted">
                    {{ $detail }}@if ($progress !== null) · <span class="tabular-nums text-body">{{ $progress }}%</span>@endif
                </p>
            </div>
        @endforeach
    </section>

    {{-- KONTEN UTAMA BERDASARKAN TAB --}}
    <div wire:loading.delay.class="opacity-60" class="flex flex-col gap-6 transition-opacity sm:gap-8">

    @if ($activeTab === 'matrix')
        {{-- ========================================================================= --}}
        {{-- TAB 1: MATRIKS IURAN SISWA (BUKU PEMBANTU IURAN KAS KELAS)               --}}
        {{-- ========================================================================= --}}
        @include($matrixLayout === 'grid' ? 'reports._matrix' : 'reports._period_board')
        @include('reports._signoff')
    @elseif ($activeTab === 'bku')
        {{-- ========================================================================= --}}
        {{-- TAB 2: BUKU KAS UMUM (BKU KELAS - STANDAR AKUNTANSI SEKOLAH INDONESIA)    --}}
        {{-- ========================================================================= --}}
        @include('reports._bku')
        @include('reports._signoff')
    @else
        @if ($periodSummaries->isNotEmpty())
            <section aria-labelledby="rekap-periode-heading" class="ui-card flex flex-col overflow-hidden">
                <div class="flex flex-col gap-1 border-b border-line px-4 py-4 sm:px-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 id="rekap-periode-heading" class="text-base font-semibold text-ink">Ringkasan per Periode Tagihan</h2>
                        <x-ui.badge class="font-mono tabular-nums">{{ $periodSummaries->total() }} periode</x-ui.badge>
                    </div>
                    <p class="max-w-xl text-xs leading-relaxed text-muted">Target, dana masuk (termasuk cicilan), dan status tenggat tiap periode.</p>
                </div>

                <div class="hidden grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)] gap-6 border-b border-line bg-canvas px-6 py-2 md:grid">
                    <span class="ui-eyebrow">Periode</span>
                    <span class="ui-eyebrow">Terkumpul</span>
                    <span class="ui-eyebrow text-right">Nominal</span>
                </div>

                <ul class="divide-y divide-line">
                    @foreach ($periodSummaries as $item)
                        @php
                            $p = $item['period'];
                            $pct = $item['target'] > 0 ? min(100, (int) round(($item['collected'] * 100) / $item['target'])) : 0;
                            $allPaid = $item['paid_count'] === $item['total_count'] && $item['total_count'] > 0;
                        @endphp
                        <li class="grid gap-3 px-4 py-3.5 transition-colors hover:bg-canvas sm:px-6 md:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)] md:items-center md:gap-6">
                            <div class="flex min-w-0 flex-col gap-1.5">
                                <span class="text-sm font-medium text-ink wrap-break-word">{{ $p->title }}</span>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <x-ui.badge>{{ $p->category->name ?? 'Uang Kas' }}</x-ui.badge>
                                    @if ($allPaid)
                                        <x-ui.badge tone="success" dot>Lunas Semua</x-ui.badge>
                                    @elseif ($item['is_overdue'])
                                        <x-ui.badge tone="danger" dot>Lewat Tenggat</x-ui.badge>
                                    @else
                                        <x-ui.badge tone="warning" dot>Masih Bisa Dicicil</x-ui.badge>
                                    @endif
                                </div>
                                <p class="text-xs text-muted">
                                    Tenggat {{ $p->due_date->format('d M Y') }} ·
                                    <span class="text-ink">{{ $item['paid_count'] }}</span> lunas,
                                    <span class="text-ink">{{ $item['partial_count'] }}</span> menyicil,
                                    <span class="text-ink">{{ $item['unpaid_count'] }}</span> belum bayar
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-line" role="progressbar"
                                    aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Terkumpul {{ $pct }}%">
                                    <div class="h-full rounded-full {{ $allPaid ? 'bg-success' : 'bg-brand' }}" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="w-9 shrink-0 text-right font-mono text-[11px] tabular-nums text-ink">{{ $pct }}%</span>
                            </div>

                            <div class="flex items-baseline justify-between gap-2 md:flex-col md:items-end md:gap-0">
                                <span class="text-sm font-medium tabular-nums text-ink">
                                    Rp{{ number_format($item['collected'], 0, ',', '.') }}
                                    <span class="font-normal text-muted">/ Rp{{ number_format($item['target'], 0, ',', '.') }}</span>
                                </span>
                                <span class="text-[11px] tabular-nums text-muted">Sisa Rp{{ number_format($item['remaining'], 0, ',', '.') }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <x-report-pager live :paginator="$periodSummaries" label="periode" per-page-model="summaryPerPage" :per-page-options="$summaryPageSizes" />
            </section>
        @endif


        {{-- Filter & Daftar Detail Pembayaran Anggota --}}
        <section aria-labelledby="detail-laporan-heading"
            class="flex flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-xs">

            {{-- Header & Filter --}}
            <div class="flex flex-col gap-4 border-b border-line px-4 py-5 sm:px-6">

                {{-- Heading --}}
                <div class="flex flex-col gap-1.5">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <h2 id="detail-laporan-heading" class="text-base font-semibold text-ink">
                            Detail Pembayaran & Cicilan Anggota
                        </h2>

                        <span
                            class="ui-tag ui-tag-plain tabular-nums font-mono">
                            {{ $invoices->total() }} pembayaran
                        </span>
                    </div>

                    <p class="max-w-xl text-xs leading-relaxed text-muted">
                        Pantau nominal terbayar, sisa cicilan, dan kondisi jatuh tempo per anggota.
                    </p>
                </div>

                {{-- Filter --}}
                <form method="GET" action="{{ route('reports.payments') }}" wire:submit.prevent="$refresh"
                    class="grid grid-cols-2 gap-2 xl:flex xl:flex-wrap xl:items-center print:hidden">

                    <input type="hidden" name="tab" value="detail">

                    @if ($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif

                    <div wire:ignore class="min-w-0 xl:min-w-40">
                    <select name="period_id" data-tom-select wire:model.live="periodId" aria-label="Filter periode"
                        class="min-h-10 w-full min-w-0 rounded-xl border border-line bg-surface px-3 text-xs font-medium text-ink focus:border-line-strong focus:outline-none xl:w-auto xl:min-w-40">
                        <option value="">Semua Periode</option>

                        @foreach ($periods as $p)
                            <option value="{{ $p->id }}" @selected((string) $selectedPeriodId === (string) $p->id)>
                                {{ $p->title }}
                            </option>
                        @endforeach
                    </select>
                    </div>

                    <div wire:ignore class="min-w-0 xl:min-w-40">
                    <select name="category_id" data-tom-select wire:model.live="categoryId" aria-label="Filter kategori"
                        class="min-h-10 w-full min-w-0 rounded-xl border border-line bg-surface px-3 text-xs font-medium text-ink focus:border-line-strong focus:outline-none xl:w-auto xl:min-w-40">
                        <option value="">Semua Kategori</option>

                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) $selectedCategoryId === (string) $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    </div>

                    <div class="col-span-2 flex min-w-0 gap-2 xl:ml-auto xl:w-auto">
                        <input type="search" name="q" value="{{ $search }}" wire:model.live.debounce.400ms="search"
                            placeholder="Cari nama / NIM..."
                            class="min-h-10 min-w-0 flex-1 rounded-xl border border-line bg-canvas/30 px-3 text-xs text-ink placeholder:text-muted/60 focus:border-line-strong focus:bg-surface focus:outline-none xl:w-48 xl:flex-none">

                        <button type="submit"
                            class="ui-btn ui-btn-primary shrink-0">
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

                <div class="-mx-4 flex items-center gap-2 overflow-x-auto px-4 pb-1 text-xs sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 print:hidden" role="group"
                    aria-label="Filter status pembayaran">
                    @foreach ($statusChips as [$key, $label, $count])
                        <a href="{{ route('reports.payments', $key === '' ? $baseQuery : array_merge($baseQuery, ['status' => $key])) }}"
                            wire:click.prevent="filterStatus('{{ $key }}')"
                            @if ($statusFilter === $key) aria-current="true" @endif
                            @class([
                                'inline-flex min-h-8 items-center gap-1.5 whitespace-nowrap rounded-full border px-3 font-semibold transition-colors',
                                'border-ink bg-ink text-canvas' => $statusFilter === $key,
                                'border-ink/10 bg-surface text-muted hover:bg-canvas hover:text-ink' =>
                                    $statusFilter !== $key,
                            ])>
                            {{ $label }}
                            <span @class([
                                'rounded-full px-1.5 text-[11px] tabular-nums',
                                'bg-canvas/20' => $statusFilter === $key,
                                'bg-ink/5' => $statusFilter !== $key,
                            ])>{{ $count }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            {{-- Daftar Invoice --}}
            <div class="divide-y divide-ink/5">

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
                        class="flex flex-col gap-4 px-4 py-4 transition-colors hover:bg-surface/[0.015] sm:px-6 md:flex-row md:items-center md:justify-between">

                        {{-- Identitas Anggota --}}
                        <div class="flex min-w-0 flex-1 items-start gap-4">

                            <span
                                class="inline-flex min-w-16 shrink-0 items-center justify-center rounded-lg bg-canvas px-2.5 py-1 font-mono text-xs font-semibold tabular-nums text-muted">
                                {{ $invoice->user->student_no ?? '—' }}
                            </span>

                            <div class="flex min-w-0 flex-col gap-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="break-words text-sm font-semibold text-ink">
                                        {{ $invoice->user->name ?? 'Anggota tidak ditemukan' }}
                                    </span>

                                    <span class="text-muted">·</span>

                                    <span class="ui-tag ui-tag-plain">
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
                                <span class="font-semibold text-success">
                                    Rp{{ number_format($paidAmt, 0, ',', '.') }}
                                </span>

                                <span class="text-muted">/</span>

                                <span class="font-semibold text-ink">
                                    Rp{{ number_format($invoice->amount, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-ink/10">
                                <div class="h-full rounded-full transition-all
                            {{ $isPaid ? 'bg-success' : ($isOverdue ? 'bg-danger' : 'bg-brand') }}"
                                    style="width: {{ $pct }}%">
                                </div>
                            </div>

                            @if ($remAmt > 0)
                                <span
                                    class="text-right text-[11px] font-medium tabular-nums
                            {{ $isOverdue ? 'text-danger' : 'text-muted' }}">
                                    Sisa: Rp{{ number_format($remAmt, 0, ',', '.') }}
                                </span>
                            @endif

                        </div>

                        {{-- Status Pembayaran --}}
                        <div class="flex flex-wrap items-center gap-2 md:w-60 md:shrink-0 md:justify-end">
                            @if ($isPaid)
                                <span
                                    class="ui-tag ui-tag-success">Lunas</span>
                            @elseif ($invoice->isPartial())
                                <span
                                    class="ui-tag ui-tag-warning">Menyicil ({{ $pct }}%)</span>
                            @else
                                <span
                                    class="ui-tag ui-tag-danger">Belum Bayar</span>
                            @endif

                            @if ($isOverdue)
                                <span
                                    class="ui-tag ui-tag-danger">Terlambat {{ $invoice->overdueDays() }} hari · Wajib Lunas Penuh</span>
                            @endif
                        </div>
                    </div>

                @empty
                    <div class="px-6 py-14 text-center">
                        <div class="mx-auto mb-3 flex size-11 items-center justify-center rounded-full bg-ink/5">
                            <svg class="size-5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7">
                                <path d="M9 12l2 2 4-4M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z" />
                            </svg>
                        </div>

                        <p class="text-sm font-semibold text-ink">
                            Tidak ada data pembayaran
                        </p>

                        <p class="mt-1 text-xs text-muted">
                            Tidak ada data laporan pembayaran yang sesuai filter.
                        </p>
                    </div>
                @endforelse

            </div>

            <x-report-pager live :paginator="$invoices" label="pembayaran" />

        </section>

    @endif
    </div>
</div>
