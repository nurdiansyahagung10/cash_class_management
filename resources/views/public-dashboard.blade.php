<x-layouts.public title="Dashboard publik">
    <header class="flex flex-col gap-2.5">
        <h1 class="text-3xl font-bold tracking-tight text-black sm:text-4xl">Kas kelas, terbuka untuk semua</h1>
        <p class="max-w-xl font-serif text-base leading-relaxed text-muted sm:text-lg">
            Saldo dan arus kas dihitung langsung dari ledger yang tidak bisa diubah. Masuk untuk melihat dan membayar tagihanmu.
        </p>
    </header>

    <section aria-label="Ringkasan kas" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Saldo kas', 'Rp'.number_format($balance, 0, ',', '.'), 'Dihitung dari ledger'],
            ['Anggota aktif', $activeMembers, 'Anggota yang ditagih'],
            ['Tingkat pelunasan', $paidRate.'%', 'Tagihan yang sudah lunas'],
            ['Integritas ledger', $brokenSeq === null && $anchorsIntact ? 'Utuh' : 'Terputus', match (true) {
                $brokenSeq !== null => 'Entri bermasalah: #'.$brokenSeq,
                ! $anchorsIntact => 'Anchor harian tidak cocok',
                default => 'Rantai hash SHA-256 valid',
            }],
        ] as [$label, $value, $detail])
            <article class="flex min-h-28 flex-col justify-between rounded-2xl border border-line bg-white p-5 shadow-xs">
                <h2 class="text-[11px] font-semibold uppercase tracking-wider text-muted">{{ $label }}</h2>
                <p class="my-1 text-2xl font-bold tracking-tight text-black tabular-nums sm:text-3xl">{{ $value }}</p>
                <p class="text-[11px] text-muted">{{ $detail }}</p>
            </article>
        @endforeach
    </section>

    @if ($latestAnchor)
        <section aria-labelledby="judul-anchor" class="flex flex-col gap-4 rounded-2xl bg-navy p-5 text-white shadow-xs sm:p-6">
            <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
                <div class="flex flex-col gap-1">
                    <h2 id="judul-anchor" class="text-xs font-semibold uppercase tracking-wider text-white/70">Anchor harian</h2>
                    <p class="text-sm font-semibold">{{ $latestAnchor->anchored_on->translatedFormat('d F Y') }} · entri #{{ $latestAnchor->seq }}</p>
                </div>
                <p class="font-mono text-2xl font-bold tracking-tight sm:text-3xl">{{ substr($latestAnchor->hash, 0, 4) }}…{{ substr($latestAnchor->hash, -4) }}</p>
            </div>
            <p class="break-all font-mono text-[11px] leading-relaxed text-white/60">{{ $latestAnchor->hash }}</p>
            <p class="border-t border-white/10 pt-4 text-xs leading-relaxed text-white/80">Simpan atau tangkap layar anchor ini, lalu bandingkan nanti dengan hash entri #{{ $latestAnchor->seq }} di halaman Ledger.</p>
        </section>
    @endif

    <x-cash-calendar :calendar="$cashCalendar" />
</x-layouts.public>
