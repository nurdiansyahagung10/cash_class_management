<section aria-labelledby="kalender-kas-heading" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface p-5 shadow-xs sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-0.5">
            <h2 id="kalender-kas-heading" class="text-base font-semibold text-ink">Kalender kas · {{ $calendar['month']->translatedFormat('F Y') }}</h2>
            <p class="text-xs text-muted">
                Masuk <span class="font-semibold text-success tabular-nums">+Rp{{ number_format($calendar['totalIn'], 0, ',', '.') }}</span>
                · Keluar <span class="font-semibold text-danger tabular-nums">-Rp{{ number_format($calendar['totalOut'], 0, ',', '.') }}</span>
            </p>
        </div>
        <nav aria-label="Navigasi bulan" class="flex items-center gap-1.5">
            @php($prev = $calendar['month']->copy()->subMonthNoOverflow()->format('Y-m'))
            @php($next = $calendar['month']->copy()->addMonthNoOverflow()->format('Y-m'))
            <a href="{{ route('dashboard', ['bulan' => $prev]) }}" wire:click.prevent="previousMonth" aria-label="Bulan sebelumnya" class="ui-btn ui-btn-outline size-9 px-0">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
            </a>
            <a href="{{ route('dashboard') }}" wire:click.prevent="thisMonth" class="ui-btn ui-btn-outline">Hari ini</a>
            <a href="{{ route('dashboard', ['bulan' => $next]) }}" wire:click.prevent="nextMonth" aria-label="Bulan berikutnya" class="ui-btn ui-btn-outline size-9 px-0">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
            </a>
        </nav>
    </div>

    <div wire:loading.class="opacity-60" class="grid grid-cols-7 gap-px transition-opacity overflow-hidden rounded-xl border border-line bg-line" role="grid">
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dayName)
            <div role="columnheader" class="bg-canvas py-2.5 text-center text-[11px] font-semibold text-muted">{{ $dayName }}</div>
        @endforeach

        @foreach ($calendar['weeks'] as $week)
            @foreach ($week as $cell)
                @if ($cell === null)
                    <div class="min-h-14 bg-canvas sm:min-h-20"></div>
                @else
                    <div role="gridcell" @if ($cell['isToday']) aria-current="date" @endif
                         title="{{ \Illuminate\Support\Carbon::parse($cell['date'])->translatedFormat('d F Y') }}: masuk Rp{{ number_format($cell['in'], 0, ',', '.') }}, keluar Rp{{ number_format($cell['out'], 0, ',', '.') }}"
                         class="flex min-h-14 flex-col gap-0.5 p-1.5 transition-colors sm:min-h-20 sm:p-2 {{ $cell['in'] || $cell['out'] ? 'bg-brand-soft' : 'bg-surface' }} {{ $cell['isToday'] ? 'ring-1 ring-inset ring-brand' : '' }}">
                        <span class="flex size-5 items-center justify-center rounded-full text-[11px] font-semibold sm:size-6 sm:text-xs {{ $cell['isToday'] ? 'bg-brand text-white shadow-xs' : 'text-ink' }}">{{ $cell['day'] }}</span>
                        @if ($cell['in'])
                            <span class="truncate text-[10px] font-semibold text-success sm:text-[11px] tabular-nums">+{{ number_format($cell['in'] / 1000, 0, ',', '.') }}k</span>
                        @endif
                        @if ($cell['out'])
                            <span class="truncate text-[10px] font-semibold text-danger sm:text-[11px] tabular-nums">-{{ number_format($cell['out'] / 1000, 0, ',', '.') }}k</span>
                        @endif
                    </div>
                @endif
            @endforeach
        @endforeach
    </div>
</section>
