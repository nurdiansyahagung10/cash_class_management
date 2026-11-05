<x-layouts.app title="Dashboard">
    {{-- Header Notion / Figma Style --}}
    <header class="flex flex-col gap-2.5">
        <div class="flex items-center gap-2 text-xs font-semibold text-muted">
            <span class="hover:text-black transition-colors">Kas Kelas 1A</span>
            <span class="text-black/20">/</span>
            <span class="text-black">Dashboard</span>
            <span class="text-black/20">·</span>
            <span class="font-normal">{{ now()->translatedFormat('l, d F Y') }}</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-black">Halo, {{ $user->name }}</h1>
        <p class="max-w-xl font-serif text-base sm:text-lg leading-relaxed text-muted">
            @if ($user->isAdmin())
                Ringkasan kas, tagihan, dan antrean administrasi kelas.
            @elseif ($user->canManageMembers())
                Ringkasan verifikasi anggota dan pembayaran yang perlu ditinjau.
            @else
                Pantau tagihan dan riwayat pembayaran kas kelasmu.
            @endif
        </p>
    </header>

    {{-- Notion-style Ledger Integrity Banner --}}
    <section aria-label="Status integritas ledger" class="flex flex-col gap-4 rounded-2xl border border-line bg-white p-5 shadow-xs sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-4">
            <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $brokenSeq === null ? 'bg-[#e6f3fe] text-brand border border-brand/20' : 'bg-[#fff5f5] text-[#8f1d0e] border border-[#fde4e0]' }}">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
            </div>
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2.5">
                    <h2 class="text-sm font-bold text-black">Integritas ledger: {{ $brokenSeq === null ? 'Utuh' : 'Terputus' }}</h2>
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $brokenSeq === null ? 'bg-[#dcfce7] text-[#14532d] border border-[#bbf7d0]' : 'bg-[#fde4e0] text-[#8f1d0e] border border-[#fecaca]' }}">
                        {{ $brokenSeq === null ? 'Valid' : 'Audit Error' }}
                    </span>
                </div>
                <p class="text-xs text-muted leading-relaxed">
                    {{ $brokenSeq === null ? 'Rantai hash tervalidasi.' : 'Entri pertama yang tidak cocok: #'.$brokenSeq }}
                    Data dienkripsi berurutan dengan SHA-256 dan tidak dapat dimanipulasi secara sepihak.
                </p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2.5 sm:self-center">
            <a href="{{ route('ledger') }}"
               class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-4 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Lihat ledger
            </a>
            <form method="POST" action="{{ route('ledger.verify') }}" class="inline">
                @csrf
                <button type="submit"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Periksa ledger
                </button>
            </form>
        </div>
    </section>

    {{-- Notion / Figma Stat Tiles Grid --}}
    <section aria-label="Ringkasan" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if ($user->isAdmin())
            @foreach ([
                ['Saldo kas', 'Rp'.number_format($balance, 0, ',', '.'), 'Saldo dihitung langsung dari ledger'],
                ['Anggota aktif', $activeMembers, 'Anggota yang dapat ditagih'],
                ['Tagihan belum lunas', 'Rp'.number_format($unpaidTotal, 0, ',', '.'), 'Total tagihan seluruh anggota'],
                ['Tingkat pelunasan', $paidRate.'%', 'Persentase invoice yang sudah lunas'],
                ['Periode tagihan', $periodCount, 'Periode yang pernah dibuat'],
                ['Bukti menunggu review', $pendingPayments, 'Pembayaran yang siap diperiksa'],
                ['Menunggu verifikasi anggota', $pendingMembers, 'Pendaftar aktif di antrean']
            ] as [$label, $value, $detail])
                <article class="flex min-h-28 flex-col justify-between rounded-2xl border border-line bg-white p-5 shadow-xs transition-all hover:border-black/20 hover:shadow-sm">
                    <h2 class="text-[11px] font-semibold text-muted uppercase tracking-wider">{{ $label }}</h2>
                    <p class="text-2xl sm:text-3xl font-bold tracking-tight text-black my-1 tabular-nums">{{ $value }}</p>
                    <p class="text-[11px] text-muted">{{ $detail }}</p>
                </article>
            @endforeach
        @elseif ($user->canManageMembers())
            @foreach ([
                ['Anggota aktif', $activeMembers, 'Terdaftar dan aktif'],
                ['Menunggu verifikasi anggota', $pendingMembers, 'Perlu keputusan admin'],
                ['Bukti menunggu review', $pendingPayments, 'Pembayaran siap diperiksa'],
                ['Saldo kas', 'Rp'.number_format($balance, 0, ',', '.'), 'Sumber data: ledger']
            ] as [$label, $value, $detail])
                <article class="flex min-h-28 flex-col justify-between rounded-2xl border border-line bg-white p-5 shadow-xs transition-all hover:border-black/20 hover:shadow-sm">
                    <h2 class="text-[11px] font-semibold text-muted uppercase tracking-wider">{{ $label }}</h2>
                    <p class="text-2xl sm:text-3xl font-bold tracking-tight text-black my-1 tabular-nums">{{ $value }}</p>
                    <p class="text-[11px] text-muted">{{ $detail }}</p>
                </article>
            @endforeach
        @else
            @foreach ([
                ['Tagihan belum lunas', 'Rp'.number_format($unpaidTotal, 0, ',', '.'), 'Jumlah yang perlu dibayar'],
                ['Tagihan lunas', $paidInvoices, 'Invoice yang sudah dibayar'],
                ['Bukti menunggu review', $pendingPayments, 'Bukti pembayaran yang dikirim'],
                ['Saldo kas kelas', 'Rp'.number_format($balance, 0, ',', '.'), 'Transparan dan dihitung dari ledger']
            ] as [$label, $value, $detail])
                <article class="flex min-h-28 flex-col justify-between rounded-2xl border border-line bg-white p-5 shadow-xs transition-all hover:border-black/20 hover:shadow-sm">
                    <h2 class="text-[11px] font-semibold text-muted uppercase tracking-wider">{{ $label }}</h2>
                    <p class="text-2xl sm:text-3xl font-bold tracking-tight text-black my-1 tabular-nums">{{ $value }}</p>
                    <p class="text-[11px] text-muted">{{ $detail }}</p>
                </article>
            @endforeach
        @endif
    </section>

    {{-- Kalender kas bulanan --}}
    <section aria-labelledby="kalender-kas-heading" class="flex flex-col gap-4 rounded-2xl border border-line bg-white p-5 shadow-xs sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-col gap-0.5">
                <h2 id="kalender-kas-heading" class="text-base font-bold text-black">Kalender kas · {{ $cashCalendar['month']->translatedFormat('F Y') }}</h2>
                <p class="text-xs text-muted">
                    Masuk <span class="font-semibold text-[#14532d] tabular-nums">+Rp{{ number_format($cashCalendar['totalIn'], 0, ',', '.') }}</span>
                    · Keluar <span class="font-semibold text-[#8f1d0e] tabular-nums">-Rp{{ number_format($cashCalendar['totalOut'], 0, ',', '.') }}</span>
                </p>
            </div>
            <nav aria-label="Navigasi bulan" class="flex items-center gap-1.5">
                <a href="{{ route('dashboard', ['bulan' => $cashCalendar['month']->copy()->subMonth()->format('Y-m')]) }}" aria-label="Bulan sebelumnya" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border border-line text-sm font-semibold transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">&larr;</a>
                <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line px-3.5 text-xs font-semibold transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Hari ini</a>
                <a href="{{ route('dashboard', ['bulan' => $cashCalendar['month']->copy()->addMonth()->format('Y-m')]) }}" aria-label="Bulan berikutnya" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border border-line text-sm font-semibold transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">&rarr;</a>
            </nav>
        </div>

        <div class="grid grid-cols-7 gap-px overflow-hidden rounded-xl border border-line bg-black/[0.08]" role="grid">
            @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dayName)
                <div role="columnheader" class="bg-canvas py-2.5 text-center text-[11px] font-semibold text-muted">{{ $dayName }}</div>
            @endforeach

            @foreach ($cashCalendar['weeks'] as $week)
                @foreach ($week as $cell)
                    @if ($cell === null)
                        <div class="min-h-14 bg-canvas/60 sm:min-h-20"></div>
                    @else
                        <div role="gridcell" @if ($cell['isToday']) aria-current="date" @endif
                             title="{{ \Illuminate\Support\Carbon::parse($cell['date'])->translatedFormat('d F Y') }}: masuk Rp{{ number_format($cell['in'], 0, ',', '.') }}, keluar Rp{{ number_format($cell['out'], 0, ',', '.') }}"
                             class="flex min-h-14 flex-col gap-0.5 bg-white p-1.5 transition-colors sm:min-h-20 sm:p-2 {{ $cell['in'] || $cell['out'] ? 'bg-brand-soft/30' : '' }} {{ $cell['isToday'] ? 'ring-1 ring-inset ring-brand' : '' }}">
                            <span class="flex size-5 items-center justify-center rounded-full text-[11px] font-semibold sm:size-6 sm:text-xs {{ $cell['isToday'] ? 'bg-brand text-white shadow-xs' : 'text-black' }}">{{ $cell['day'] }}</span>
                            @if ($cell['in'])
                                <span class="truncate text-[10px] font-semibold text-[#14532d] sm:text-[11px] tabular-nums">+{{ number_format($cell['in'] / 1000, 0, ',', '.') }}k</span>
                            @endif
                            @if ($cell['out'])
                                <span class="truncate text-[10px] font-semibold text-[#8f1d0e] sm:text-[11px] tabular-nums">-{{ number_format($cell['out'] / 1000, 0, ',', '.') }}k</span>
                            @endif
                        </div>
                    @endif
                @endforeach
            @endforeach
        </div>
    </section>

    {{-- Role Specific Content --}}
    @if ($user->isAdmin())
        <section aria-labelledby="periode-terbaru" class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                <div>
                    <h2 id="periode-terbaru" class="text-base font-bold text-black">Periode terbaru</h2>
                    <p class="text-xs text-muted">Pantau jumlah invoice yang sudah lunas per tagihan.</p>
                </div>
                <a href="{{ route('periods.index') }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-xs font-semibold text-brand hover:bg-brand-soft transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Kelola periode &rarr;
                </a>
            </div>
            <div class="divide-y divide-black/5">
                @forelse ($latestPeriods as $period)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 hover:bg-black/[0.02] transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-black">{{ $period->title }}</p>
                            <p class="text-xs text-muted">Jatuh tempo {{ $period->due_date->format('d M Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-black tabular-nums">Rp{{ number_format($period->amount, 0, ',', '.') }}</span>
                            <span class="rounded-full bg-black/5 px-2.5 py-0.5 text-xs font-medium text-black">
                                {{ $period->paid_count }} / {{ $period->invoices_count }} lunas
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-xs text-muted">Belum ada periode tagihan.</p>
                @endforelse
            </div>
        </section>

        <nav aria-label="Aksi admin" class="flex flex-wrap gap-3">
            <a href="{{ route('payments.review') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-xs sm:text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Review pembayaran ({{ $pendingPayments }})
            </a>
            <a href="{{ route('members.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-4 text-xs sm:text-sm font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Verifikasi anggota ({{ $pendingMembers }})
            </a>
            <a href="{{ route('categories.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-4 text-xs sm:text-sm font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Kelola kategori
            </a>
        </nav>
    @elseif ($user->canManageMembers())
        <nav aria-label="Aksi pengelola" class="flex flex-wrap gap-3">
            <a href="{{ route('members.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-xs sm:text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Lihat antrean anggota ({{ $pendingMembers }})
            </a>
            <a href="{{ route('periods.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-4 text-xs sm:text-sm font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Tagihan kelas
            </a>
            <a href="{{ route('categories.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-4 text-xs sm:text-sm font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Kategori tagihan
            </a>
        </nav>
    @else
        <div class="grid gap-6 xl:grid-cols-2">
            <section aria-labelledby="tagihan-belum-lunas" class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
                <div class="border-b border-line px-5 py-4">
                    <h2 id="tagihan-belum-lunas" class="text-base font-bold text-black">Tagihan belum lunas</h2>
                    <p class="text-xs text-muted">Pilih tagihan untuk membuka QRIS dan mengirim bukti transfer.</p>
                </div>
                <div class="divide-y divide-black/5">
                    @forelse ($unpaidInvoices as $invoice)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 hover:bg-black/[0.02] transition-colors">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-sm text-black">{{ $invoice->period->title }}</p>
                                    @if ($invoice->period->category)
                                        <span class="rounded-full bg-brand-soft px-2 py-0.5 text-[10px] font-semibold text-brand border border-brand/20">
                                            {{ $invoice->period->category->name }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-muted">Jatuh tempo {{ $invoice->period->due_date->format('d M Y') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-black tabular-nums">Rp{{ number_format($invoice->amount, 0, ',', '.') }}</span>
                                <a href="{{ route('payments.create', $invoice) }}"
                                   class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    Bayar
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-xs text-muted">Semua tagihan sudah lunas. Mantap!</p>
                    @endforelse
                </div>
            </section>

            <section aria-labelledby="riwayat-pembayaran" class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
                <div class="border-b border-line px-5 py-4">
                    <h2 id="riwayat-pembayaran" class="text-base font-bold text-black">Riwayat pembayaran</h2>
                    <p class="text-xs text-muted">Lima transaksi pembayaran terakhirmu.</p>
                </div>
                <div class="divide-y divide-black/5">
                    @forelse ($latestPayments as $payment)
                        <div class="flex items-center justify-between gap-3 px-5 py-4 hover:bg-black/[0.02] transition-colors">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-black">{{ $payment->invoice->period->title }}</p>
                                <p class="text-xs text-muted">
                                    {{ $payment->created_at->format('d M Y') }} · Rp{{ number_format($payment->amount, 0, ',', '.') }}
                                    @if ($payment->category)
                                        · <span class="text-brand font-medium">{{ $payment->category->name }}</span>
                                    @endif
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $payment->status === 'paid' ? 'bg-[#dcfce7] text-[#14532d]' : ($payment->status === 'reversed' ? 'bg-[#fde4e0] text-[#8f1d0e]' : 'bg-[#fff1d6] text-[#7a4a00]') }}">
                                {{ $payment->status === 'paid' ? 'Lunas' : ($payment->status === 'reversed' ? 'Dibatalkan' : ($payment->proof_path ? 'Menunggu verifikasi' : 'Belum dibayar')) }}
                            </span>
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-xs text-muted">Belum ada riwayat pembayaran.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif
</x-layouts.app>
