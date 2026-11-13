<x-layouts.app title="Dashboard">
    {{-- Header Notion / Figma Style --}}
    <header class="flex flex-col gap-4">
        <div class="flex items-center  gap-2 text-xs font-semibold text-muted">
            <span class="hover:text-black transition-colors">KK 1A</span>
            <span class="text-black/20">/</span>
            <span class="text-black">Dashboard</span>
            <span class="text-black/20">·</span>
            <span class="font-normal">{{ now()->translatedFormat('l, d F Y') }}</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-black">Halo, {{ $user->name }}</h1>
        <p class="max-w-xl font-serif text-base sm:text-lg leading-relaxed text-muted">
            @if ($user->isAdmin())
                Ringkasan kas, tagihan, dan antrean administrasi kelas.
            @elseif ($user->canManageMembers())
                Ringkasan verifikasi anggota dan pembayaran yang perlu ditinjau.
            @else
                Pantau tagihan dan riwayat pembayaran kas kelasmu.
            @endif
        </p>
    </header>

    {{-- Notion-style Ledger Integrity Banner --}}
    <section aria-label="Status integritas ledger"
        class="flex flex-col gap-4 rounded-2xl border border-line bg-white p-5 shadow-xs sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-4">
            <div
                class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $brokenSeq === null ? 'bg-[#e6f3fe] text-brand border border-brand/20' : 'bg-[#fff5f5] text-[#8f1d0e] border border-[#fde4e0]' }}">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
            </div>
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2.5">
                    <h2 class="text-sm font-bold text-black">Integritas ledger:
                        {{ $brokenSeq === null ? 'Utuh' : 'Terputus' }}</h2>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $brokenSeq === null ? 'bg-[#dcfce7] text-[#14532d] border border-[#bbf7d0]' : 'bg-[#fde4e0] text-[#8f1d0e] border border-[#fecaca]' }}">
                        {{ $brokenSeq === null ? 'Valid' : 'Audit Error' }}
                    </span>
                </div>
                <p class="text-xs text-muted leading-relaxed">
                    {{ $brokenSeq === null ? 'Rantai hash tervalidasi.' : 'Entri pertama yang tidak cocok: #' . $brokenSeq }}
                    Data dienkripsi berurutan dengan SHA-256 dan tidak dapat dimanipulasi secara sepihak.
                </p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2.5 sm:self-center">
            <a href="{{ route('ledger') }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-4 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Lihat ledger
            </a>
            <form method="POST" action="{{ route('ledger.verify') }}" class="inline">
                @csrf
                <button type="submit"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Periksa ledger
                </button>
            </form>
        </div>
    </section>


    {{-- Notion / Figma Stat Tiles Carousel --}}
    <section aria-label="Ringkasan" class="relative min-w-0">

        @php
            if ($user->isAdmin()) {
                $stats = [
                    ['Saldo kas', 'Rp' . number_format($balance, 0, ',', '.'), 'Saldo dihitung langsung dari ledger'],
                    ['Anggota aktif', $activeMembers, 'Anggota yang dapat ditagih'],
                    [
                        'Tagihan belum lunas',
                        'Rp' . number_format($unpaidTotal, 0, ',', '.'),
                        'Total tagihan seluruh anggota',
                    ],
                    ['Tingkat pelunasan', $paidRate . '%', 'Persentase invoice yang sudah lunas'],
                    ['Periode tagihan', $periodCount, 'Periode yang pernah dibuat'],
                    ['Bukti menunggu review', $pendingPayments, 'Pembayaran yang siap diperiksa'],
                    ['Menunggu verifikasi anggota', $pendingMembers, 'Pendaftar aktif di antrean'],
                ];
            } elseif ($user->canManageMembers()) {
                $stats = [
                    ['Anggota aktif', $activeMembers, 'Terdaftar dan aktif'],
                    ['Menunggu verifikasi anggota', $pendingMembers, 'Perlu keputusan admin'],
                    ['Bukti menunggu review', $pendingPayments, 'Pembayaran siap diperiksa'],
                    ['Saldo kas', 'Rp' . number_format($balance, 0, ',', '.'), 'Sumber data: ledger'],
                ];
            } else {
                $stats = [
                    [
                        'Tagihan belum lunas',
                        'Rp' . number_format($unpaidTotal, 0, ',', '.'),
                        'Jumlah yang perlu dibayar',
                    ],
                    ['Tagihan lunas', $paidInvoices, 'Invoice yang sudah dibayar'],
                    ['Bukti menunggu review', $pendingPayments, 'Bukti pembayaran yang dikirim'],
                    [
                        'Saldo kas kelas',
                        'Rp' . number_format($balance, 0, ',', '.'),
                        'Transparan dan dihitung dari ledger',
                    ],
                ];
            }
        @endphp

        {{-- Carousel Header --}}
        <div class="mb-4 flex items-center justify-between gap-4">
            <h2 class="text-sm font-semibold tracking-tight text-black">
                Ringkasan
            </h2>

            <div class="flex items-center gap-2">
                <button type="button"
                    class="stats-prev flex h-8 w-8 items-center justify-center rounded-full border border-line bg-white text-black transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="Statistik sebelumnya">
                    <span aria-hidden="true">&larr;</span>
                </button>

                <button type="button"
                    class="stats-next flex h-8 w-8 items-center justify-center rounded-full border border-line bg-white text-black transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="Statistik berikutnya">
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </div>

        {{-- Swiper --}}
        <div class="swiper stats-swiper min-w-0">
            <div class="swiper-wrapper">

                @foreach ($stats as [$label, $value, $detail])
                    <div class="swiper-slide !h-auto">
                        <article
                            class="flex h-full min-h-32 flex-col justify-between rounded-2xl border border-line bg-white p-5 shadow-xs transition-all hover:border-black/20 hover:shadow-sm">

                            <h3 class="text-[11px] font-semibold uppercase tracking-wider text-muted">
                                {{ $label }}
                            </h3>

                            <p
                                class="my-2 break-words text-2xl font-bold tracking-tight text-black tabular-nums sm:text-3xl">
                                {{ $value }}
                            </p>

                            <p class="text-[11px] text-muted">
                                {{ $detail }}
                            </p>

                        </article>
                    </div>
                @endforeach

            </div>
        </div>

    </section>

    {{-- Kalender kas bulanan --}}
    <x-cash-calendar :calendar="$cashCalendar" />

    {{-- Role Specific Content --}}
    @if ($user->isAdmin())
        <section aria-labelledby="periode-terbaru"
            class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                <div>
                    <h2 id="periode-terbaru" class="text-base font-bold text-black">Periode terbaru</h2>
                    <p class="text-xs text-muted">Pantau jumlah invoice yang sudah lunas per tagihan.</p>
                </div>
                <a href="{{ route('periods.index') }}"
                    class="inline-flex min-h-11 items-center rounded-xl px-3 text-xs font-semibold text-brand hover:bg-brand-soft transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Kelola periode &rarr;
                </a>
            </div>
            <div class="divide-y divide-black/5">
                @forelse ($latestPeriods as $period)
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 hover:bg-black/[0.02] transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-black">{{ $period->title }}</p>
                            <p class="text-xs text-muted">Jatuh tempo {{ $period->due_date->format('d M Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="text-sm font-bold text-black tabular-nums">Rp{{ number_format($period->amount, 0, ',', '.') }}</span>
                            <span class="rounded-full bg-black/5 px-2.5 py-0.5 text-xs font-medium text-black">
                                {{ $period->paid_count }} / {{ $period->invoices_count }} lunas
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-xs text-muted">Belum ada periode tagihan.</p>
                @endforelse
            </div>
        </section>

        <nav aria-label="Aksi admin" class="flex flex-wrap gap-3">
            <a href="{{ route('payments.review') }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-xs sm:text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Review pembayaran ({{ $pendingPayments }})
            </a>
            <a href="{{ route('members.index') }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-4 text-xs sm:text-sm font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Verifikasi anggota ({{ $pendingMembers }})
            </a>
            <a href="{{ route('categories.index') }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-4 text-xs sm:text-sm font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Kelola kategori
            </a>
        </nav>
    @elseif ($user->canManageMembers())
        <nav aria-label="Aksi pengelola" class="flex flex-wrap gap-3">
            <a href="{{ route('reports.payments') }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-xs sm:text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Laporan & Bantu Bayar Siswa
            </a>
            <a href="{{ route('members.index') }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-4 text-xs sm:text-sm font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Lihat antrean anggota ({{ $pendingMembers }})
            </a>
            <a href="{{ route('periods.index') }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-4 text-xs sm:text-sm font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Tagihan kelas
            </a>
            <a href="{{ route('categories.index') }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-4 text-xs sm:text-sm font-semibold text-black shadow-xs transition-colors hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Kategori tagihan
            </a>
        </nav>

        {{-- Tagihan Kas Pribadi Staff (Bendahara) --}}
        @if ($unpaidInvoices->isNotEmpty())
            <section aria-labelledby="tagihan-kas-staff"
                class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
                <div class="border-b border-line px-5 py-4">
                    <h2 id="tagihan-kas-staff" class="text-base font-bold text-black">Tagihan kas pribadimu</h2>
                    <p class="text-xs text-muted">Sebagai bendahara/staff kelas, Anda juga memiliki kewajiban iuran kas
                        kelas.</p>
                </div>
                <div class="divide-y divide-black/5">
                    @foreach ($unpaidInvoices as $invoice)
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 hover:bg-black/[0.02] transition-colors">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-sm text-black">{{ $invoice->period->title }}</p>
                                    @if ($invoice->period->category)
                                        <span
                                            class="rounded-full bg-brand-soft px-2 py-0.5 text-[10px] font-semibold text-brand border border-brand/20">
                                            {{ $invoice->period->category->name }}
                                        </span>
                                    @endif
                                    @if ($invoice->isPartial())
                                        <span
                                            class="rounded-full bg-[#fff1d6] px-2 py-0.5 text-[10px] font-semibold text-[#7a4a00] border border-[#fed7aa]">
                                            Menyicil
                                        </span>
                                    @endif
                                    @if ($invoice->isOverdue())
                                        <span
                                            class="rounded-full bg-[#fde4e0] px-2 py-0.5 text-[10px] font-semibold text-[#8f1d0e] border border-[#fecaca]">
                                            Lewat Tenggat
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-muted">
                                    Jatuh tempo {{ $invoice->period->due_date->format('d M Y') }}
                                    @if ($invoice->isPartial())
                                        · Terbayar Rp{{ number_format($invoice->paidAmount(), 0, ',', '.') }} dari
                                        Rp{{ number_format($invoice->amount, 0, ',', '.') }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-sm font-bold text-black tabular-nums">Rp{{ number_format($invoice->remainingAmount(), 0, ',', '.') }}</span>
                                <a href="{{ route('payments.create', $invoice) }}"
                                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    {{ $invoice->isPartial() ? 'Lanjut Bayar' : 'Bayar Kas' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @else
        <div class="grid gap-6 xl:grid-cols-2">
            <section aria-labelledby="tagihan-belum-lunas"
                class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
                <div class="border-b border-line px-5 py-4">
                    <h2 id="tagihan-belum-lunas" class="text-base font-bold text-black">Tagihan belum lunas</h2>
                    <p class="text-xs text-muted">Pilih tagihan untuk membuka QRIS dan mengirim bukti transfer.</p>
                </div>
                <div class="divide-y divide-black/5">
                    @forelse ($unpaidInvoices as $invoice)
                        <div
                            class="flex flex-col gap-3 px-5 py-4 transition-colors hover:bg-black/[0.02] sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                            <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                                <p class="text-sm font-semibold text-black wrap-break-word">{{ $invoice->period->title }}</p>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if ($invoice->period->category)
                                        <span
                                            class="rounded-full border border-brand/20 bg-brand-soft px-2 py-0.5 text-[10px] font-semibold text-brand">{{ $invoice->period->category->name }}</span>
                                    @endif
                                    @if ($invoice->isPartial())
                                        <span
                                            class="rounded-full border border-[#fed7aa] bg-[#fff1d6] px-2 py-0.5 text-[10px] font-semibold text-[#7a4a00]">Menyicil</span>
                                    @endif
                                    @if ($invoice->isOverdue())
                                        <span
                                            class="rounded-full border border-[#fecaca] bg-[#fde4e0] px-2 py-0.5 text-[10px] font-semibold text-[#8f1d0e]">Lewat Tenggat</span>
                                    @endif
                                </div>
                                <p class="text-xs leading-relaxed text-muted">
                                    Jatuh tempo {{ $invoice->period->due_date->format('d M Y') }}
                                    @if ($invoice->isPartial())
                                        <span class="block sm:inline">· Terbayar
                                            Rp{{ number_format($invoice->paidAmount(), 0, ',', '.') }} dari
                                            Rp{{ number_format($invoice->amount, 0, ',', '.') }}</span>
                                    @endif
                                </p>
                            </div>
                            <div
                                class="flex items-center justify-between gap-3 border-t border-black/5 pt-3 sm:shrink-0 sm:justify-end sm:border-0 sm:pt-0">
                                <div class="flex flex-col sm:items-end">
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-muted">Sisa</span>
                                    <span
                                        class="text-base font-bold tabular-nums text-black">Rp{{ number_format($invoice->remainingAmount(), 0, ',', '.') }}</span>
                                </div>
                                <a href="{{ route('payments.create', $invoice) }}"
                                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-5 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    {{ $invoice->isPartial() ? 'Lanjut Bayar' : 'Bayar' }}
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-xs text-muted">Semua tagihan sudah lunas. Mantap!</p>
                    @endforelse
                </div>
            </section>

            <section aria-labelledby="riwayat-pembayaran"
                class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
                <div class="border-b border-line px-5 py-4">
                    <h2 id="riwayat-pembayaran" class="text-base font-bold text-black">Riwayat pembayaran</h2>
                    <p class="text-xs text-muted">Lima transaksi pembayaran terakhirmu.</p>
                </div>
                <div class="divide-y divide-black/5">
                    @forelse ($latestPayments as $payment)
                        <div
                            class="flex items-start justify-between gap-3 px-5 py-4 transition-colors hover:bg-black/[0.02]">
                            <div class="flex min-w-0 flex-col gap-0.5">
                                <p class="text-sm font-semibold text-black wrap-break-word">
                                    {{ $payment->invoice->period->title }}</p>
                                <p class="text-xs text-muted">
                                    {{ $payment->created_at->format('d M Y') }}
                                    @if ($payment->category)
                                        · <span class="font-medium text-brand">{{ $payment->category->name }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1.5">
                                <span
                                    class="text-sm font-bold tabular-nums text-black">Rp{{ number_format($payment->amount, 0, ',', '.') }}</span>
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $payment->status === 'paid' ? 'bg-[#dcfce7] text-[#14532d]' : ($payment->status === 'reversed' ? 'bg-[#fde4e0] text-[#8f1d0e]' : 'bg-[#fff1d6] text-[#7a4a00]') }}">
                                    {{ $payment->status === 'paid' ? 'Lunas' : ($payment->status === 'reversed' ? 'Dibatalkan' : ($payment->proof_path ? 'Menunggu verifikasi' : 'Belum dibayar')) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-xs text-muted">Belum ada riwayat pembayaran.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif

    <script nonce="{{ Vite::cspNonce() }}">
        document.addEventListener('DOMContentLoaded', () => {
            const statsCarousel = document.querySelector('.stats-swiper');

            if (!statsCarousel || typeof Swiper === 'undefined') return;

            new Swiper(statsCarousel, {
                slidesPerView: 1.15,
                spaceBetween: 16,
                speed: 450,
                grabCursor: true,
                watchOverflow: true,

                navigation: {
                    nextEl: '.stats-next',
                    prevEl: '.stats-prev',
                },

                breakpoints: {
                    480: {
                        slidesPerView: 1.5,
                    },
                    640: {
                        slidesPerView: 2,
                    },
                    1024: {
                        slidesPerView: 3,
                    },
                    1280: {
                        slidesPerView: 4,
                    },
                },
            });
        });
    </script>
</x-layouts.app>
