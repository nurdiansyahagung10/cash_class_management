<x-layouts.app title="Review pembayaran">
    <header class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-xs font-semibold text-muted">
            <span class="transition-colors hover:text-black">Kas Kelas 1A</span>
            <span class="text-black/20">/</span>
            <span class="text-black">Review pembayaran</span>
            <span class="text-black/20">·</span>
            <span class="font-normal">{{ now()->translatedFormat('l, d F Y') }}</span>
        </div>
    </header>

    @php
        $baseQuery = array_filter(
            ['period_id' => $selectedPeriodId, 'category_id' => $selectedCategoryId, 'q' => $search],
            fn($v) => $v !== null && $v !== '',
        );
        $hasFilter = $baseQuery !== [];
    @endphp

    <section class="flex flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-xs"
        aria-labelledby="judul-review">
        <div class="flex flex-col gap-4 border-b border-line px-4 py-5 sm:px-6">
            <div class="flex flex-col gap-1.5">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <h1 id="judul-review" class="text-base font-bold text-black sm:text-lg">Review pembayaran</h1>
                    <span
                        class="inline-flex items-center rounded-full border border-brand/20 bg-brand-soft px-3 py-1 text-xs font-semibold tabular-nums text-brand">
                        {{ $payments->total() }} {{ $tab === 'approved' ? 'disetujui' : 'menunggu' }}
                    </span>
                </div>
                <p class="max-w-xl text-xs leading-relaxed text-muted">
                    Verifikasi bukti transfer anggota, atau telusuri riwayat pembayaran yang sudah disetujui.
                </p>
            </div>

            {{-- Tab --}}
            <div class="flex flex-wrap items-center gap-2 text-xs" role="group" aria-label="Status review">
                @foreach ([['pending', 'Menunggu review', $pendingCount], ['approved', 'Sudah disetujui', $approvedCount]] as [$key, $label, $count])
                    <a href="{{ route('payments.review', $baseQuery + ['tab' => $key]) }}"
                        @if ($tab === $key) aria-current="true" @endif @class([
                            'inline-flex min-h-9 items-center gap-1.5 whitespace-nowrap rounded-full border px-3.5 font-semibold transition-colors',
                            'border-black bg-black text-white' => $tab === $key,
                            'border-black/10 bg-white text-muted hover:bg-canvas hover:text-black' =>
                                $tab !== $key,
                        ])>
                        {{ $label }}
                        <span @class([
                            'rounded-full px-1.5 text-[11px] tabular-nums',
                            'bg-white/20' => $tab === $key,
                            'bg-black/5' => $tab !== $key,
                        ])>{{ $count }}</span>
                    </a>
                @endforeach
            </div>

            {{-- Filter --}}
            <form method="GET" action="{{ route('payments.review') }}"
                class="grid grid-cols-2 gap-2 xl:flex xl:flex-wrap xl:items-center">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <select name="period_id" aria-label="Filter periode"
                    class="min-h-11 w-full min-w-0 rounded-xl border border-black/15 bg-white px-3 text-xs font-medium text-black focus:border-brand focus:outline-none sm:min-h-10 xl:w-auto xl:min-w-44">
                    <option value="">Semua periode</option>
                    @foreach ($periods as $p)
                        <option value="{{ $p->id }}" @selected($selectedPeriodId === $p->id)>{{ $p->title }}</option>
                    @endforeach
                </select>

                <select name="category_id" aria-label="Filter kategori"
                    class="min-h-11 w-full min-w-0 rounded-xl border border-black/15 bg-white px-3 text-xs font-medium text-black focus:border-brand focus:outline-none sm:min-h-10 xl:w-auto xl:min-w-44">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected($selectedCategoryId === $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>

                <div class="col-span-2 flex min-w-0 gap-2 xl:ml-auto">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama / NIM..."
                        aria-label="Cari nama atau NIM"
                        class="min-h-11 min-w-0 flex-1 rounded-xl border border-black/15 bg-canvas/30 px-3 text-xs text-black placeholder:text-muted/60 focus:border-brand focus:bg-white focus:outline-none sm:min-h-10 xl:w-52 xl:flex-none">
                    <button type="submit"
                        class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-brand px-4 text-xs font-semibold text-white shadow-xs transition hover:bg-brand-dark sm:min-h-10">Terapkan</button>
                    @if ($hasFilter)
                        <a href="{{ route('payments.review', ['tab' => $tab]) }}"
                            class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-muted transition-colors hover:bg-canvas hover:text-black sm:min-h-10">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="divide-y divide-black/5">
            @forelse ($payments as $payment)
                @php
                    $invoice = $payment->invoice;
                    $group = $tab === 'pending' && $payment->batch_key ? ($batches[$payment->batch_key] ?? null) : null;
                    $categoryName = $payment->category->name ?? ($invoice->period->category->name ?? 'Uang Kas');
                    $staffPayment = $invoice->user->isStaff();
                    $canReview = !$staffPayment || auth()->user()->isAdmin();
                @endphp
                <article
                    class="flex flex-col gap-4 px-4 py-5 transition-colors hover:bg-black/1.5 sm:flex-row sm:items-start sm:gap-5 sm:px-6">
                    @if ($payment->proof_path)
                        <a href="{{ route('payments.proof.show', $payment) }}" target="_blank" rel="noopener"
                            class="group relative block h-40 w-full shrink-0 overflow-hidden rounded-2xl border border-line bg-canvas/60 shadow-xs sm:size-28">
                            <img src="{{ route('payments.proof.show', $payment) }}" loading="lazy"
                                alt="Bukti transfer {{ $invoice->user->name }}"
                                class="size-full object-cover transition duration-200 group-hover:scale-105">
                            <span
                                class="absolute inset-x-0 bottom-0 bg-black/70 py-1 text-center text-[11px] font-semibold text-white">Buka
                                foto</span>
                        </a>
                    @endif

                    <div class="flex min-w-0 flex-1 flex-col gap-3">
                        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                            <div class="flex min-w-0 flex-col gap-1">
                                <strong
                                    class="text-base font-bold text-black wrap-break-word">{{ $invoice->user->name }}</strong>
                                <span class="text-xs text-muted">NIM <span
                                        class="font-mono font-bold text-black">{{ $invoice->user->student_no ?? '—' }}</span>
                                    · <span class="font-mono">{{ $payment->order_id }}</span></span>
                            </div>
                            <span
                                class="text-lg font-bold tabular-nums text-black">Rp{{ number_format($group ? $group->sum('amount') : $payment->amount, 0, ',', '.') }}</span>
                        </div>

                        @if ($group)
                            <div class="rounded-xl border border-brand/20 bg-brand-soft/40 px-3.5 py-2.5 text-xs leading-relaxed text-black">
                                <strong>Dibayar sekaligus · {{ $group->count() }} tagihan</strong>
                                <ul class="mt-1 text-muted">
                                    @foreach ($group as $row)
                                        <li>{{ $row->invoice->period->title }}: Rp{{ number_format($row->amount, 0, ',', '.') }}</li>
                                    @endforeach
                                </ul>
                                <span class="font-medium">Verifikasi atau tolak berlaku untuk semuanya.</span>
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                class="rounded-full border border-brand/20 bg-brand-soft px-2.5 py-0.5 text-[11px] font-semibold text-brand">{{ $categoryName }}</span>
                            <span
                                class="rounded-full border border-black/10 bg-white px-2.5 py-0.5 text-[11px] font-semibold text-black">{{ $invoice->period->title }}</span>

                            @if ($tab === 'approved')
                                <span
                                    class="rounded-full border border-[#bbf7d0] bg-[#dcfce7] px-2.5 py-0.5 text-[11px] font-semibold text-[#14532d]">Disetujui</span>
                            @elseif ($payment->amount < $invoice->remainingAmount())
                                <span
                                    class="rounded-full border border-[#fed7aa] bg-[#fff1d6] px-2.5 py-0.5 text-[11px] font-semibold text-[#7a4a00]">Cicilan
                                    · tagihan Rp{{ number_format($invoice->amount, 0, ',', '.') }}</span>
                            @endif

                            @if ($tab === 'pending' && $invoice->isOverdue())
                                <span
                                    class="rounded-full border border-[#fecaca] bg-[#fde4e0] px-2.5 py-0.5 text-[11px] font-semibold text-[#8f1d0e]">Terlambat
                                    {{ $invoice->overdueDays() }} hari</span>
                            @endif

                            @if ($staffPayment)
                                <span
                                    class="rounded-full border border-navy/20 bg-navy/10 px-2.5 py-0.5 text-[11px] font-semibold text-navy">Pembayaran
                                    staf</span>
                            @endif

                            @if (!empty($payment->gateway_response['paid_by_staff_name']))
                                <span
                                    class="rounded-full border border-navy/20 bg-navy/10 px-2.5 py-0.5 text-[11px] font-semibold text-navy">Via
                                    Bendahara: {{ $payment->gateway_response['paid_by_staff_name'] }}</span>
                            @endif
                        </div>

                        @if ($tab === 'approved')
                            <p class="text-xs text-muted">
                                Disetujui
                                <span
                                    class="font-semibold text-black">{{ ($payment->reviewed_at ?? ($payment->paid_at ?? $payment->updated_at))->translatedFormat('d M Y, H:i') }}</span>
                                @if ($payment->reviewer)
                                    oleh <span class="font-semibold text-black">{{ $payment->reviewer->name }}</span>
                                @endif
                            </p>
                        @elseif (!$canReview)
                            <p
                                class="flex items-start gap-2 rounded-xl border border-navy/15 bg-navy/5 px-3.5 py-2.5 text-xs leading-relaxed text-navy">
                                <svg class="mt-0.5 size-4 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4" />
                                </svg>
                                <span>Pembayaran ini dilakukan oleh staf, sehingga hanya <strong>admin</strong> yang
                                    dapat memverifikasinya.</span>
                            </p>
                        @else
                            <div class="flex flex-wrap items-start gap-2.5">
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
                                        class="mt-2.5 flex w-full flex-col gap-2.5 rounded-2xl border border-[#fde4e0] bg-[#fff5f5] p-4 sm:w-80">
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
                        @endif
                    </div>
                </article>
            @empty
                <div class="flex flex-col items-center gap-2 px-6 py-12 text-center">
                    <p class="text-sm text-muted">
                        @if ($hasFilter)
                            Tidak ada pembayaran yang cocok dengan filter.
                        @else
                            {{ $tab === 'approved' ? 'Belum ada pembayaran yang disetujui.' : 'Belum ada bukti pembayaran untuk direview.' }}
                        @endif
                    </p>
                    @if ($hasFilter)
                        <a href="{{ route('payments.review', ['tab' => $tab]) }}"
                            class="text-xs font-semibold text-brand hover:underline">Reset filter</a>
                    @endif
                </div>
            @endforelse
        </div>

        <x-report-pager :paginator="$payments" label="pembayaran" />
    </section>
</x-layouts.app>
