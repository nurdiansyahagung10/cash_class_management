@php
    use App\Models\BillingPeriod;
    use Illuminate\Support\Carbon;

    $stages = [
        'incoming' => ['label' => 'Akan datang', 'hint' => 'Tagihan yang belum dibuka. Periodenya belum dimulai.', 'tone' => 'bg-brand/15 text-info'],
        'running' => ['label' => 'Berjalan', 'hint' => 'Tagihan yang sedang berlangsung dan belum lunas.', 'tone' => 'bg-surface-2 text-warning'],
        'paid' => ['label' => 'Sudah dibayar', 'hint' => 'Tagihan yang sudah lunas.', 'tone' => 'bg-surface-2 text-success'],
    ];

    $rangeLabel = function ($period) {
        $start = $period->rangeStart();
        $end = Carbon::parse($period->due_date->toDateString(), BillingPeriod::TIMEZONE);

        if ($start->isSameDay($end)) {
            return $end->translatedFormat('d M Y');
        }

        if ($start->year !== $end->year) {
            return $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y');
        }

        return $start->translatedFormat($start->month === $end->month ? 'd' : 'd M') . ' – ' . $end->translatedFormat('d M Y');
    };
@endphp

<x-layouts.app title="Riwayat pembayaran">
    <x-ui.page-header :eyebrow="'Pembayaran · ' . now()->translatedFormat('l, d F Y')" title="Riwayat pembayaran"
        description="Semua pembayaran kas yang pernah kamu kirim beserta statusnya." />

    <section aria-labelledby="judul-riwayat" class="flex flex-col rounded-2xl border border-line bg-surface shadow-xs">
        <div class="flex flex-col gap-4 border-b border-line px-4 py-5 sm:px-6">
            <div class="flex flex-col gap-1.5">
                <h1 id="judul-riwayat" class="text-base font-semibold text-ink">Riwayat pembayaran</h1>
                <p class="max-w-xl text-xs leading-relaxed text-muted">
                    Semua tagihanmu, dari yang akan datang, sedang berjalan, sampai yang sudah dibayar.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs" role="group" aria-label="Tahap tagihan">
                <a href="{{ route('payments.history') }}" @if (!$stage) aria-current="true" @endif
                    @class([
                        'inline-flex min-h-9 items-center gap-2 rounded-full border px-3.5 font-semibold transition-colors',
                        'border-brand bg-brand text-white' => !$stage,
                        'border-line bg-surface text-muted hover:text-ink' => $stage,
                    ])>
                    Semua
                </a>
                @foreach ($stages as $key => $meta)
                    <a href="{{ route('payments.history', ['tahap' => $key]) }}"
                        @if ($stage === $key) aria-current="true" @endif
                        @class([
                            'inline-flex min-h-9 items-center gap-2 rounded-full border px-3.5 font-semibold transition-colors',
                            'border-brand bg-brand text-white' => $stage === $key,
                            'border-line bg-surface text-muted hover:text-ink' => $stage !== $key,
                        ])>
                        {{ $meta['label'] }}
                        <span class="tabular-nums opacity-80">{{ $groups[$key]->count() }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="flex flex-col divide-y divide-line">
            @foreach ($visible as $key)
                @php $meta = $stages[$key]; @endphp
                <div class="flex flex-col">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 bg-canvas/40 px-4 py-3 sm:px-6">
                        <h2 class="text-sm font-semibold text-ink">{{ $meta['label'] }}
                            <span class="ml-1 font-semibold tabular-nums text-muted">{{ $groups[$key]->count() }}</span>
                        </h2>
                        <p class="text-[11px] text-muted">{{ $meta['hint'] }}</p>
                    </div>

                    <div class="divide-y divide-ink/5">
                        @forelse ($groups[$key] as $invoice)
                            @php
                                $period = $invoice->period;
                                $pending = $invoice->payments->contains(fn ($p) => $p->status === 'pending' && $p->proof_path);
                                $paidAt = $invoice->payments->where('status', 'paid')->max('paid_at');

                                [$badge, $badgeTone] = match (true) {
                                    $key === 'paid' => ['Lunas', 'bg-surface-2 text-success'],
                                    $key === 'incoming' => ['Akan datang', $meta['tone']],
                                    $pending => ['Menunggu verifikasi', 'bg-surface-2 text-warning'],
                                    $invoice->isOverdue() => ['Terlambat ' . $invoice->overdueDays() . ' hari', 'bg-surface-2 text-danger'],
                                    $invoice->isPartial() => ['Dibayar sebagian', 'bg-surface-2 text-warning'],
                                    default => ['Belum dibayar', 'bg-surface-2 text-warning'],
                                };
                            @endphp
                            <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-3 px-4 py-4 sm:px-6">
                                <div class="flex min-w-0 flex-col gap-1">
                                    <p class="text-sm font-semibold text-ink wrap-break-word">{{ $period->title }}</p>
                                    <p class="text-xs text-muted">
                                        @if ($period->category)
                                            <span class="font-medium text-brand">{{ $period->category->name }}</span> ·
                                        @endif
                                        {{ $rangeLabel($period) }}
                                    </p>
                                    <p class="text-[11px] text-muted">
                                        @if ($key === 'paid')
                                            Dibayar {{ $paidAt ? $paidAt->timezone(BillingPeriod::TIMEZONE)->translatedFormat('d M Y') : '' }}
                                        @else
                                            Tenggat {{ $period->due_date->translatedFormat('d M Y') }}, 23.59 WIB
                                            @if ($invoice->isPartial())
                                                · sisa Rp{{ number_format($invoice->remainingAmount(), 0, ',', '.') }}
                                            @endif
                                        @endif
                                    </p>
                                </div>

                                <div class="flex shrink-0 flex-col items-end gap-1.5">
                                    <span class="text-sm font-semibold tabular-nums text-ink">Rp{{ number_format($invoice->amount, 0, ',', '.') }}</span>
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $badgeTone }}">{{ $badge }}</span>
                                    @if ($key === 'running' && !$pending)
                                        <a href="{{ route('payments.create', $invoice) }}"
                                            class="text-xs font-semibold text-brand transition-colors hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Bayar
                                            sekarang</a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="px-6 py-8 text-center text-xs text-muted">Belum ada tagihan di tahap ini.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.app>
