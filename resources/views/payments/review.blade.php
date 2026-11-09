<x-layouts.app title="Review pembayaran">
    <header class="flex flex-col gap-2">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-xs font-semibold text-muted">
                <span class="hover:text-black transition-colors">Kas Kelas 1A</span>
                <span class="text-black/20">/</span>
                <span class="text-black">Review pembayaran</span>
                <span class="text-black/20">·</span>
                <span class="font-normal">{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </header>

    <section class="flex max-w-4xl flex-col divide-y divide-black/5 rounded-2xl border border-line bg-white shadow-xs">
        @forelse ($payments as $payment)
            <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-start sm:gap-6">
                <a href="{{ route('payments.proof.show', $payment) }}" target="_blank" rel="noopener"
                    class="group relative block size-28 shrink-0 overflow-hidden rounded-2xl border border-line bg-canvas/60 shadow-xs">
                    <img src="{{ route('payments.proof.show', $payment) }}"
                        alt="Bukti transfer {{ $payment->invoice->user->name }}"
                        class="size-full object-cover transition duration-200 group-hover:scale-105">
                    <span
                        class="absolute inset-x-0 bottom-0 bg-black/70 py-1 text-center text-[11px] font-semibold text-white">Buka
                        foto</span>
                </a>

                <div class="flex min-w-0 flex-1 flex-col gap-2.5">
                    <div class="flex flex-col gap-1">
                        <strong class="text-base font-bold text-black">{{ $payment->invoice->user->name }}</strong>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-muted">No. absen / NIM:</span>
                            <span
                                class="inline-flex min-w-14 items-center justify-center rounded-md border border-black/10 bg-canvas px-2 py-0.5 font-mono text-xs font-bold text-black tabular-nums">{{ $payment->invoice->user->student_no ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span
                            class="rounded-full bg-brand-soft px-2.5 py-0.5 text-xs font-semibold text-brand border border-brand/20">
                            {{ $payment->category->name ?? ($payment->invoice->period->category->name ?? 'Uang Kas') }}
                        </span>
                        <span class="font-semibold text-black">{{ $payment->invoice->period->title }}</span>
                        <span class="text-black/30">·</span>
                        <span
                            class="font-bold text-black tabular-nums">Rp{{ number_format($payment->amount, 0, ',', '.') }}</span>
                        @if ($payment->amount < $payment->invoice->remainingAmount())
                            <span
                                class="rounded-full bg-[#fff1d6] px-2.5 py-0.5 text-[11px] font-semibold text-[#7a4a00] border border-[#fed7aa]">
                                Cicilan (Tagihan Rp{{ number_format($payment->invoice->amount, 0, ',', '.') }})
                            </span>
                        @endif
                        @if ($payment->invoice->isOverdue())
                            <span
                                class="rounded-full bg-[#fde4e0] px-2.5 py-0.5 text-[11px] font-semibold text-[#8f1d0e] border border-[#fecaca]">
                                Terlambat {{ $payment->invoice->overdueDays() }} hari
                            </span>
                        @endif
                        @if (!empty($payment->gateway_response['paid_by_staff_name']))
                            <span
                                class="rounded-full bg-navy/10 px-2.5 py-0.5 text-[11px] font-semibold text-navy border border-navy/20">
                                Disetor via Bendahara: {{ $payment->gateway_response['paid_by_staff_name'] }}
                            </span>
                        @endif
                        <span class="text-black/30">·</span>
                        <span class="font-mono text-xs text-muted">{{ $payment->order_id }}</span>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <form method="POST" action="{{ route('payments.approve', $payment) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-5 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                Verifikasi lunas
                            </button>
                        </form>

                        <details class="group relative">
                            <summary
                                class="inline-flex min-h-11 cursor-pointer list-none items-center justify-center rounded-xl border border-[#fde4e0] bg-[#fff5f5] px-4 text-xs font-semibold text-[#8f1d0e] transition-colors hover:bg-[#fde4e0] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#8f1d0e]">
                                Tolak bukti
                            </summary>
                            <form method="POST" action="{{ route('payments.reject', $payment) }}"
                                class="mt-2.5 flex flex-col gap-2.5 rounded-2xl border border-[#fde4e0] bg-[#fff5f5] p-4 shadow-xl sm:w-80">
                                @csrf
                                <label class="flex flex-col gap-1 text-xs font-semibold text-[#8f1d0e]">
                                    <span>Alasan penolakan (opsional)</span>
                                    <input type="text" name="rejection_note"
                                        placeholder="Contoh: Bukti buram / nominal salah"
                                        class="min-h-10 w-full rounded-xl border border-black/15 bg-white px-3 text-xs text-black placeholder:text-muted/60 focus:border-[#8f1d0e] focus:outline-none focus:ring-2 focus:ring-[#8f1d0e]/20">
                                </label>
                                <button type="submit"
                                    class="inline-flex min-h-10 items-center justify-center rounded-xl bg-[#8f1d0e] px-3.5 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-[#701509] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#8f1d0e]">
                                    Konfirmasi tolak
                                </button>
                            </form>
                        </details>
                    </div>
                </div>
            </div>
        @empty
            <p class="px-6 py-12 text-center text-xs text-muted">Belum ada bukti pembayaran untuk direview.</p>
        @endforelse
    </section>
</x-layouts.app>
