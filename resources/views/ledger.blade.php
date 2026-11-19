@php
    $pills = [
        'PAYMENT_CONFIRMED' => 'bg-surface-2 text-success border border-line',
        'ACCOUNT_VERIFIED' => 'bg-brand-soft text-info border border-brand/20',
        'PHONE_BOUND' => 'bg-brand-soft text-info border border-brand/20',
        'INVOICE_CREATED' => 'bg-surface-2 text-body border border-ink/10',
        'REVERSAL' => 'bg-surface-2 text-danger border border-line',
    ];
    $short = fn(?string $hash) => $hash ? substr($hash, 0, 4) . '…' . substr($hash, -4) : '—';
@endphp
<x-dynamic-component :component="$isGuest ? 'layouts.public' : 'layouts.app'" title="Ledger publik">
    <x-ui.page-header :eyebrow="'Keuangan · ' . now()->translatedFormat('l, d F Y')" title="Ledger publik"
        description="Catatan kas berantai hash: setiap entri terkunci berurutan, koreksi lewat entri reversal.">
        <x-slot:actions>
            <div class="flex items-center gap-2.5 print:hidden">
            @unless ($isGuest)
                <a href="{{ route('ledger.export') }}"
                    class="ui-btn ui-btn-outline gap-2">
                    <svg class="size-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh CSV
                </a>
            @endunless
            <button type="button" onclick="window.print()"
                class="ui-btn ui-btn-outline gap-2">
                <svg class="size-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak
            </button>
        </div>
        </x-slot:actions>
    </x-ui.page-header>

    <section aria-label="Status integritas" class="grid gap-4 sm:grid-cols-2">
        <div class="flex flex-col justify-between gap-4 rounded-2xl border border-line bg-surface-2 p-6 text-ink shadow-xs">
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2">
                    <span
                        class="inline-block size-2 rounded-full {{ $brokenSeq === null ? 'bg-emerald-400' : 'bg-red-400' }}"></span>
                    <span
                        class="text-xs font-semibold uppercase tracking-wider text-muted">{{ $brokenSeq === null ? 'Rantai utuh' : 'Rantai terputus' }}</span>
                </div>
                <span class="text-2xl sm:text-3xl font-semibold tracking-tight">
                    {{ $brokenSeq === null ? "{$total} entri valid" : "Putus di entri #{$brokenSeq}" }}
                </span>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-ink/10 pt-4 text-xs">
                <span class="text-muted">Dihitung ulang setiap halaman dibuka</span>
                @unless ($isGuest)
                    <form method="POST" action="{{ route('ledger.verify') }}" class="print:hidden">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center rounded-xl bg-ink/15 px-3 py-1.5 text-xs font-semibold text-ink transition-colors hover:bg-ink/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Audit
                            sekarang</button>
                    </form>
                @endunless
            </div>
        </div>
        <div class="flex flex-col justify-between gap-4 rounded-2xl border border-line bg-surface p-6 text-ink shadow-xs">
            <div class="flex flex-col gap-1.5">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber">Aturan</span>
                <span class="text-xl sm:text-2xl font-semibold leading-snug">Tidak ada edit, tidak ada hapus.</span>
            </div>
            <p class="border-t border-line pt-4 text-xs font-medium leading-relaxed text-muted">Salah input
                dikoreksi dengan entri REVERSAL baru.</p>
        </div>
    </section>

    <section aria-labelledby="judul-entri" class="flex flex-col rounded-2xl border border-line bg-surface shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-line px-6 py-5">
            <div class="flex items-center gap-2.5">
                <h2 id="judul-entri" class="text-lg font-semibold text-ink">Entri</h2>
                <span class="ui-tag ui-tag-plain">{{ $total }}
                    total</span>
            </div>
            <form method="GET" action="{{ route('ledger') }}" class="flex items-center gap-2 print:hidden">
                <label for="q" class="text-xs font-semibold text-muted">Cari</label>
                <div class="relative">
                    <input id="q" type="search" name="q" value="{{ $search }}"
                        placeholder="{{ $isGuest ? 'Tipe atau awal hash' : 'Tipe, isi, atau awal hash' }}"
                        class="min-h-9 w-64 rounded-xl border border-line bg-canvas/30 px-3 text-xs font-normal text-ink placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">
                </div>
            </form>
        </div>

        <div class="divide-y divide-ink/5">
            @forelse ($entries as $entry)
                @php
                    $payloadData = json_decode($entry->payload, true) ?? [];
                    $category = $payloadData['category'] ?? null;
                @endphp
                <div
                    class="flex flex-wrap items-center gap-x-5 gap-y-3 px-6 py-4.5 hover:bg-surface/[0.015] transition-colors">
                    <span class="w-14 shrink-0 font-mono text-xs font-semibold text-muted">#{{ $entry->seq }}</span>
                    <span
                        class="shrink-0 rounded-full px-3 py-1 text-[11px] font-semibold {{ $pills[$entry->type->value] ?? 'bg-surface-2 text-body' }}">{{ $entry->type->value }}</span>
                    <div class="flex min-w-0 flex-1 basis-56 flex-col gap-1">
                        @if ($category)
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="ui-tag ui-tag-info">
                                    Kategori: {{ $category }}
                                </span>
                            </div>
                        @endif
                        <span
                            class="break-all font-mono text-xs text-ink/90">{{ $isGuest ? $entry->publicPayload() : $entry->payload }}</span>
                        <span
                            class="text-[11px] text-muted">{{ $isGuest ? ($entry->actor_id ? 'Pengurus kelas' : 'Sistem') : $entry->actor?->name ?? 'Sistem' }}
                            ·
                            {{ $entry->created_at->format('d M Y, H.i') }}</span>
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs">
                        <span class="text-muted">{{ $short($entry->prev_hash) }}</span>
                        <span aria-hidden="true" class="text-ink/30">→</span>
                        <span class="sr-only">menjadi</span>
                        <span class="font-semibold text-ink">{{ $short($entry->hash) }}</span>
                    </div>

                    @if (auth()->user()?->isAdmin() && in_array($entry->type->value, ['PAYMENT_CONFIRMED', 'EXPENSE_APPROVED']))
                        @if (in_array($entry->seq, $reversedSeqs ?? []))
                            <span
                                class="ui-tag ui-tag-danger">Dibatalkan</span>
                        @else
                            <details class="group relative print:hidden">
                                <summary aria-label="Koreksi transaksi nomor #{{ $entry->seq }}"
                                    class="cursor-pointer list-none rounded-xl border border-line bg-surface-2 px-3 py-1.5 text-xs font-semibold text-danger transition-colors hover:bg-danger/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger">
                                    Koreksi (Reversal)
                                </summary>
                                <form method="POST" action="{{ route('ledger.reverse', $entry) }}"
                                    class="absolute right-0 top-full z-20 mt-1.5 flex w-76 flex-col gap-2.5 rounded-2xl border border-line bg-surface p-4 shadow-xl">
                                    @csrf
                                    <span class="text-xs font-semibold text-ink">Batalkan transaksi
                                        #{{ $entry->seq }}?</span>
                                    <span class="text-[11px] text-muted leading-relaxed">Akan membuat entri REVERSAL
                                        baru di ledger untuk membatalkan saldo transaksi ini.</span>
                                    <input type="text" name="reason" placeholder="Alasan (opsional)"
                                        class="min-h-10 rounded-xl border border-line px-3 text-xs focus:border-line-strong focus:outline-none focus:ring-2 focus:ring-ink/5">
                                    <button type="submit"
                                        class="ui-btn ui-btn-danger">Konfirmasi
                                        Reversal</button>
                                </form>
                            </details>
                        @endif
                    @endif
                </div>
            @empty
                <p class="px-6 py-12 text-center text-xs text-muted">Belum ada entri.</p>
            @endforelse
        </div>

        <div class="border-t border-ink/5 bg-canvas/30 px-6 py-3.5 text-[11px] font-medium text-muted">Hash sebelumnya
            → hash entri ini.</div>
    </section>

    <div class="print:hidden">
        {{ $entries->links() }}
    </div>
</x-dynamic-component>
