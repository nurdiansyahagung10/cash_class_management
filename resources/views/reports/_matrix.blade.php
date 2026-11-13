        <section aria-labelledby="heading-matriks"
            class="flex flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-xs">
            <div class="flex flex-col gap-4 border-b border-line bg-canvas/30 px-4 py-5 sm:px-6">
                <div class="flex flex-col gap-1.5">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <h2 id="heading-matriks" class="text-base font-bold text-black sm:text-lg">Matriks Rekapitulasi Iuran Kas Siswa</h2>
                        <span
                            class="inline-flex items-center rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-semibold text-muted">Buku Pembantu Kas</span>
                        <span
                            class="inline-flex items-center rounded-full border border-brand/20 bg-brand-soft px-3 py-1 text-xs font-semibold tabular-nums text-brand">
                            {{ $matrixData['paginator']->total() }} anggota
                        </span>
                    </div>
                    <p class="max-w-xl text-xs leading-relaxed text-muted">Setoran kas setiap anggota per periode
                        tagihan. Total setor dan tunggakan dihitung dari seluruh periode.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 print:hidden">
                    <form method="GET" action="{{ route('reports.payments') }}" class="flex min-w-0 flex-1 gap-2 sm:max-w-sm">
                        <input type="hidden" name="tab" value="matrix">
                        @if (request()->boolean('all_periods'))
                            <input type="hidden" name="all_periods" value="1">
                        @endif
                        <input type="search" name="mq" value="{{ request('mq') }}" placeholder="Cari nama / NIM..."
                            class="min-h-10 min-w-0 flex-1 rounded-xl border border-black/15 bg-white px-3 text-xs text-black placeholder:text-muted/60 focus:border-brand focus:outline-none">
                        <button type="submit"
                            class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-xl bg-brand px-4 text-xs font-semibold text-white shadow-xs transition hover:bg-brand-dark">Cari</button>
                    </form>

                    @if ($matrixData['hiddenPeriods'] > 0 || request()->boolean('all_periods'))
                        <a href="{{ route('reports.payments', array_merge(request()->except(['all_periods', 'matrix_page']), request()->boolean('all_periods') ? [] : ['all_periods' => 1])) }}"
                            class="inline-flex min-h-10 items-center rounded-full border border-black/10 bg-white px-3.5 text-xs font-semibold text-muted transition-colors hover:bg-canvas hover:text-black">
                            {{ request()->boolean('all_periods') ? 'Tampilkan periode terbaru saja' : 'Tampilkan semua periode (+' . $matrixData['hiddenPeriods'] . ' lainnya)' }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="max-h-[70vh] overflow-auto">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 z-20 border-b border-line bg-canvas text-muted">
                        <tr>
                            <th scope="col"
                                class="sticky left-0 z-30 w-12 bg-canvas px-3 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider">No</th>
                            <th scope="col"
                                class="sticky left-12 z-30 min-w-44 bg-canvas px-3 py-3.5 text-[11px] font-bold uppercase tracking-wider shadow-[1px_0_0_var(--color-line)]">Anggota</th>
                            @foreach ($matrixData['periods'] as $period)
                                <th scope="col" class="min-w-32 px-3 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider">
                                    <div class="flex flex-col items-center">
                                        <span class="max-w-28 truncate font-semibold text-black" title="{{ $period->title }}">{{ $period->title }}</span>
                                        <span class="text-[10px] font-normal text-muted">Rp{{ number_format($period->amount, 0, ',', '.') }}</span>
                                    </div>
                                </th>
                            @endforeach
                            <th scope="col" class="min-w-28 px-4 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-[#14532d]">Total Setor</th>
                            <th scope="col" class="min-w-28 px-4 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-[#8f1d0e]">Tunggakan</th>
                            <th scope="col" class="min-w-24 px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse ($matrixData['rows'] as $row)
                            <tr class="group transition-colors hover:bg-black/1.5">
                                <td class="sticky left-0 z-10 bg-white px-3 py-3 text-center font-mono text-muted group-hover:bg-[#fafafa]">{{ $row['no'] }}</td>
                                <td class="sticky left-12 z-10 bg-white px-3 py-3 shadow-[1px_0_0_var(--color-line)] group-hover:bg-[#fafafa]">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-black">{{ $row['user']->name }}</span>
                                        <span class="font-mono text-[10px] text-muted">{{ $row['user']->student_no ?? '—' }} · <span class="capitalize">{{ $row['user']->role->value }}</span></span>
                                    </div>
                                </td>
                                @foreach ($matrixData['periods'] as $period)
                                    @php($cell = $row['cells'][$period->id] ?? null)
                                    <td class="px-3 py-3 text-center tabular-nums">
                                        @if (! $cell || $cell['status'] === 'none')
                                            <span class="text-muted/60">—</span>
                                        @elseif ($cell['status'] === 'paid')
                                            <span class="inline-flex flex-col items-center rounded-lg border border-[#bbf7d0] bg-[#dcfce7] px-2 py-0.5 text-[11px] font-semibold text-[#14532d]">
                                                <span>Lunas</span>
                                                <span class="font-mono text-[10px]">Rp{{ number_format($cell['paid'], 0, ',', '.') }}</span>
                                            </span>
                                        @else
                                            @php($partial = $cell['status'] === 'partial')
                                            <div class="inline-flex flex-col items-center gap-0.5">
                                                <span @class([
                                                    'inline-flex flex-col items-center rounded-lg border px-2 py-0.5 text-[11px] font-semibold',
                                                    'border-[#fed7aa] bg-[#fff1d6] text-[#7a4a00]' => $partial,
                                                    'border-[#fecaca] bg-[#fde4e0] text-[#8f1d0e]' => ! $partial,
                                                ])>
                                                    @if ($partial)
                                                        <span>Cicil Rp{{ number_format($cell['paid'], 0, ',', '.') }}</span>
                                                        <span class="text-[10px] text-[#8f1d0e]">Sisa Rp{{ number_format($cell['remaining'], 0, ',', '.') }}</span>
                                                    @else
                                                        <span>Belum Bayar</span>
                                                        @if ($cell['is_overdue'])
                                                            <span class="text-[9px] font-bold uppercase tracking-tight">Telat</span>
                                                        @endif
                                                    @endif
                                                </span>
                                                @if (auth()->user()?->isStaff() && ! empty($cell['invoice_id']))
                                                    <a href="{{ route('payments.create', $cell['invoice_id']) }}"
                                                        class="text-[10px] font-semibold text-brand hover:underline print:hidden">Bantu bayar</a>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-right font-bold tabular-nums text-[#14532d]">Rp{{ number_format($row['total_paid'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-bold tabular-nums {{ $row['total_remaining'] > 0 ? 'text-[#8f1d0e]' : 'text-muted' }}">Rp{{ number_format($row['total_remaining'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span @class([
                                        'inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold',
                                        'border-[#bbf7d0] bg-[#dcfce7] text-[#14532d]' => $row['status'] === 'Lunas',
                                        'border-[#fed7aa] bg-[#fff1d6] text-[#7a4a00]' => $row['status'] === 'Menyicil',
                                        'border-[#fecaca] bg-[#fde4e0] text-[#8f1d0e]' => ! in_array($row['status'], ['Lunas', 'Menyicil'], true),
                                    ])>{{ $row['status'] === 'Lunas' ? 'Lunas' : ($row['status'] === 'Menyicil' ? 'Menyicil' : 'Menunggak') }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($matrixData['periods']) + 5 }}" class="px-6 py-12 text-center text-xs text-muted">
                                    {{ request('mq') ? 'Tidak ada anggota yang cocok dengan pencarian.' : 'Belum ada data anggota kelas terdaftar.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="sticky bottom-0 z-20 border-t-2 border-black/10 bg-canvas text-xs font-bold">
                        <tr>
                            <td colspan="2" class="sticky left-0 z-30 bg-canvas px-3 py-3 text-right text-[11px] uppercase tracking-wider">Total semua anggota</td>
                            @foreach ($matrixData['periods'] as $period)
                                <td class="px-3 py-3 text-center font-mono text-[11px] text-[#14532d]">Rp{{ number_format($matrixData['periodTotals'][$period->id] ?? 0, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="px-4 py-3 text-right font-mono text-sm text-[#14532d]">Rp{{ number_format($matrixData['totalPaid'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-[#8f1d0e]">Rp{{ number_format($matrixData['totalRemaining'], 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <x-report-pager :paginator="$matrixData['paginator']" label="anggota" />
        </section>
