        @php
            $w = $matrixData['periodWindow'];
            $windowUrl = fn (int $page) => route('reports.payments', array_merge($query, ['tab' => 'matrix', 'period_page' => $page]));
            $hasMatrixFilter = isset($query['mq']) || isset($query['m_category']) || isset($query['m_cols']);
            $selectedMatrixCategory = $query['m_category'] ?? '';
            $selectedColumns = (int) ($w['size'] ?? \App\Http\Controllers\PaymentReportController::MATRIX_DEFAULT_WINDOW);
            $rupiah = fn (int $value) => 'Rp' . number_format($value, 0, ',', '.');
            $canAssist = (bool) auth()->user()?->isStaff();
            $rowTone = fn (string $status) => match ($status) {
                'Lunas' => ['success', 'Lunas'],
                'Menyicil' => ['warning', 'Menyicil'],
                'Bebas Kas' => ['neutral', 'Bebas kas'],
                default => ['danger', 'Menunggak'],
            };
        @endphp
        <section aria-labelledby="heading-matriks" class="ui-card flex flex-col overflow-hidden">
            <div class="flex flex-col gap-4 border-b border-line px-4 py-4 sm:px-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex min-w-0 flex-col gap-1">
                        <h2 id="heading-matriks" class="text-base font-semibold text-ink">Matriks iuran siswa</h2>
                        <p class="max-w-xl text-xs leading-relaxed text-muted">
                            Buku Pembantu Kas · <span class="tabular-nums">{{ $matrixData['paginator']->total() }}</span> anggota.
                            Total setor dan tunggakan dihitung dari semua periode yang cocok dengan filter.
                        </p>
                    </div>
                    @include('reports._matrix_view_toggle')
                </div>

                {{-- One toolbar: search · category · column count · period window --}}
                <form method="GET" action="{{ route('reports.payments') }}" wire:submit.prevent="$refresh" class="flex flex-col gap-2 lg:flex-row lg:items-center print:hidden" role="search" aria-label="Filter matriks">
                    <input type="hidden" name="tab" value="matrix">
                    <input type="hidden" name="m_view" value="grid">
                    <div class="flex min-w-0 flex-1 gap-2">
                        <input type="search" name="mq" value="{{ $query['mq'] ?? '' }}" wire:model.live.debounce.400ms="matrixSearch" placeholder="Cari nama / NIM..." aria-label="Cari anggota"
                            class="min-h-9 min-w-0 flex-1 rounded-lg border border-line bg-surface px-3 text-[13px] text-ink shadow-2xs placeholder:text-muted/70 focus:border-line-strong focus:outline-none focus:ring-3 focus:ring-ink/5">
                        <button type="submit" class="ui-btn ui-btn-primary shrink-0">Cari</button>
                    </div>
                    <div class="grid grid-cols-[minmax(0,1fr)_7rem] gap-2 lg:w-[22rem]">
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
                    </div>
                    @if ($w && $w['pages'] > 1)
                        <div class="flex items-center justify-between gap-1 rounded-lg border border-line bg-surface p-0.5 shadow-2xs lg:justify-start" role="group" aria-label="Geser jendela periode">
                            <a href="{{ $w['page'] < $w['pages'] ? $windowUrl($w['page'] + 1) : '#' }}" wire:click.prevent="showPeriodWindow({{ min($w['pages'], $w['page'] + 1) }})" aria-label="Periode lebih lama" @class([
                                'inline-flex size-8 items-center justify-center rounded-md text-muted transition-colors hover:bg-surface-3 hover:text-ink',
                                'pointer-events-none opacity-40' => $w['page'] >= $w['pages'],
                            ])>
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                            </a>
                            <span class="whitespace-nowrap px-1 text-xs tabular-nums text-muted">Periode <span class="font-medium text-ink">{{ $w['from'] }}–{{ $w['to'] }}</span> dari {{ $w['total'] }}</span>
                            <a href="{{ $w['page'] > 1 ? $windowUrl($w['page'] - 1) : '#' }}" wire:click.prevent="showPeriodWindow({{ max(1, $w['page'] - 1) }})" aria-label="Periode lebih baru" @class([
                                'inline-flex size-8 items-center justify-center rounded-md text-muted transition-colors hover:bg-surface-3 hover:text-ink',
                                'pointer-events-none opacity-40' => $w['page'] <= 1,
                            ])>
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                            </a>
                        </div>
                    @endif
                    @if ($hasMatrixFilter)
                        <a href="{{ route('reports.payments', ['tab' => 'matrix', 'm_view' => 'grid']) }}" wire:click.prevent="resetMatrixFilters" class="ui-btn ui-btn-ghost text-muted">Reset</a>
                    @endif
                </form>
            </div>

            {{-- Desktop: matrix with sticky member column --}}
            <div class="hidden max-h-[70vh] overflow-auto [scrollbar-width:thin] md:block">
                <table class="w-full border-separate border-spacing-0 text-left text-xs">
                    <thead class="text-muted">
                        <tr class="[&>th]:sticky [&>th]:top-0 [&>th]:z-20 [&>th]:border-b [&>th]:border-line [&>th]:bg-surface-2 [&>th]:px-3 [&>th]:py-2.5 [&>th]:text-[10px] [&>th]:font-semibold [&>th]:uppercase [&>th]:tracking-wider">
                            <th scope="col" class="left-0 z-30! w-10 text-center">No</th>
                            <th scope="col" class="left-10 z-30! min-w-44 shadow-[1px_0_0_var(--color-line)]">Anggota</th>
                            @foreach ($matrixData['periods'] as $period)
                                <th scope="col" class="min-w-26 text-center">
                                    <span class="block max-w-28 truncate text-[11px] font-medium normal-case tracking-normal text-ink" title="{{ $period->title }}">{{ $period->title }}</span>
                                    <span class="block font-mono text-[10px] font-normal normal-case tracking-normal">{{ $rupiah((int) $period->amount) }}</span>
                                </th>
                            @endforeach
                            <th scope="col" class="min-w-24 text-right">Setor</th>
                            <th scope="col" class="min-w-24 text-right">Tunggakan</th>
                            <th scope="col" class="min-w-24 pr-5! text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($matrixData['rows'] as $row)
                            @php([$statusTone, $statusLabel] = $rowTone($row['status']))
                            <tr class="group [&>td]:border-b [&>td]:border-line [&>td]:px-3 [&>td]:py-2.5">
                                <td class="sticky left-0 z-10 bg-surface text-center font-mono text-[11px] text-faint group-hover:bg-surface-2">{{ $row['no'] }}</td>
                                <td class="sticky left-10 z-10 bg-surface shadow-[1px_0_0_var(--color-line)] group-hover:bg-surface-2">
                                    <span class="block max-w-52 truncate text-[13px] font-medium text-ink">{{ $row['user']->name }}</span>
                                    <span class="font-mono text-[10px] text-muted">{{ $row['user']->student_no ?? '—' }}</span>
                                </td>
                                @foreach ($matrixData['periods'] as $period)
                                    @php($cell = $row['cells'][$period->id] ?? null)
                                    <td class="text-center group-hover:bg-surface-2">
                                        @if (! $cell || $cell['status'] === 'none')
                                            <span class="text-faint">—</span>
                                        @else
                                            <div class="inline-flex flex-col items-center gap-1">
                                                @if ($cell['status'] === 'paid')
                                                    <span class="ui-tag ui-tag-success">Lunas</span>
                                                @elseif ($cell['status'] === 'partial')
                                                    <span class="ui-tag ui-tag-warning">Cicil</span>
                                                    <span class="font-mono text-[10px] text-muted">sisa {{ $rupiah($cell['remaining']) }}</span>
                                                @else
                                                    <span class="ui-tag ui-tag-danger">{{ $cell['is_overdue'] ? 'Telat' : 'Belum' }}</span>
                                                @endif
                                                @if ($cell['status'] !== 'paid' && $canAssist && ! empty($cell['invoice_id']))
                                                    <a href="{{ route('payments.create', $cell['invoice_id']) }}" class="text-[10px] font-medium text-link hover:underline print:hidden">Bantu bayar</a>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-right tabular-nums text-body group-hover:bg-surface-2">{{ $rupiah($row['total_paid']) }}</td>
                                <td class="text-right tabular-nums group-hover:bg-surface-2 {{ $row['total_remaining'] > 0 ? 'font-medium text-danger' : 'text-faint' }}">{{ $row['total_remaining'] > 0 ? $rupiah($row['total_remaining']) : '—' }}</td>
                                <td class="pr-5! text-center group-hover:bg-surface-2"><x-ui.badge :tone="$statusTone">{{ $statusLabel }}</x-ui.badge></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($matrixData['periods']) + 5 }}" class="px-6 py-12 text-center text-xs text-muted">
                                    {{ isset($query['mq']) ? 'Tidak ada anggota yang cocok dengan pencarian.' : 'Belum ada data anggota kelas terdaftar.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="text-xs text-muted">
                        <tr class="[&>td]:sticky [&>td]:bottom-0 [&>td]:z-20 [&>td]:border-t [&>td]:border-line [&>td]:bg-surface-2 [&>td]:px-3 [&>td]:py-2.5">
                            <td colspan="2" class="left-0 z-30! font-medium text-body shadow-[1px_0_0_var(--color-line)]">Total semua anggota</td>
                            @foreach ($matrixData['periods'] as $period)
                                <td class="text-center font-mono text-[11px] tabular-nums">{{ $rupiah($matrixData['periodTotals'][$period->id] ?? 0) }}</td>
                            @endforeach
                            <td class="text-right font-medium tabular-nums text-ink">{{ $rupiah($matrixData['totalPaid']) }}</td>
                            <td class="text-right font-medium tabular-nums {{ $matrixData['totalRemaining'] > 0 ? 'text-danger' : 'text-faint' }}">{{ $rupiah($matrixData['totalRemaining']) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Mobile: one compact card per member, no horizontal scroll --}}
            <ul class="divide-y divide-line md:hidden">
                @forelse ($matrixData['rows'] as $row)
                    @php([$statusTone, $statusLabel] = $rowTone($row['status']))
                    @php($nextUnpaid = collect($row['cells'])->first(fn ($c) => in_array($c['status'], ['unpaid', 'partial'], true) && ! empty($c['invoice_id'])))
                    <li class="flex flex-col gap-2.5 px-4 py-3.5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 flex-col">
                                <span class="truncate text-[13px] font-semibold text-ink">{{ $row['user']->name }}</span>
                                <span class="font-mono text-[10px] text-muted">{{ $row['user']->student_no ?? '—' }}</span>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <x-ui.badge :tone="$statusTone">{{ $statusLabel }}</x-ui.badge>
                                <span class="text-[11px] tabular-nums {{ $row['total_remaining'] > 0 ? 'font-medium text-danger' : 'text-muted' }}">
                                    {{ $row['total_remaining'] > 0 ? 'Sisa ' . $rupiah($row['total_remaining']) : 'Tidak ada tunggakan' }}
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                            @foreach ($matrixData['periods'] as $period)
                                @php($cell = $row['cells'][$period->id] ?? null)
                                @continue(! $cell || $cell['status'] === 'none')
                                <span class="flex min-w-0 items-center justify-between gap-2 text-[11px] text-muted" title="{{ $period->title }}">
                                    <span class="min-w-0 truncate">{{ $period->title }}</span>
                                    @if ($cell['status'] === 'paid')
                                        <span class="ui-tag ui-tag-success">Lunas</span>
                                    @elseif ($cell['status'] === 'partial')
                                        <span class="ui-tag ui-tag-warning">Cicil</span>
                                    @else
                                        <span class="ui-tag ui-tag-danger">{{ $cell['is_overdue'] ? 'Telat' : 'Belum' }}</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                        @if ($canAssist && $nextUnpaid)
                            <a href="{{ route('payments.create', $nextUnpaid['invoice_id']) }}" class="ui-btn ui-btn-outline min-h-8 self-start px-2.5 text-xs print:hidden">Bantu bayar</a>
                        @endif
                    </li>
                @empty
                    <li class="px-6 py-12 text-center text-xs text-muted">
                        {{ isset($query['mq']) ? 'Tidak ada anggota yang cocok dengan pencarian.' : 'Belum ada data anggota kelas terdaftar.' }}
                    </li>
                @endforelse
                <li class="flex justify-between gap-3 bg-surface-2 px-4 py-3 text-xs text-muted">
                    <span>Total setor <span class="font-medium tabular-nums text-ink">{{ $rupiah($matrixData['totalPaid']) }}</span></span>
                    <span>Tunggakan <span class="font-medium tabular-nums {{ $matrixData['totalRemaining'] > 0 ? 'text-danger' : 'text-faint' }}">{{ $rupiah($matrixData['totalRemaining']) }}</span></span>
                </li>
            </ul>

            <x-report-pager live :paginator="$matrixData['paginator']" label="anggota" />
        </section>
