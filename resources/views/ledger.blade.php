@php
    $pills = [
        'PAYMENT_CONFIRMED' => 'bg-[#dcfce7] text-[#14532d] border border-[#bbf7d0]',
        'ACCOUNT_VERIFIED' => 'bg-brand-soft text-[#003d73] border border-brand/20',
        'PHONE_BOUND' => 'bg-brand-soft text-[#003d73] border border-brand/20',
        'INVOICE_CREATED' => 'bg-[#f1efed] text-[#3d3a37] border border-black/10',
        'REVERSAL' => 'bg-[#fde4e0] text-[#8f1d0e] border border-[#fecaca]',
    ];
    $short = fn (?string $hash) => $hash ? substr($hash, 0, 4).'…'.substr($hash, -4) : '—';
@endphp
<x-layouts.app title="Ledger publik">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex flex-col gap-2">
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-black">Ledger publik</h1>
            <p class="max-w-xl font-serif text-base sm:text-lg leading-relaxed text-muted">Setiap baris menyimpan hash baris sebelumnya. Ubah satu baris lama, dan rantai sesudahnya langsung putus.</p>
        </div>
        <div class="flex items-center gap-2.5 print:hidden">
            <a href="{{ route('ledger.export') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-line bg-white px-4 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                <svg class="size-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Unduh CSV
            </a>
            <button type="button" onclick="window.print()" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-line bg-white px-4 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                <svg class="size-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak
            </button>
        </div>
    </header>

    <section aria-label="Status integritas" class="grid gap-4 sm:grid-cols-2">
        <div class="flex flex-col justify-between gap-4 rounded-2xl bg-navy p-6 text-white shadow-xs">
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2">
                    <span class="inline-block size-2 rounded-full {{ $brokenSeq === null ? 'bg-emerald-400' : 'bg-red-400' }}"></span>
                    <span class="text-xs font-semibold uppercase tracking-wider text-white/70">{{ $brokenSeq === null ? 'Rantai utuh' : 'Rantai terputus' }}</span>
                </div>
                <span class="text-2xl sm:text-3xl font-bold tracking-tight">
                    {{ $brokenSeq === null ? "{$total} entri valid" : "Putus di entri #{$brokenSeq}" }}
                </span>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-white/10 pt-4 text-xs">
                <span class="text-white/70">Dihitung ulang setiap halaman dibuka</span>
                <form method="POST" action="{{ route('ledger.verify') }}" class="print:hidden">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-xl bg-white/15 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-white/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Audit sekarang</button>
                </form>
            </div>
        </div>
        <div class="flex flex-col justify-between gap-4 rounded-2xl bg-amber p-6 text-black shadow-xs">
            <div class="flex flex-col gap-1.5">
                <span class="text-xs font-semibold uppercase tracking-wider text-black/70">Aturan</span>
                <span class="text-xl sm:text-2xl font-bold leading-snug">Tidak ada edit, tidak ada hapus.</span>
            </div>
            <p class="border-t border-black/10 pt-4 text-xs font-medium leading-relaxed text-black/80">Salah input dikoreksi dengan entri REVERSAL baru.</p>
        </div>
    </section>

    <section aria-labelledby="judul-entri" class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-line px-6 py-5">
            <div class="flex items-center gap-2.5">
                <h2 id="judul-entri" class="text-lg font-bold text-black">Entri</h2>
                <span class="rounded-full bg-black/5 px-2.5 py-0.5 text-xs font-semibold text-muted">{{ $total }} total</span>
            </div>
            <form method="GET" action="{{ route('ledger') }}" class="flex items-center gap-2 print:hidden">
                <label for="q" class="text-xs font-semibold text-muted">Cari</label>
                <div class="relative">
                    <input id="q" type="search" name="q" value="{{ $search }}" placeholder="Tipe, isi, atau awal hash"
                           class="min-h-11 w-64 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-xs font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>
            </form>
        </div>

        <div class="divide-y divide-black/5">
            @forelse ($entries as $entry)
                @php
                    $payloadData = json_decode($entry->payload, true) ?? [];
                    $category = $payloadData['category'] ?? null;
                @endphp
                <div class="flex flex-wrap items-center gap-x-5 gap-y-3 px-6 py-4.5 hover:bg-black/[0.015] transition-colors">
                    <span class="w-14 shrink-0 font-mono text-xs font-bold text-muted">#{{ $entry->seq }}</span>
                    <span class="shrink-0 rounded-full px-3 py-1 text-[11px] font-semibold {{ $pills[$entry->type->value] ?? 'bg-[#f1efed] text-[#3d3a37]' }}">{{ $entry->type->value }}</span>
                    <div class="flex min-w-0 flex-1 basis-56 flex-col gap-1">
                        @if ($category)
                            <div class="flex items-center gap-1.5">
                                <span class="rounded-full bg-brand-soft px-2.5 py-0.5 text-[10px] font-semibold text-brand border border-brand/20">
                                    Kategori: {{ $category }}
                                </span>
                            </div>
                        @endif
                        <span class="break-all font-mono text-xs text-black/90">{{ $entry->payload }}</span>
                        <span class="text-[11px] text-muted">{{ $entry->actor?->name ?? 'Sistem' }} · {{ $entry->created_at->format('d M Y, H.i') }}</span>
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs">
                        <span class="text-muted">{{ $short($entry->prev_hash) }}</span>
                        <span aria-hidden="true" class="text-black/30">→</span>
                        <span class="sr-only">menjadi</span>
                        <span class="font-bold text-black">{{ $short($entry->hash) }}</span>
                    </div>

                    @if (auth()->user()->isAdmin() && in_array($entry->type->value, ['PAYMENT_CONFIRMED', 'EXPENSE_APPROVED']))
                        @if (in_array($entry->seq, $reversedSeqs ?? []))
                            <span class="rounded-full bg-[#fde4e0] px-2.5 py-0.5 text-xs font-semibold text-[#8f1d0e]">Dibatalkan</span>
                        @else
                            <details class="group relative print:hidden">
                                <summary aria-label="Koreksi transaksi nomor #{{ $entry->seq }}" class="cursor-pointer list-none rounded-xl border border-[#fde4e0] bg-[#fff5f5] px-3 py-1.5 text-xs font-semibold text-[#8f1d0e] transition-colors hover:bg-[#fde4e0] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#8f1d0e]">
                                    Koreksi (Reversal)
                                </summary>
                                <form method="POST" action="{{ route('ledger.reverse', $entry) }}" class="absolute right-0 top-full z-20 mt-1.5 flex w-76 flex-col gap-2.5 rounded-2xl border border-line bg-white p-4 shadow-xl">
                                    @csrf
                                    <span class="text-xs font-bold text-black">Batalkan transaksi #{{ $entry->seq }}?</span>
                                    <span class="text-[11px] text-muted leading-relaxed">Akan membuat entri REVERSAL baru di ledger untuk membatalkan saldo transaksi ini.</span>
                                    <input type="text" name="reason" placeholder="Alasan (opsional)" class="min-h-10 rounded-xl border border-black/15 px-3 text-xs focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                                    <button type="submit" class="min-h-10 rounded-xl bg-[#8f1d0e] px-3 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-[#701509] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#8f1d0e]">Konfirmasi Reversal</button>
                                </form>
                            </details>
                        @endif
                    @endif
                </div>
            @empty
                <p class="px-6 py-12 text-center text-xs text-muted">Belum ada entri.</p>
            @endforelse
        </div>

        <div class="border-t border-black/5 bg-canvas/30 px-6 py-3.5 text-[11px] font-medium text-muted">Hash sebelumnya → hash entri ini.</div>
    </section>

    <div class="print:hidden">
        {{ $entries->links() }}
    </div>
</x-layouts.app>
