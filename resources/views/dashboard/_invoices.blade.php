<ul class="divide-y divide-line">
    @forelse ($invoices as $invoice)
        <li class="flex flex-col gap-3 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:px-5">
            <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                <p class="text-[13px] font-medium text-ink wrap-break-word">{{ $invoice->period->title }}</p>
                <div class="flex flex-wrap items-center gap-1.5">
                    @if ($invoice->period->category)
                        <x-ui.badge>{{ $invoice->period->category->name }}</x-ui.badge>
                    @endif
                    @if ($invoice->isPartial())
                        <x-ui.badge tone="warning">Menyicil</x-ui.badge>
                    @endif
                    @if ($invoice->isOverdue())
                        <x-ui.badge tone="danger" dot>Lewat Tenggat</x-ui.badge>
                    @endif
                </div>
                <p class="text-xs leading-relaxed text-muted">
                    Jatuh tempo {{ $invoice->period->due_date->format('d M Y') }}
                    @if ($invoice->isPartial())
                        · Terbayar Rp{{ number_format($invoice->paidAmount(), 0, ',', '.') }} dari Rp{{ number_format($invoice->amount, 0, ',', '.') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center justify-between gap-3 border-t border-line pt-3 sm:shrink-0 sm:justify-end sm:border-0 sm:pt-0">
                <div class="flex flex-col sm:items-end">
                    <span class="ui-eyebrow">Sisa</span>
                    <span class="text-sm font-semibold tabular-nums text-ink">Rp{{ number_format($invoice->remainingAmount(), 0, ',', '.') }}</span>
                </div>
                <a href="{{ route('payments.create', $invoice) }}" class="ui-btn ui-btn-primary">
                    {{ $invoice->isPartial() ? 'Lanjut Bayar' : $payLabel }}
                </a>
            </div>
        </li>
    @empty
        <li class="px-6 py-10 text-center text-xs text-muted">Semua tagihan sudah lunas. Mantap!</li>
    @endforelse
</ul>
