{{--
    Iuran siswa per periode: pick a period from the chip row, members are grouped into
    Belum bayar / Menyicil / Lunas. Default layout of the "Iuran siswa" tab; the classic matrix sits behind the view toggle.
--}}
@php
    $board = $periodBoard;
    $selected = $board['selected'];
    $rupiah = fn (int $value) => 'Rp' . number_format($value, 0, ',', '.');
    $canAssist = (bool) auth()->user()?->isStaff();
    $selectedMatrixCategory = $query['m_category'] ?? '';
    $hasBoardFilter = isset($query['mq']) || isset($query['m_category']);
    $periodUrl = fn (int $id) => route('reports.payments', array_merge($query, ['tab' => 'matrix', 'm_period' => $id]));
    $paidPct = $board['billed'] > 0 ? $board['paidCount'] * 100 / $board['billed'] : 0;
    $partialPct = $board['billed'] > 0 ? $board['partialCount'] * 100 / $board['billed'] : 0;
@endphp
<section aria-labelledby="heading-iuran-periode" class="ui-card flex flex-col overflow-hidden">
    <div class="flex flex-col gap-4 border-b border-line px-4 py-4 sm:px-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex min-w-0 flex-col gap-1">
                <h2 id="heading-iuran-periode" class="text-base font-semibold text-ink">Iuran siswa per periode</h2>
                <p class="max-w-xl text-xs leading-relaxed text-muted">Pilih periode untuk melihat siapa yang belum bayar, yang masih menyicil, dan yang sudah lunas.</p>
            </div>
            @include('reports._matrix_view_toggle')
        </div>

        <form method="GET" action="{{ route('reports.payments') }}" wire:submit.prevent="$refresh" class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto] sm:items-center print:hidden" role="search" aria-label="Filter iuran per periode">
            <input type="hidden" name="tab" value="matrix">
            <div class="flex min-w-0 gap-2">
                <input type="search" name="mq" value="{{ $query['mq'] ?? '' }}" wire:model.live.debounce.400ms="matrixSearch" placeholder="Cari nama / NIM..." aria-label="Cari anggota"
                    class="min-h-10 min-w-0 flex-1 rounded-lg border border-line bg-surface px-3 text-[13px] text-ink shadow-2xs placeholder:text-muted/70 focus:border-line-strong focus:outline-none focus:ring-3 focus:ring-ink/5">
                <button type="submit" class="ui-btn ui-btn-primary shrink-0">Cari</button>
            </div>
            <div wire:ignore wire:key="board-category-{{ $selectedMatrixCategory }}" class="min-w-0">
                <select name="m_category" data-tom-select wire:model.live="matrixCategory" aria-label="Filter kategori periode">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) $selectedMatrixCategory === (string) $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            @if ($hasBoardFilter)
                <a href="{{ route('reports.payments', ['tab' => 'matrix']) }}" wire:click.prevent="resetMatrixFilters" class="ui-btn ui-btn-ghost text-muted">Reset</a>
            @endif
        </form>
    </div>

    @if ($board['periods']->isEmpty())
        <p class="px-6 py-12 text-center text-xs text-muted">Belum ada periode tagihan{{ $selectedMatrixCategory !== '' ? ' di kategori ini' : '' }}.</p>
    @else
        {{-- Period chips, newest first --}}
        <nav aria-label="Pilih periode" class="scroll-row flex gap-1.5 border-b border-line px-4 py-3 sm:px-6 print:hidden">
            @foreach ($board['periods'] as $period)
                @php($isSelected = $selected?->is($period))
                <a href="{{ $periodUrl($period->id) }}" wire:key="board-period-{{ $period->id }}" wire:click.prevent="showMatrixPeriod({{ $period->id }})"
                    @if ($isSelected) aria-current="true" @endif
                    @class([
                        'inline-flex min-h-8 shrink-0 items-center gap-1.5 rounded-full border px-3 text-xs font-medium transition-colors',
                        'border-transparent bg-primary text-on-primary' => $isSelected,
                        'border-line bg-surface text-body hover:border-line-strong hover:text-ink' => ! $isSelected,
                    ])>
                    <span class="max-w-40 truncate">{{ $period->title }}</span>
                    <span @class(['font-mono text-[10px] tabular-nums', 'opacity-70' => $isSelected, 'text-muted' => ! $isSelected])>{{ $period->paid_count }}/{{ $period->billed_count }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Selected period summary --}}
        <div class="flex flex-col gap-2.5 border-b border-line px-4 py-4 sm:px-6">
            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <h3 class="text-sm font-semibold text-ink">{{ $selected->title }}</h3>
                    <span class="text-xs text-muted">{{ $rupiah((int) $selected->amount) }} · jatuh tempo {{ $selected->due_date->translatedFormat('d M Y') }}</span>
                    @if ($selected->isPastDue() && $board['paidCount'] < $board['billed'])
                        <x-ui.badge tone="danger">Lewat tempo</x-ui.badge>
                    @endif
                </div>
                <p class="text-xs tabular-nums text-muted">
                    <span class="font-medium text-ink">{{ $board['paidCount'] }}/{{ $board['billed'] }}</span> lunas · terkumpul <span class="font-medium text-ink">{{ $rupiah($board['collected']) }}</span>
                </p>
            </div>
            <div class="flex h-2 w-full overflow-hidden rounded-full bg-line" role="progressbar" aria-valuenow="{{ (int) round($paidPct) }}" aria-valuemin="0" aria-valuemax="100" aria-label="Siswa lunas di {{ $selected->title }}">
                <div class="h-full bg-success" style="width: {{ $paidPct }}%"></div>
                <div class="h-full bg-warning" style="width: {{ $partialPct }}%"></div>
            </div>
            @if ($board['remaining'] > 0)
                <p class="text-xs text-muted">Belum terkumpul <span class="font-semibold tabular-nums text-danger">{{ $rupiah($board['remaining']) }}</span></p>
            @endif
        </div>

        @if ($board['billed'] === 0)
            <p class="px-6 py-12 text-center text-xs text-muted">
                {{ isset($query['mq']) ? 'Tidak ada anggota yang cocok dengan pencarian.' : 'Belum ada tagihan anggota untuk periode ini.' }}
            </p>
        @else
            @foreach ([
                'unpaid' => ['Belum bayar', 'danger'],
                'partial' => ['Menyicil', 'warning'],
            ] as $groupKey => [$groupLabel, $groupTone])
                @continue($board['groups'][$groupKey] === [])
                <section aria-label="{{ $groupLabel }}" class="flex flex-col">
                    <h4 class="flex items-center gap-2 px-4 pb-1.5 pt-3.5 text-[11px] font-semibold uppercase tracking-wider text-muted sm:px-6">
                        <x-ui.badge :tone="$groupTone" class="font-mono tabular-nums">{{ count($board['groups'][$groupKey]) }}</x-ui.badge>
                        {{ $groupLabel }}
                    </h4>
                    <ul class="divide-y divide-line border-t border-line">
                        @foreach ($board['groups'][$groupKey] as $row)
                            <li wire:key="board-row-{{ $row['invoice_id'] }}" class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 px-4 py-2.5 sm:px-6">
                                <div class="flex min-w-0 flex-col">
                                    <span class="truncate text-[13px] font-semibold text-ink">{{ $row['user']->name }}</span>
                                    <span class="font-mono text-[10px] text-muted">{{ $row['user']->student_no ?? '—' }}</span>
                                </div>
                                <div class="ml-auto flex shrink-0 items-center gap-2">
                                    <span class="font-mono text-[11px] tabular-nums text-muted">
                                        {{ $groupKey === 'partial' ? 'sisa ' : '' }}{{ $rupiah($row['remaining']) }}
                                    </span>
                                    @if ($row['is_overdue'])
                                        <x-ui.badge tone="danger">Telat</x-ui.badge>
                                    @endif
                                    @if ($canAssist)
                                        <a href="{{ route('payments.create', $row['invoice_id']) }}"
                                            class="ui-btn {{ $groupKey === 'unpaid' ? 'ui-btn-primary' : 'ui-btn-outline' }} min-h-7 px-2.5 text-xs print:hidden">Bantu bayar</a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach

            @if ($board['groups']['paid'] !== [])
                <details class="group/paid flex flex-col" @if ($board['groups']['unpaid'] === [] && $board['groups']['partial'] === []) open @endif>
                    <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 border-t border-line px-4 text-[11px] font-semibold uppercase tracking-wider text-muted transition-colors hover:text-ink sm:px-6 [&::-webkit-details-marker]:hidden">
                        <x-ui.badge tone="success" class="font-mono tabular-nums">{{ count($board['groups']['paid']) }}</x-ui.badge>
                        Lunas
                        <span class="ml-auto font-sans text-xs font-normal normal-case tracking-normal">
                            <span class="group-open/paid:hidden">Tampilkan</span><span class="hidden group-open/paid:inline">Sembunyikan</span>
                        </span>
                    </summary>
                    <ul class="divide-y divide-line border-t border-line">
                        @foreach ($board['groups']['paid'] as $row)
                            <li wire:key="board-row-{{ $row['invoice_id'] }}" class="flex items-center justify-between gap-3 px-4 py-2.5 sm:px-6">
                                <div class="flex min-w-0 flex-col">
                                    <span class="truncate text-[13px] font-medium text-ink">{{ $row['user']->name }}</span>
                                    <span class="font-mono text-[10px] text-muted">{{ $row['user']->student_no ?? '—' }}</span>
                                </div>
                                <span class="font-mono text-[11px] tabular-nums text-success">{{ $rupiah($row['paid']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        @endif
    @endif
</section>
