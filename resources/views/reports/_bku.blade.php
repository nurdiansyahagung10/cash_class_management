        <section aria-labelledby="heading-bku"
            class="flex flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-xs">
            <div class="flex flex-col gap-4 border-b border-line bg-canvas/30 px-4 py-5 sm:px-6">
                <div class="flex flex-col gap-1.5">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <h2 id="heading-bku" class="text-base font-bold text-black sm:text-lg">Buku Kas Umum (BKU) Kas Kelas 1A</h2>
                        <span
                            class="inline-flex items-center rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-semibold text-muted">Standar Akuntansi Kas</span>
                        <span
                            class="inline-flex items-center rounded-full border border-brand/20 bg-brand-soft px-3 py-1 text-xs font-semibold tabular-nums text-brand">
                            {{ $bkuData['paginator']->total() }} transaksi
                        </span>
                    </div>
                    <p class="max-w-xl text-xs leading-relaxed text-muted">Jurnal kronologis penerimaan iuran,
                        pengeluaran, dan saldo berjalan. Halaman terakhir adalah transaksi terbaru.</p>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <div class="rounded-xl border border-line bg-white px-4 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-muted">Total Penerimaan</p>
                        <p class="text-base font-bold tabular-nums text-[#14532d]">Rp{{ number_format($bkuData['totalDebit'], 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl border border-line bg-white px-4 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-muted">Total Pengeluaran</p>
                        <p class="text-base font-bold tabular-nums text-[#8f1d0e]">Rp{{ number_format($bkuData['totalCredit'], 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl border border-brand/20 bg-brand-soft px-4 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-brand">Saldo Kas</p>
                        <p class="text-base font-bold tabular-nums text-brand">Rp{{ number_format($bkuData['endingBalance'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Mobile: kartu transaksi --}}
            <div class="divide-y divide-black/5 md:hidden">
                @forelse ($bkuData['entries'] as $entry)
                    <div class="flex flex-col gap-2 px-4 py-4">
                        <div class="flex items-center justify-between gap-3 text-[11px] text-muted">
                            <span class="font-mono">#{{ $entry['no'] }} · {{ $entry['date'] }}</span>
                            <span class="rounded bg-black/5 px-2 py-0.5 font-mono font-bold">{{ \Illuminate\Support\Str::limit($entry['ref'], 22) }}</span>
                        </div>
                        <p class="text-sm font-medium leading-snug text-black wrap-break-word">{{ $entry['description'] }}</p>
                        <div class="flex items-end justify-between gap-3">
                            <span @class([
                                'text-sm font-bold tabular-nums',
                                'text-[#14532d]' => $entry['debit'] > 0,
                                'text-[#8f1d0e]' => $entry['credit'] > 0,
                            ])>{{ $entry['debit'] > 0 ? '+ Rp' . number_format($entry['debit'], 0, ',', '.') : '− Rp' . number_format($entry['credit'], 0, ',', '.') }}</span>
                            <span class="text-right text-[11px] text-muted">Saldo <span class="font-bold tabular-nums text-black">Rp{{ number_format($entry['balance'], 0, ',', '.') }}</span></span>
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-12 text-center text-xs text-muted">Belum ada transaksi kas kelas yang tercatat.</p>
                @endforelse
            </div>

            {{-- Desktop: tabel --}}
            <div class="hidden max-h-[70vh] overflow-auto md:block">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 z-10 border-b border-line bg-canvas text-muted">
                        <tr>
                            <th scope="col" class="w-12 px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider">No</th>
                            <th scope="col" class="min-w-24 px-4 py-3.5 text-[11px] font-bold uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="min-w-28 px-4 py-3.5 text-[11px] font-bold uppercase tracking-wider">No. Bukti</th>
                            <th scope="col" class="min-w-64 px-4 py-3.5 text-[11px] font-bold uppercase tracking-wider">Uraian Transaksi</th>
                            <th scope="col" class="min-w-32 px-4 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-[#14532d]">Penerimaan</th>
                            <th scope="col" class="min-w-32 px-4 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-[#8f1d0e]">Pengeluaran</th>
                            <th scope="col" class="min-w-32 px-4 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-black">Saldo Kas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse ($bkuData['entries'] as $entry)
                            <tr class="transition-colors hover:bg-black/1.5">
                                <td class="px-4 py-3 text-center font-mono text-muted">{{ $entry['no'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-muted">{{ $entry['date'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-muted"><span class="rounded bg-black/5 px-2 py-0.5">{{ \Illuminate\Support\Str::limit($entry['ref'], 22) }}</span></td>
                                <td class="px-4 py-3 font-medium text-black">{{ $entry['description'] }}</td>
                                <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums {{ $entry['debit'] > 0 ? 'text-[#14532d]' : 'text-muted/40' }}">{{ $entry['debit'] > 0 ? 'Rp' . number_format($entry['debit'], 0, ',', '.') : '—' }}</td>
                                <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums {{ $entry['credit'] > 0 ? 'text-[#8f1d0e]' : 'text-muted/40' }}">{{ $entry['credit'] > 0 ? 'Rp' . number_format($entry['credit'], 0, ',', '.') : '—' }}</td>
                                <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-black">Rp{{ number_format($entry['balance'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-xs text-muted">Belum ada transaksi kas kelas yang tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-report-pager :paginator="$bkuData['paginator']" label="transaksi" />
        </section>
