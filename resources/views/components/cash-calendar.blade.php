@props(['calendar'])
<section aria-labelledby="kalender-kas-heading" class="flex flex-col gap-4 rounded-2xl border border-line bg-white p-5 shadow-xs sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-0.5">
            <h2 id="kalender-kas-heading" class="text-base font-bold text-black">Kalender kas · {{ $calendar['month']->translatedFormat('F Y') }}</h2>
            <p class="text-xs text-muted">
                Masuk <span class="font-semibold text-[#14532d] tabular-nums">+Rp{{ number_format($calendar['totalIn'], 0, ',', '.') }}</span>
                · Keluar <span class="font-semibold text-[#8f1d0e] tabular-nums">-Rp{{ number_format($calendar['totalOut'], 0, ',', '.') }}</span>
            </p>
        </div>
        <nav aria-label="Navigasi bulan" class="flex items-center gap-1.5">
            <a href="{{ route('dashboard', ['bulan' => $calendar['month']->copy()->subMonth()->format('Y-m')]) }}" aria-label="Bulan sebelumnya" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border border-line text-sm font-semibold transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">&larr;</a>
            <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line px-3.5 text-xs font-semibold transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Hari ini</a>
            <a href="{{ route('dashboard', ['bulan' => $calendar['month']->copy()->addMonth()->format('Y-m')]) }}" aria-label="Bulan berikutnya" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border border-line text-sm font-semibold transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">&rarr;</a>
        </nav>
    </div>

    <div class="grid grid-cols-7 gap-px overflow-hidden rounded-xl border border-line bg-black/[0.08]" role="grid">
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dayName)
            <div role="columnheader" class="bg-canvas py-2.5 text-center text-[11px] font-semibold text-muted">{{ $dayName }}</div>
        @endforeach

        @foreach ($calendar['weeks'] as $week)
            @foreach ($week as $cell)
                @if ($cell === null)
                    <div class="min-h-14 bg-canvas/60 sm:min-h-20"></div>
                @else
                    <div role="gridcell" @if ($cell['isToday']) aria-current="date" @endif
                         title="{{ \Illuminate\Support\Carbon::parse($cell['date'])->translatedFormat('d F Y') }}: masuk Rp{{ number_format($cell['in'], 0, ',', '.') }}, keluar Rp{{ number_format($cell['out'], 0, ',', '.') }}"
                         class="flex min-h-14 flex-col gap-0.5 bg-white p-1.5 transition-colors sm:min-h-20 sm:p-2 {{ $cell['in'] || $cell['out'] ? 'bg-brand-soft/30' : '' }} {{ $cell['isToday'] ? 'ring-1 ring-inset ring-brand' : '' }}">
                        <span class="flex size-5 items-center justify-center rounded-full text-[11px] font-semibold sm:size-6 sm:text-xs {{ $cell['isToday'] ? 'bg-brand text-white shadow-xs' : 'text-black' }}">{{ $cell['day'] }}</span>
                        @if ($cell['in'])
                            <span class="truncate text-[10px] font-semibold text-[#14532d] sm:text-[11px] tabular-nums">+{{ number_format($cell['in'] / 1000, 0, ',', '.') }}k</span>
                        @endif
                        @if ($cell['out'])
                            <span class="truncate text-[10px] font-semibold text-[#8f1d0e] sm:text-[11px] tabular-nums">-{{ number_format($cell['out'] / 1000, 0, ',', '.') }}k</span>
                        @endif
                    </div>
                @endif
            @endforeach
        @endforeach
    </div>
</section>
