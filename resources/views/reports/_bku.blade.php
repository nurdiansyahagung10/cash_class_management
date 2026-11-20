        @php
            $rupiah = fn (int $value) => 'Rp' . number_format($value, 0, ',', '.');
        @endphp
        <section aria-labelledby="heading-bku" class="ui-card flex flex-col overflow-hidden">
            <div class="flex flex-col gap-4 border-b border-line px-4 py-4 sm:px-6">
                <div class="flex min-w-0 flex-col gap-1">
                    <h2 id="heading-bku" class="text-base font-semibold text-ink">Buku kas umum</h2>
                    <p class="max-w-xl text-xs leading-relaxed text-muted">
                        Standar Akuntansi Kas · <span class="tabular-nums">{{ $bkuData['paginator']->total() }}</span> transaksi.
                        Jurnal penerimaan iuran, pengeluaran, dan saldo berjalan; halaman terakhir berisi transaksi terbaru.
                    </p>
                </div>

                {{-- Totals: one quiet row, color only on the sign --}}
                <dl class="grid grid-cols-1 divide-y divide-line rounded-lg border border-line sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    <div class="flex items-baseline justify-between gap-3 px-3.5 py-2.5 sm:flex-col sm:items-start sm:gap-0.5">
                        <dt class="text-xs text-muted">Total penerimaan</dt>
                        <dd class="text-sm font-semibold tabular-nums text-ink"><span class="text-success">+</span> {{ $rupiah($bkuData['totalDebit']) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3 px-3.5 py-2.5 sm:flex-col sm:items-start sm:gap-0.5">
                        <dt class="text-xs text-muted">Total pengeluaran</dt>
                        <dd class="text-sm font-semibold tabular-nums text-ink"><span class="text-danger">−</span> {{ $rupiah($bkuData['totalCredit']) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3 bg-surface-2 px-3.5 py-2.5 sm:flex-col sm:items-start sm:gap-0.5">
                        <dt class="text-xs text-muted">Saldo kas</dt>
                        <dd class="text-sm font-semibold tabular-nums text-ink">{{ $rupiah($bkuData['endingBalance']) }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Mobile: one compact row per transaction --}}
            <ul class="divide-y divide-line md:hidden">
                @forelse ($bkuData['entries'] as $entry)
                    <li class="flex flex-col gap-1.5 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <p class="min-w-0 text-[13px] font-medium leading-snug text-ink wrap-break-word">{{ $entry['description'] }}</p>
                            <span @class([
                                'shrink-0 text-[13px] font-medium tabular-nums',
                                'text-success' => $entry['debit'] > 0,
                                'text-danger' => $entry['credit'] > 0,
                            ])>{{ $entry['debit'] > 0 ? '+' . $rupiah($entry['debit']) : '−' . $rupiah($entry['credit']) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-[11px] text-muted">
                            <span class="min-w-0 truncate"><span class="font-mono">{{ $entry['date'] }}</span> · <span class="font-mono">{{ \Illuminate\Support\Str::limit($entry['ref'], 22) }}</span></span>
                            <span class="shrink-0">Saldo <span class="tabular-nums text-body">{{ $rupiah($entry['balance']) }}</span></span>
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-12 text-center text-xs text-muted">Belum ada transaksi kas kelas yang tercatat.</li>
                @endforelse
            </ul>

            {{-- Desktop: journal table --}}
            <div class="hidden max-h-[70vh] overflow-auto [scrollbar-width:thin] md:block">
                <table class="w-full border-separate border-spacing-0 text-left text-xs">
                    <thead class="text-muted">
                        <tr class="[&>th]:sticky [&>th]:top-0 [&>th]:z-10 [&>th]:border-b [&>th]:border-line [&>th]:bg-surface-2 [&>th]:px-3 [&>th]:py-2.5 [&>th]:text-[10px] [&>th]:font-semibold [&>th]:uppercase [&>th]:tracking-wider">
                            <th scope="col" class="w-10 pl-5! text-center">No</th>
                            <th scope="col" class="min-w-24">Tanggal</th>
                            <th scope="col" class="min-w-28">No. bukti</th>
                            <th scope="col" class="min-w-64">Uraian</th>
                            <th scope="col" class="min-w-28 text-right">Penerimaan</th>
                            <th scope="col" class="min-w-28 text-right">Pengeluaran</th>
                            <th scope="col" class="min-w-28 pr-5! text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bkuData['entries'] as $entry)
                            <tr class="transition-colors hover:bg-surface-2 [&>td]:border-b [&>td]:border-line [&>td]:px-3 [&>td]:py-2.5">
                                <td class="pl-5! text-center font-mono text-[11px] text-faint">{{ $entry['no'] }}</td>
                                <td class="whitespace-nowrap font-mono text-[11px] text-muted">{{ $entry['date'] }}</td>
                                <td class="whitespace-nowrap font-mono text-[11px] text-muted">{{ \Illuminate\Support\Str::limit($entry['ref'], 22) }}</td>
                                <td class="text-[13px] text-ink">{{ $entry['description'] }}</td>
                                <td class="text-right tabular-nums {{ $entry['debit'] > 0 ? 'text-success' : 'text-faint' }}">{{ $entry['debit'] > 0 ? $rupiah($entry['debit']) : '—' }}</td>
                                <td class="text-right tabular-nums {{ $entry['credit'] > 0 ? 'text-danger' : 'text-faint' }}">{{ $entry['credit'] > 0 ? $rupiah($entry['credit']) : '—' }}</td>
                                <td class="pr-5! text-right font-medium tabular-nums text-ink">{{ $rupiah($entry['balance']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-xs text-muted">Belum ada transaksi kas kelas yang tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-report-pager live :paginator="$bkuData['paginator']" label="transaksi" />
        </section>
