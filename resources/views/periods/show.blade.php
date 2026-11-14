<x-layouts.app title="Rekap {{ $period->title }}">
    <div class="flex flex-col gap-6">
        <div class="flex items-center gap-2 text-xs font-semibold">
            <a href="{{ route('periods.index') }}"
                class="inline-flex items-center gap-1.5 text-muted transition-colors hover:text-black">
                <span>&larr;</span>
                <span>Kembali ke periode tagihan</span>
            </a>
        </div>

        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand">Rekap Pembayaran</span>
                    <span
                        class="rounded-full bg-brand-soft px-2.5 py-0.5 text-xs font-semibold text-brand border border-brand/20">
                        {{ $period->category->name ?? 'Uang Kas' }}
                    </span>
                    @if ($period->qris_url)
                        @php
                            $isDynamic = str_starts_with($period->qris_url, '000201');
                        @endphp
                        <span
                            class="rounded-full {{ $isDynamic ? 'bg-[#dcfce7] text-[#14532d] border border-[#bbf7d0]' : 'bg-[#fff1d6] text-[#7a4a00] border border-[#fed7aa]' }} px-2.5 py-0.5 text-xs font-semibold">
                            {{ $isDynamic ? 'QRIS Dinamis Aktif' : 'QRIS Statis' }}
                        </span>
                    @endif
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-black">{{ $period->title }}</h1>
                <p class="text-xs sm:text-sm text-muted">Jatuh tempo: {{ $period->due_date->format('d M Y') }} ·
                    Tagihan: <span
                        class="font-semibold text-black tabular-nums">Rp{{ number_format($period->amount, 0, ',', '.') }}</span>
                    / anggota</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($period->canBeCancelled())
                    <form method="POST" action="{{ route('periods.destroy', $period) }}" class="print:hidden"
                        onsubmit="return confirm('Batalkan tagihan ini? Semua tagihan anggota akan dihapus.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex min-h-11 items-center rounded-xl bg-[#fde4e0] px-4 text-xs font-semibold text-[#8f1d0e] transition-colors hover:bg-[#fbd0c9]">
                            Batalkan Tagihan
                        </button>
                    </form>
                @endif
                <button type="button" onclick="window.print()"
                    class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-line bg-white px-4 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-black/5 print:hidden">
                    <svg class="size-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Rekap
                </button>
            </div>
        </header>

        <section aria-label="Statistik rekap" class="grid gap-4 sm:grid-cols-3">
            <div class="flex flex-col gap-1 rounded-2xl border border-line bg-white p-5 shadow-xs">
                <span class="text-xs font-semibold uppercase tracking-wider text-muted">Dana Terkumpul</span>
                <span
                    class="text-2xl sm:text-3xl font-bold tracking-tight  tabular-nums my-1">Rp{{ number_format($collectedAmount, 0, ',', '.') }}</span>
                <span class="text-xs text-muted">dari target <span
                        class="font-medium text-black tabular-nums">Rp{{ number_format($totalAmount, 0, ',', '.') }}</span></span>
            </div>
            <div class="flex flex-col gap-1 rounded-2xl border border-line bg-white p-5 shadow-xs">
                <span class="text-xs font-semibold uppercase tracking-wider text-muted">Sudah Lunas</span>
                <span class="text-2xl sm:text-3xl font-bold tracking-tight  tabular-nums my-1">{{ $paidCount }}
                    anggota</span>
                <span class="text-xs text-muted"><span
                        class="font-medium text-black tabular-nums">{{ $totalCount > 0 ? round(($paidCount / $totalCount) * 100) : 0 }}%</span>
                    dari total siswa</span>
            </div>
            <div class="flex flex-col gap-1 rounded-2xl border border-line bg-white p-5 shadow-xs">
                <span class="text-xs font-semibold uppercase tracking-wider text-muted">Belum Membayar</span>
                <span class="text-2xl sm:text-3xl font-bold tracking-tight  tabular-nums my-1">{{ $unpaidCount }}
                    anggota</span>
                <span class="text-xs text-muted">Menunggu pembayaran</span>
            </div>
        </section>

        @if ($filter !== 'paid' && $unpaidInvoices->isNotEmpty())
            <section aria-labelledby="judul-pengingat"
                class="flex flex-col rounded-2xl border border-line bg-white shadow-xs print:hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-6 py-4.5">
                    <div class="flex flex-col gap-0.5">
                        <h2 id="judul-pengingat" class="text-lg font-bold text-black">Pengingat pembayaran</h2>
                        <p class="text-xs text-muted">Salin pesan lalu kirim sendiri lewat WhatsApp.</p>
                    </div>
                    <button type="button" data-copy-text="{{ $reminder->groupMessage($period, $unpaidInvoices) }}"
                        class="inline-flex min-h-11 items-center gap-1.5 rounded-xl bg-brand px-4 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        <span data-copy-label>Salin pesan grup</span>
                    </button>
                </div>
                <p data-copy-status role="status" aria-live="polite" class="sr-only"></p>

                <ul class="divide-y divide-black/5">
                    @foreach ($unpaidInvoices as $invoice)
                        @php
                            $personalUrl = $reminder->whatsAppUrl($invoice);
                        @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-3">
                            <span class="text-sm font-semibold text-black">{{ $invoice->user->name }}
                                <span class="font-normal text-muted tabular-nums">· sisa
                                    Rp{{ number_format($invoice->remainingAmount(), 0, ',', '.') }}</span></span>
                            <span class="flex flex-wrap items-center gap-2">
                                <button type="button"
                                    data-copy-text="{{ $reminder->personalMessage($invoice) }}"
                                    class="inline-flex min-h-11 items-center rounded-xl border border-line bg-canvas/60 px-3.5 text-xs font-semibold text-black transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    <span data-copy-label>Salin pesan</span>
                                </button>
                                @if ($personalUrl)
                                    <a href="{{ $personalUrl }}" target="_blank" rel="noopener"
                                        class="inline-flex min-h-11 items-center rounded-xl border border-line bg-canvas/60 px-3.5 text-xs font-semibold text-black transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Kirim
                                        WA</a>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section aria-labelledby="judul-daftar" class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-6 py-4.5">
                <h2 id="judul-daftar" class="text-lg font-bold text-black">Daftar Tagihan Anggota
                    ({{ $invoices->count() }})</h2>

                <div class="flex flex-wrap items-center gap-1.5 print:hidden">
                    <a href="{{ route('periods.show', $period) }}"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ empty($filter) ? 'bg-black text-white shadow-xs' : 'text-muted hover:bg-canvas' }}">Semua</a>
                    <a href="{{ route('periods.show', [$period, 'status' => 'unpaid']) }}"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $filter === 'unpaid' ? 'bg-[#8f1d0e] text-white shadow-xs' : 'text-muted hover:bg-canvas' }}">Belum
                        Bayar ({{ $unpaidCount }})</a>
                    <a href="{{ route('periods.show', [$period, 'status' => 'partial']) }}"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $filter === 'partial' ? 'bg-[#7a4a00] text-white shadow-xs' : 'text-muted hover:bg-canvas' }}">Menyicil
                        ({{ $partialCount ?? 0 }})</a>
                    <a href="{{ route('periods.show', [$period, 'status' => 'paid']) }}"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $filter === 'paid' ? 'bg-[#14532d] text-white shadow-xs' : 'text-muted hover:bg-canvas' }}">Lunas
                        ({{ $paidCount }})</a>
                </div>
            </div>

            <div class="divide-y divide-black/5">
                @forelse ($invoices as $invoice)
                    @php
                        $payment = $invoice->payments->first();
                        $paidAmt = $invoice->paidAmount();
                        $remAmt = $invoice->remainingAmount();
                        $isOverdue = $invoice->isOverdue();
                        $waUrl =
                            $reminder->whatsAppUrl($invoice) ??
                            'https://wa.me/?text=' . rawurlencode($reminder->personalMessage($invoice));
                    @endphp
                    <div
                        class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 hover:bg-black/[0.015] transition-colors">
                        <div class="flex items-center gap-4">
                            <span
                                class="inline-flex min-w-16 shrink-0 items-center justify-center rounded-lg border border-black/10 bg-canvas px-2.5 py-1 font-mono text-xs font-bold text-muted tabular-nums">{{ $invoice->user->student_no ?? '—' }}</span>
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-black">{{ $invoice->user->name }}</span>
                                <span class="text-xs text-muted capitalize">
                                    {{ $invoice->user->role->value }}
                                    @if ($invoice->isPartial())
                                        · Terbayar Rp{{ number_format($paidAmt, 0, ',', '.') }} (Sisa
                                        Rp{{ number_format($remAmt, 0, ',', '.') }})
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            @if ($invoice->isPaid())
                                <span
                                    class="rounded-full bg-[#dcfce7] px-2.5 py-0.5 text-xs font-semibold text-[#14532d] border border-[#bbf7d0]">Lunas</span>
                                @if ($payment?->proof_path)
                                    <a href="{{ route('payments.proof.show', $payment) }}" target="_blank"
                                        rel="noopener"
                                        class="text-xs font-semibold text-brand hover:underline print:hidden">Lihat
                                        bukti</a>
                                @endif
                            @elseif ($invoice->isPartial())
                                <span
                                    class="rounded-full bg-[#fff1d6] px-2.5 py-0.5 text-xs font-semibold text-[#7a4a00] border border-[#fed7aa]">Menyicil</span>
                                @if ($isOverdue)
                                    <span
                                        class="rounded-full bg-[#fde4e0] px-2.5 py-0.5 text-xs font-semibold text-[#8f1d0e] border border-[#fecaca]">Lewat
                                        Tenggat</span>
                                @endif
                                <a href="{{ $waUrl }}" target="_blank" rel="noopener"
                                    class="inline-flex min-h-11 items-center gap-1.5 rounded-xl border border-line bg-canvas/60 px-3.5 text-xs font-semibold text-black transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand print:hidden">
                                    Ingatkan WA
                                </a>
                                @if (auth()->user()?->isStaff())
                                    <a href="{{ route('payments.create', $invoice) }}"
                                        class="inline-flex min-h-11 items-center gap-1.5 rounded-xl bg-brand px-3.5 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand print:hidden">
                                        Bantu Bayar
                                    </a>
                                @endif
                            @else
                                <span
                                    class="rounded-full bg-[#fde4e0] px-2.5 py-0.5 text-xs font-semibold text-[#8f1d0e] border border-[#fecaca]">
                                    {{ $isOverdue ? 'Belum Bayar · Lewat Tenggat' : 'Belum Bayar' }}
                                </span>
                                <a href="{{ $waUrl }}" target="_blank" rel="noopener"
                                    class="inline-flex min-h-11 items-center gap-1.5 rounded-xl border border-line bg-canvas/60 px-3.5 text-xs font-semibold text-black transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand print:hidden">
                                    Ingatkan WA
                                </a>
                                @if (auth()->user()?->isStaff())
                                    <a href="{{ route('payments.create', $invoice) }}"
                                        class="inline-flex min-h-11 items-center gap-1.5 rounded-xl bg-brand px-3.5 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand print:hidden">
                                        Bantu Bayar
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-12 text-center text-xs text-muted">Tidak ada data tagihan.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
