<x-layouts.public title="Dashboard publik">
    <header class="flex flex-col gap-4">
        <div class="flex items-center gap-2 text-xs font-semibold text-muted">
            <span class="hover:text-black transition-colors">Kas Kelas 1A</span>
            <span class="text-black/20">/</span>
            <span class="text-black">Dashboard</span>
            <span class="text-black/20">·</span>
            <span class="font-normal">{{ now()->translatedFormat('l, d F Y') }}</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-black">
            Halo{{ Auth::user() ? ', ' . Auth::user()->name : '' }}</h1>
        <p class="max-w-xl font-serif text-base sm:text-lg leading-relaxed text-muted">
            @if (Auth::user())
                @if ($user->isAdmin())
                    Ringkasan kas, tagihan, dan antrean administrasi kelas.
                @elseif ($user->canManageMembers())
                    Ringkasan verifikasi anggota dan pembayaran yang perlu ditinjau.
                @else
                    Pantau tagihan dan riwayat pembayaran kas kelasmu.
                @endif
            @else
                Ringkasan kas dan tagihan kelas 1A
            @endif
        </p>
    </header>

    {{-- Notion / Figma Stat Tiles Carousel --}}
    <section aria-label="Ringkasan" class="relative min-w-0">

        @php
            $stats = [
                ['Saldo kas', 'Rp' . number_format($balance, 0, ',', '.'), 'Dihitung dari ledger'],
                ['Anggota aktif', $activeMembers, 'Anggota yang ditagih'],
                ['Tingkat pelunasan', $paidRate . '%', 'Tagihan yang sudah lunas'],
                [
                    'Integritas ledger',
                    $brokenSeq === null && $anchorsIntact ? 'Utuh' : 'Terputus',
                    match (true) {
                        $brokenSeq !== null => 'Entri bermasalah: #' . $brokenSeq,
                        !$anchorsIntact => 'Anchor harian tidak cocok',
                        default => 'Rantai hash SHA-256 valid',
                    },
                ],
            ];
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

    @if ($latestAnchor)
        <section aria-labelledby="judul-anchor"
            class="flex flex-col gap-4 rounded-2xl bg-navy p-5 text-white shadow-xs sm:p-6">
            <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
                <div class="flex flex-col gap-1">
                    <h2 id="judul-anchor" class="text-xs font-semibold uppercase tracking-wider text-white/70">Anchor
                        harian</h2>
                    <p class="text-sm font-semibold">{{ $latestAnchor->anchored_on->translatedFormat('d F Y') }} · entri
                        #{{ $latestAnchor->seq }}</p>
                </div>
                <p class="font-mono text-2xl font-bold tracking-tight sm:text-3xl">
                    {{ substr($latestAnchor->hash, 0, 4) }}…{{ substr($latestAnchor->hash, -4) }}</p>
            </div>
            <p class="break-all font-mono text-[11px] leading-relaxed text-white/60">{{ $latestAnchor->hash }}</p>
            <p class="border-t border-white/10 pt-4 text-xs leading-relaxed text-white/80">Simpan atau tangkap layar
                anchor ini, lalu bandingkan nanti dengan hash entri #{{ $latestAnchor->seq }} di halaman Ledger.</p>
        </section>
    @endif

    <x-cash-calendar :calendar="$cashCalendar" />

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
</x-layouts.public>
