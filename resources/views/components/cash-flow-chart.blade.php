@props(['flow'])

@php
    $peak = max(0, ...array_map(fn ($m) => max($m['in'], $m['out']), $flow));
    $hasFlow = $peak > 0;
    $peak = max(1, $peak);
    $totalIn = array_sum(array_column($flow, 'in'));
    $totalOut = array_sum(array_column($flow, 'out'));
    $rupiah = fn (int $value) => 'Rp' . number_format($value, 0, ',', '.');
    $short = fn (int $value) => $value >= 1_000_000
        ? rtrim(rtrim(number_format($value / 1_000_000, 1, ',', '.'), '0'), ',') . 'jt'
        : ($value >= 1000 ? number_format($value / 1000, 0, ',', '.') . 'rb' : (string) $value);
@endphp

<section aria-labelledby="arus-kas-heading" {{ $attributes->class('ui-card flex flex-col gap-5 p-4 sm:p-6') }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex flex-col gap-1">
            <h2 id="arus-kas-heading" class="text-base font-semibold text-ink">Arus kas</h2>
            <p class="text-xs text-muted">{{ count($flow) }} bulan terakhir, dari ledger</p>
        </div>
        <dl class="flex gap-5 text-xs">
            <div class="flex flex-col gap-0.5">
                <dt class="flex items-center gap-1.5 text-muted"><span class="size-2 rounded-[2px] bg-chart-in" aria-hidden="true"></span>Masuk</dt>
                <dd class="font-semibold tabular-nums text-ink">{{ $rupiah($totalIn) }}</dd>
            </div>
            <div class="flex flex-col gap-0.5">
                <dt class="flex items-center gap-1.5 text-muted"><span class="size-2 rounded-[2px] bg-chart-out" aria-hidden="true"></span>Keluar</dt>
                <dd class="font-semibold tabular-nums text-ink">{{ $rupiah($totalOut) }}</dd>
            </div>
        </dl>
    </div>

    <div class="relative flex flex-1 flex-col" aria-hidden="true">
        {{-- Recessive grid: top line labels the peak, bottom is the baseline --}}
        <div class="pointer-events-none absolute inset-x-0 top-0 bottom-6 flex flex-col justify-between">
            <div class="flex items-center gap-2 border-t border-dashed border-line">@if ($hasFlow)<span class="-mt-4 font-mono text-[10px] text-muted">{{ $short($peak) }}</span>@endif</div>
            <div class="border-t border-dashed border-line"></div>
            <div class="border-t border-line"></div>
        </div>

        <div class="relative flex min-h-44 flex-1 items-stretch gap-1 sm:min-h-52 sm:gap-3">
            @foreach ($flow as $month)
                <div class="group relative flex flex-1 flex-col items-center gap-2">
                    <div class="flex w-full flex-1 items-end justify-center gap-0.5 rounded-md px-0.5 transition-colors group-hover:bg-canvas-2 sm:px-2">
                        <span class="w-full max-w-5 rounded-t-[4px] bg-chart-in" style="height: {{ $month['in'] > 0 ? max(2, round($month['in'] * 100 / $peak)) : 0 }}%"></span>
                        <span class="w-full max-w-5 rounded-t-[4px] bg-chart-out" style="height: {{ $month['out'] > 0 ? max(2, round($month['out'] * 100 / $peak)) : 0 }}%"></span>
                    </div>
                    <span class="h-4 font-mono text-[10px] uppercase text-muted">{{ $month['month']->translatedFormat('M') }}</span>

                    <div class="pointer-events-none absolute bottom-full z-10 mb-1 w-40 rounded-lg {{ $loop->index < 2 ? 'left-0' : ($loop->remaining < 2 ? 'right-0' : 'left-1/2 -translate-x-1/2') }} border border-line bg-surface p-2.5 text-xs opacity-0 shadow-lg transition-opacity group-hover:opacity-100">
                        <p class="mb-1.5 font-medium text-ink">{{ $month['month']->translatedFormat('F Y') }}</p>
                        <p class="flex justify-between gap-2 text-muted"><span class="flex items-center gap-1.5"><span class="size-2 rounded-[2px] bg-chart-in"></span>Masuk</span><span class="tabular-nums text-ink">{{ $rupiah($month['in']) }}</span></p>
                        <p class="flex justify-between gap-2 text-muted"><span class="flex items-center gap-1.5"><span class="size-2 rounded-[2px] bg-chart-out"></span>Keluar</span><span class="tabular-nums text-ink">{{ $rupiah($month['out']) }}</span></p>
                        <p class="mt-1 flex justify-between gap-2 border-t border-line pt-1 text-muted"><span>Bersih</span><span class="font-medium tabular-nums text-ink">{{ $rupiah($month['in'] - $month['out']) }}</span></p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <table class="sr-only">
        <caption>Arus kas per bulan</caption>
        <thead><tr><th scope="col">Bulan</th><th scope="col">Masuk</th><th scope="col">Keluar</th></tr></thead>
        <tbody>
            @foreach ($flow as $month)
                <tr><th scope="row">{{ $month['month']->translatedFormat('F Y') }}</th><td>{{ $rupiah($month['in']) }}</td><td>{{ $rupiah($month['out']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</section>
