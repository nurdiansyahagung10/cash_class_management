        @php
            $w = $matrixData['periodWindow'];
            $windowUrl = fn (int $page) => route('reports.payments', array_merge($query, ['tab' => 'matrix', 'period_page' => $page]));
            $hasMatrixFilter = isset($query['mq']) || isset($query['m_category']) || isset($query['m_cols']);
            $selectedMatrixCategory = $query['m_category'] ?? '';
            $selectedColumns = (int) ($w['size'] ?? \App\Http\Controllers\PaymentReportController::MATRIX_DEFAULT_WINDOW);
        @endphp
        <section aria-labelledby="heading-matriks" class="ui-card flex flex-col overflow-hidden">
            <div class="flex flex-col gap-4 border-b border-line px-4 py-4 sm:px-6">
                <div class="flex flex-col gap-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 id="heading-matriks" class="text-base font-semibold text-ink">Matriks Rekapitulasi Iuran Kas Siswa</h2>
                        <x-ui.badge>Buku Pembantu Kas</x-ui.badge>
                        <x-ui.badge class="font-mono tabular-nums">{{ $matrixData['paginator']->total() }} anggota</x-ui.badge>
                    </div>
                    <p class="max-w-xl text-xs leading-relaxed text-muted">Setoran tiap anggota per periode. Kolom periode ditampilkan per jendela; total setor dan tunggakan dihitung dari semua periode yang cocok dengan filter.</p>
                </div>

                <form method="GET" action="{{ route('reports.payments') }}" wire:submit.prevent="$refresh" class="grid grid-cols-2 gap-2 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_7.5rem_auto] md:items-center print:hidden" role="search" aria-label="Filter matriks">
                    <input type="hidden" name="tab" value="matrix">
                    <div class="col-span-2 flex min-w-0 gap-2 md:col-span-1">
                        <input type="search" name="mq" value="{{ $query['mq'] ?? '' }}" wire:model.live.debounce.400ms="matrixSearch" placeholder="Cari nama / NIM..." aria-label="Cari anggota"
                            class="min-h-10 min-w-0 flex-1 rounded-lg border border-line bg-surface px-3 text-[13px] text-ink shadow-2xs placeholder:text-muted/70 focus:border-line-strong focus:outline-none focus:ring-3 focus:ring-ink/5">
                        <button type="submit" class="ui-btn ui-btn-primary shrink-0">Cari</button>
                    </div>
                    {{-- wire:key follows the value so a reset swaps in a fresh, correctly selected Tom Select --}}
                    <div wire:ignore wire:key="m-category-{{ $selectedMatrixCategory }}" class="min-w-0">
                        <select name="m_category" data-tom-select wire:model.live="matrixCategory" aria-label="Filter kategori periode">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected((string) $selectedMatrixCategory === (string) $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div wire:ignore wire:key="m-cols-{{ $selectedColumns }}" class="min-w-0">
                        <select name="m_cols" data-tom-select wire:model.live="matrixColumns" aria-label="Jumlah kolom periode">
                            @foreach ($matrixWindowSizes as $size)
                                <option value="{{ $size === \App\Http\Controllers\PaymentReportController::MATRIX_DEFAULT_WINDOW ? '' : $size }}" @selected($selectedColumns === $size)>{{ $size }} kolom</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($hasMatrixFilter)
                        <a href="{{ route('reports.payments', ['tab' => 'matrix']) }}" wire:click.prevent="resetMatrixFilters" class="ui-btn ui-btn-ghost col-span-2 text-muted md:col-span-1">Reset</a>
                    @endif
                </form>

                @if ($w && $w['pages'] > 1)
                    <div class="flex flex-col gap-2 rounded-lg border border-line bg-canvas p-2 sm:flex-row sm:items-center print:hidden" role="group" aria-label="Geser jendela periode">
                        <div class="flex items-center gap-1.5">
                            <a href="{{ $w['page'] < $w['pages'] ? $windowUrl($w['page'] + 1) : '#' }}" wire:click.prevent="showPeriodWindow({{ min($w['pages'], $w['page'] + 1) }})" aria-label="Periode lebih lama" @class([
                                'ui-btn ui-btn-outline size-10 px-0',
                                'pointer-events-none opacity-40' => $w['page'] >= $w['pages'],
                            ])>
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" /></svg>
                            </a>
                            <span class="min-w-32 flex-1 text-center text-xs tabular-nums text-muted sm:flex-none">Periode <span class="font-medium text-ink">{{ $w['from'] }}–{{ $w['to'] }}</span> dari {{ $w['total'] }}</span>
                            <a href="{{ $w['page'] > 1 ? $windowUrl($w['page'] - 1) : '#' }}" wire:click.prevent="showPeriodWindow({{ max(1, $w['page'] - 1) }})" aria-label="Periode lebih baru" @class([
                                'ui-btn ui-btn-outline size-10 px-0',
                                'pointer-events-none opacity-40' => $w['page'] <= 1,
                            ])>
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6" /></svg>
                            </a>
                        </div>
                        {{-- Options depend on category + column count, so the key changes with them (and with the page) --}}
                        <div wire:ignore wire:key="window-{{ $selectedMatrixCategory }}-{{ $selectedColumns }}-{{ $w['page'] }}" class="min-w-0 flex-1">
                            <select name="period_page" data-tom-select wire:model.live="periodWindow" aria-label="Lompat ke jendela periode">
                                @foreach ($w['options'] as $option)
                                    <option value="{{ $option['page'] === 1 ? '' : $option['page'] }}" @selected($option['page'] === $w['page'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            <div class="hidden max-h-[70vh] overflow-auto md:block">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 z-20 border-b border-line bg-canvas text-muted">
                        <tr>
                            <th scope="col"
                                class="sticky left-0 z-30 w-12 bg-canvas px-3 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider">No</th>
                            <th scope="col"
                                class="sticky left-12 z-30 min-w-44 bg-canvas px-3 py-3.5 text-[11px] font-semibold uppercase tracking-wider shadow-[1px_0_0_var(--color-line)]">Anggota</th>
                            @foreach ($matrixData['periods'] as $period)
                                <th scope="col" class="min-w-32 px-3 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider">
                                    <div class="flex flex-col items-center">
                                        <span class="max-w-28 truncate font-semibold text-ink" title="{{ $period->title }}">{{ $period->title }}</span>
                                        <span class="text-[10px] font-normal text-muted">Rp{{ number_format($period->amount, 0, ',', '.') }}</span>
                                    </div>
                                </th>
                            @endforeach
                            <th scope="col" class="min-w-28 px-4 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-success">Total Setor</th>
                            <th scope="col" class="min-w-28 px-4 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-danger">Tunggakan</th>
                            <th scope="col" class="min-w-24 px-4 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink/5">
                        @forelse ($matrixData['rows'] as $row)
                            <tr class="group transition-colors hover:bg-ink/1.5">
                                <td class="sticky left-0 z-10 bg-surface px-3 py-3 text-center font-mono text-muted group-hover:bg-surface-2">{{ $row['no'] }}</td>
                                <td class="sticky left-12 z-10 bg-surface px-3 py-3 shadow-[1px_0_0_var(--color-line)] group-hover:bg-surface-2">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-ink">{{ $row['user']->name }}</span>
                                        <span class="font-mono text-[10px] text-muted">{{ $row['user']->student_no ?? '—' }} · <span class="capitalize">{{ $row['user']->role->value }}</span></span>
                                    </div>
                                </td>
                                @foreach ($matrixData['periods'] as $period)
                                    @php($cell = $row['cells'][$period->id] ?? null)
                                    <td class="px-3 py-3 text-center tabular-nums">
                                        @if (! $cell || $cell['status'] === 'none')
                                            <span class="text-muted/60">—</span>
                                        @elseif ($cell['status'] === 'paid')
                                            <span class="inline-flex flex-col items-center rounded-lg border border-line bg-surface-2 px-2 py-0.5 text-[11px] font-semibold text-success">
                                                <span>Lunas</span>
                                                <span class="font-mono text-[10px]">Rp{{ number_format($cell['paid'], 0, ',', '.') }}</span>
                                            </span>
                                        @else
                                            @php($partial = $cell['status'] === 'partial')
                                            <div class="inline-flex flex-col items-center gap-0.5">
                                                <span @class([
                                                    'inline-flex flex-col items-center rounded-lg border px-2 py-0.5 text-[11px] font-semibold',
                                                    'border-line bg-surface-2 text-warning' => $partial,
                                                    'border-line bg-surface-2 text-danger' => ! $partial,
                                                ])>
                                                    @if ($partial)
                                                        <span>Cicil Rp{{ number_format($cell['paid'], 0, ',', '.') }}</span>
                                                        <span class="text-[10px] text-danger">Sisa Rp{{ number_format($cell['remaining'], 0, ',', '.') }}</span>
                                                    @else
                                                        <span>Belum Bayar</span>
                                                        @if ($cell['is_overdue'])
                                                            <span class="text-[9px] font-semibold uppercase tracking-tight">Telat</span>
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
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-success">Rp{{ number_format($row['total_paid'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $row['total_remaining'] > 0 ? 'text-danger' : 'text-muted' }}">Rp{{ number_format($row['total_remaining'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span @class([
                                        'inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold',
                                        'border-line bg-surface-2 text-success' => $row['status'] === 'Lunas',
                                        'border-line bg-surface-2 text-warning' => $row['status'] === 'Menyicil',
                                        'border-line bg-surface-2 text-danger' => ! in_array($row['status'], ['Lunas', 'Menyicil'], true),
                                    ])>{{ $row['status'] === 'Lunas' ? 'Lunas' : ($row['status'] === 'Menyicil' ? 'Menyicil' : 'Menunggak') }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($matrixData['periods']) + 5 }}" class="px-6 py-12 text-center text-xs text-muted">
                                    {{ isset($query['mq']) ? 'Tidak ada anggota yang cocok dengan pencarian.' : 'Belum ada data anggota kelas terdaftar.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="sticky bottom-0 z-20 border-t-2 border-ink/10 bg-canvas text-xs font-semibold">
                        <tr>
                            <td colspan="2" class="sticky left-0 z-30 bg-canvas px-3 py-3 text-right text-[11px] uppercase tracking-wider">Total semua anggota</td>
                            @foreach ($matrixData['periods'] as $period)
                                <td class="px-3 py-3 text-center font-mono text-[11px] text-success">Rp{{ number_format($matrixData['periodTotals'][$period->id] ?? 0, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="px-4 py-3 text-right font-mono text-sm text-success">Rp{{ number_format($matrixData['totalPaid'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-danger">Rp{{ number_format($matrixData['totalRemaining'], 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>


            {{-- Tampilan mobile: satu kartu per anggota, tanpa scroll horizontal --}}
            <div class="divide-y divide-ink/5 md:hidden">
                @forelse ($matrixData['rows'] as $row)
                    @php($rowTone = match ($row['status']) {
                        'Lunas' => 'border-line bg-surface-2 text-success',
                        'Menyicil' => 'border-line bg-surface-2 text-warning',
                        default => 'border-line bg-surface-2 text-danger',
                    })
                    <details class="group px-4 py-3.5" @if ($row['total_remaining'] > 0 && $loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="w-6 shrink-0 text-center font-mono text-[11px] text-muted">{{ $row['no'] }}</span>
                                <div class="flex min-w-0 flex-col">
                                    <span class="truncate text-sm font-semibold text-ink">{{ $row['user']->name }}</span>
                                    <span class="font-mono text-[10px] text-muted">{{ $row['user']->student_no ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $rowTone }}">{{ $row['status'] === 'Lunas' || $row['status'] === 'Menyicil' ? $row['status'] : 'Menunggak' }}</span>
                                <span class="text-[11px] tabular-nums {{ $row['total_remaining'] > 0 ? 'font-semibold text-danger' : 'text-muted' }}">
                                    {{ $row['total_remaining'] > 0 ? 'Sisa Rp' . number_format($row['total_remaining'], 0, ',', '.') : 'Tidak ada tunggakan' }}
                                </span>
                            </div>
                        </summary>

                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-xl bg-surface-2 px-3 py-2">
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-success">Total setor</dt>
                                <dd class="font-semibold tabular-nums text-success">Rp{{ number_format($row['total_paid'], 0, ',', '.') }}</dd>
                            </div>
                            <div class="rounded-xl bg-surface-2 px-3 py-2">
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-danger">Tunggakan</dt>
                                <dd class="font-semibold tabular-nums text-danger">Rp{{ number_format($row['total_remaining'], 0, ',', '.') }}</dd>
                            </div>
                        </dl>

                        <ul class="mt-3 flex flex-col gap-1.5">
                            @foreach ($matrixData['periods'] as $period)
                                @php($cell = $row['cells'][$period->id] ?? null)
                                @continue(! $cell || $cell['status'] === 'none')
                                <li class="flex items-center justify-between gap-3 rounded-xl border border-ink/5 px-3 py-2">
                                    <span class="min-w-0 truncate text-xs font-medium text-ink">{{ $period->title }}</span>
                                    <span class="flex shrink-0 flex-col items-end text-[11px] font-semibold tabular-nums
                                        {{ $cell['status'] === 'paid' ? 'text-success' : ($cell['status'] === 'partial' ? 'text-warning' : 'text-danger') }}">
                                        @if ($cell['status'] === 'paid')
                                            <span>Lunas · Rp{{ number_format($cell['paid'], 0, ',', '.') }}</span>
                                        @elseif ($cell['status'] === 'partial')
                                            <span>Cicil Rp{{ number_format($cell['paid'], 0, ',', '.') }}</span>
                                            <span class="text-[10px] font-medium">Sisa Rp{{ number_format($cell['remaining'], 0, ',', '.') }}</span>
                                        @else
                                            <span>Belum bayar{{ $cell['is_overdue'] ? ' · Telat' : '' }}</span>
                                            <span class="text-[10px] font-medium">Rp{{ number_format($cell['remaining'], 0, ',', '.') }}</span>
                                        @endif
                                        @if ($cell['status'] !== 'paid' && auth()->user()?->isStaff() && ! empty($cell['invoice_id']))
                                            <a href="{{ route('payments.create', $cell['invoice_id']) }}" class="mt-0.5 inline-flex min-h-8 items-center text-[11px] font-semibold text-brand">Bantu bayar</a>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @empty
                    <p class="px-6 py-12 text-center text-xs text-muted">
                        {{ isset($query['mq']) ? 'Tidak ada anggota yang cocok dengan pencarian.' : 'Belum ada data anggota kelas terdaftar.' }}
                    </p>
                @endforelse

                <div class="grid grid-cols-2 gap-2 bg-canvas px-4 py-3 text-xs font-semibold">
                    <span class="text-success">Total setor<br>Rp{{ number_format($matrixData['totalPaid'], 0, ',', '.') }}</span>
                    <span class="text-right text-danger">Total tunggakan<br>Rp{{ number_format($matrixData['totalRemaining'], 0, ',', '.') }}</span>
                </div>
            </div>

            <x-report-pager live :paginator="$matrixData['paginator']" label="anggota" />
        </section>
