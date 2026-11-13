<x-dynamic-component :component="$isGuest ? 'layouts.public' : 'layouts.app'" title="Pengeluaran kas">
    <header class="flex flex-col gap-2">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-xs font-semibold text-muted">
                <span class="hover:text-black transition-colors">KK 1A</span>
                <span class="text-black/20">/</span>
                <span class="text-black">Pengeluaran</span>
                <span class="text-black/20">·</span>
                <span class="font-normal">{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </header>

    {{-- Notion / Figma Stat Tiles Carousel --}}
    <section aria-label="Ringkasan" class="relative min-w-0">



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

                @php
                    $sf = [
                        [
                            'Saldo kas tersedia',
                            'Rp' . number_format($stats['totalExpense'], 0, ',', '.'),
                            'bisa di gunakan untuk keperluan',
                        ],
                        [
                            'Total pengeluaran tercatat',
                            'Rp' . number_format($stats['balance'], 0, ',', '.'),
                            'dari seluruh kegiatan',
                        ],
                        ['Jumlah transaksi', $stats['unpaidInvoices'], 'Pengeluaran di setujui'],
                    ];
                @endphp
                @foreach ($sf as [$label, $value, $detail])
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



    <div class="flex lg:flex-wrap flex-wrap-reverse items-start gap-6">
        <section aria-labelledby="judul-daftar"
            class="flex min-w-0 flex-[999_1_30rem] flex-col rounded-2xl border border-line bg-white shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-6 py-4.5">
                <h2 id="judul-daftar" class="text-lg font-bold text-black">Riwayat pengeluaran</h2>

                <form method="GET" action="{{ route('expenses.index') }}" class="flex items-center gap-2">
                    <label for="search-expense" class="sr-only">Cari nama pengeluaran</label>
                    <input id="search-expense" type="search" name="q" value="{{ $search ?? '' }}"
                        placeholder="Cari nama barang..."
                        class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-xs placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                    <button type="submit"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Cari</button>
                    @if (!empty($search))
                        <a href="{{ route('expenses.index') }}"
                            class="min-h-11 inline-flex items-center rounded-xl px-3 text-xs font-semibold text-muted hover:text-black transition-colors">Reset</a>
                    @endif
                </form>
            </div>

            <div class="divide-y divide-black/5">
                @forelse ($expenses as $expense)
                    <div
                        class="flex flex-wrap items-start justify-between gap-4 px-6 py-4.5 hover:bg-black/[0.015] transition-colors">
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-[15px] font-semibold text-black">{{ $expense->title }}</span>
                                @if ($expense->receipt_path && $isGuest)
                                    <span
                                        class="inline-flex items-center rounded-full bg-[#f1efed] px-2.5 py-0.5 text-xs font-semibold text-[#3d3a37] border border-black/10">
                                        Nota tersedia untuk anggota
                                    </span>
                                @elseif ($expense->receipt_path)
                                    <a href="{{ route('expenses.receipt', $expense) }}" target="_blank" rel="noopener"
                                        class="inline-flex items-center gap-1 rounded-full bg-brand-soft px-2.5 py-0.5 text-xs font-semibold text-brand border border-brand/20 hover:underline">
                                        Lihat nota
                                    </a>
                                @endif
                            </div>
                            @if ($expense->notes && !$isGuest)
                                <p class="text-xs text-muted leading-relaxed">{{ $expense->notes }}</p>
                            @endif
                            <span class="text-xs text-muted">Dicatat oleh <span
                                    class="font-medium text-black">{{ $isGuest ? 'Pengurus kelas' : $expense->creator->name }}</span>
                                ·
                                {{ $expense->spent_at->format('d M Y') }}</span>
                        </div>
                        <span
                            class="shrink-0 font-bold text-[#8f1d0e] tabular-nums text-sm sm:text-base">-Rp{{ number_format($expense->amount, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <p class="px-6 py-12 text-center text-xs text-muted">
                        {{ !empty($search) ? 'Tidak ditemukan pengeluaran yang sesuai pencarian.' : 'Belum ada pengeluaran yang dicatat.' }}
                    </p>
                @endforelse
            </div>

            @if ($expenses->hasPages())
                <div class="border-t border-line p-4.5">
                    {{ $expenses->links() }}
                </div>
            @endif
        </section>

        @if (auth()->user()?->canManageMembers())
            <section aria-labelledby="judul-tambah"
                class="flex min-w-72 flex-[1_1_18rem] flex-col gap-5 rounded-2xl border border-line bg-white p-6 shadow-xs">
                <div class="flex flex-col gap-1">
                    <h2 id="judul-tambah" class="text-lg font-bold text-black">Catat pengeluaran</h2>
                    <span class="text-xs text-muted">Otomatis mencatat EXPENSE_APPROVED ke ledger.</span>
                </div>

                <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data"
                    class="flex flex-col gap-4">
                    @csrf
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                        <span>Keperluan / Nama barang</span>
                        <input type="text" name="title" value="{{ old('title') }}"
                            placeholder="Contoh: Spidol & penghapus papan" required
                            class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                        @error('title')
                            <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                        <span>Nominal (Rp)</span>
                        <input type="number" name="amount" value="{{ old('amount') }}" placeholder="15000"
                            min="500" required
                            class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                        @error('amount')
                            <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                        <span>Tanggal pengeluaran</span>
                        <input type="date" name="spent_at" value="{{ old('spent_at', now()->toDateString()) }}"
                            max="{{ now()->toDateString() }}" required
                            class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                        @error('spent_at')
                            <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                        <span>Foto nota / struk (opsional)</span>
                        <input type="file" name="receipt" accept="image/jpeg,image/png,image/webp,application/pdf"
                            class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 py-2 text-xs font-normal text-black focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                        @error('receipt')
                            <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                        <span>Catatan tambahan (opsional)</span>
                        <textarea name="notes" rows="2" placeholder="Keterangan toko atau rincian barang"
                            class="rounded-xl border border-black/15 bg-canvas/30 p-3 text-sm font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">{{ old('notes') }}</textarea>
                        @error('notes')
                            <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                        @enderror
                    </label>

                    <button type="submit"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Simpan
                        pengeluaran</button>
                </form>
            </section>
        @endif
    </div>
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
</x-dynamic-component>
