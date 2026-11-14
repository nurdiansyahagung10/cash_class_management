<x-layouts.app title="Periode tagihan">
    <header class="flex flex-col gap-2">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-xs font-semibold text-muted">
                <span class="hover:text-black transition-colors">KK 1A</span>
                <span class="text-black/20">/</span>
                <span class="text-black">Tagihan</span>
                <span class="text-black/20">·</span>
                <span class="font-normal">{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </header>

    <div class="flex lg:flex-wrap flex-wrap-reverse items-start gap-6">
        <section aria-labelledby="judul-periode"
            class="flex min-w-0 flex-[999_1_30rem] flex-col rounded-2xl border border-line bg-white shadow-xs">
            <div class="flex flex-col gap-3.5 border-b border-line px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <h2 id="judul-periode" class="text-base font-bold text-black sm:text-lg">Riwayat periode</h2>
                        <span
                            class="inline-flex items-center rounded-full border border-brand/20 bg-brand-soft px-3 py-1 text-xs font-semibold tabular-nums text-brand">{{ $periods->total() }}
                            periode</span>
                    </div>
                    <form method="GET" action="{{ route('periods.index') }}"
                        class="flex w-full min-w-0 items-center gap-2 sm:w-auto">
                        @if ($statusFilter)
                            <input type="hidden" name="status" value="{{ $statusFilter }}">
                        @endif
                        <label for="search-period" class="sr-only">Cari judul periode</label>
                        <input id="search-period" type="search" name="q" value="{{ $search }}"
                            placeholder="Cari judul periode..."
                            class="min-h-11 min-w-0 flex-1 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-xs placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20 sm:w-60 sm:flex-none">
                        <button type="submit"
                            class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            Cari
                        </button>
                    </form>
                </div>

                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                    <a href="{{ route('periods.index', array_filter(['q' => $search])) }}"
                        class="inline-flex min-h-9 items-center rounded-full border px-3.5 font-semibold transition-colors {{ empty($statusFilter) ? 'border-black bg-black text-white' : 'border-black/10 bg-white text-muted hover:bg-canvas' }}">
                        Semua
                    </a>
                    <a href="{{ route('periods.index', array_filter(['status' => 'active', 'q' => $search])) }}"
                        class="inline-flex min-h-9 items-center rounded-full border px-3.5 font-semibold transition-colors {{ $statusFilter === 'active' ? 'border-brand bg-brand text-white shadow-xs' : 'border-black/10 bg-white text-muted hover:bg-canvas' }}">
                        Berjalan
                    </a>
                    <a href="{{ route('periods.index', array_filter(['status' => 'overdue', 'q' => $search])) }}"
                        class="inline-flex min-h-9 items-center rounded-full border px-3.5 font-semibold transition-colors {{ $statusFilter === 'overdue' ? 'border-[#8f1d0e] bg-[#8f1d0e] text-white shadow-xs' : 'border-black/10 bg-white text-muted hover:bg-canvas' }}">
                        Lewat Tempo
                    </a>
                    <a href="{{ route('periods.index', array_filter(['status' => 'completed', 'q' => $search])) }}"
                        class="inline-flex min-h-9 items-center rounded-full border px-3.5 font-semibold transition-colors {{ $statusFilter === 'completed' ? 'border-[#14532d] bg-[#14532d] text-white shadow-xs' : 'border-black/10 bg-white text-muted hover:bg-canvas' }}">
                        Lunas Semua
                    </a>
                </div>
            </div>

            <div class="divide-y divide-black/5">
                @forelse ($periods as $period)
                    @php
                        $isCompleted = $period->invoices_count > 0 && $period->paid_count === $period->invoices_count;
                        $isOverdue = $period->isPastDue() && !$isCompleted;
                    @endphp
                    <div
                        class="flex flex-wrap items-center gap-x-4 gap-y-2 px-6 py-4.5 hover:bg-black/[0.015] transition-colors">
                        <div class="flex min-w-0 flex-1 basis-48 flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('periods.show', $period) }}"
                                    class="text-[15px] font-bold text-brand hover:underline">{{ $period->title }}
                                    &rarr;</a>
                                <span
                                    class="rounded-full bg-brand-soft px-2.5 py-0.5 text-[11px] font-semibold text-brand border border-brand/20">
                                    {{ $period->category->name ?? 'Uang Kas' }}
                                </span>
                                @if ($isCompleted)
                                    <span
                                        class="rounded-full bg-[#dcfce7] px-2.5 py-0.5 text-[11px] font-semibold text-[#14532d] border border-[#bbf7d0]">Lunas
                                        Semua</span>
                                @elseif ($isOverdue)
                                    <span
                                        class="rounded-full bg-[#fde4e0] px-2.5 py-0.5 text-[11px] font-semibold text-[#8f1d0e] border border-[#fecaca]">Lewat
                                        Tempo</span>
                                @else
                                    <span
                                        class="rounded-full bg-[#fff1d6] px-2.5 py-0.5 text-[11px] font-semibold text-[#7a4a00] border border-[#fed7aa]">Berjalan</span>
                                @endif
                                @if ($period->qris_url && str_starts_with($period->qris_url, '000201'))
                                    <span
                                        class="rounded-full bg-[#e6f3fe] px-2.5 py-0.5 text-[10px] font-semibold text-brand">QRIS
                                        Dinamis</span>
                                @endif
                            </div>
                            <span class="text-xs text-muted">Jatuh tempo
                                {{ $period->due_date->format('d M Y') }}</span>
                        </div>
                        <span
                            class="text-sm font-bold text-black tabular-nums">Rp{{ number_format($period->amount, 0, ',', '.') }}</span>
                        <a href="{{ route('periods.show', $period) }}"
                            class="rounded-full bg-black/5 px-3 py-1 text-xs font-semibold text-[#3d3a37] transition-colors hover:bg-black/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            {{ $period->paid_count }} / {{ $period->invoices_count }} lunas
                        </a>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center">
                        <p class="text-sm text-muted">Tidak ada periode tagihan yang cocok.</p>
                        @if ($search || $statusFilter)
                            <a href="{{ route('periods.index') }}"
                                class="text-xs font-semibold text-brand hover:underline">Reset pencarian & filter</a>
                        @endif
                    </div>
                @endforelse
            </div>

            <x-report-pager :paginator="$periods" label="periode" />
        </section>

        <section aria-labelledby="judul-buat"
            class="flex min-w-72 flex-[1_1_18rem] flex-col gap-5 rounded-2xl border border-line bg-white p-6 shadow-xs">
            <div class="flex flex-col gap-1">
                <h2 id="judul-buat" class="text-lg font-bold text-black">Buat periode</h2>
                <span class="text-xs text-muted">Berlaku untuk {{ $activeMembers }} anggota aktif.</span>
            </div>

            <form method="POST" action="{{ route('periods.store') }}" enctype="multipart/form-data"
                class="flex flex-col gap-4">
                @csrf
                <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                    <span>Judul Periode Tagihan</span>
                    <input type="text" name="title" value="{{ old('title') }}"
                        placeholder="Contoh: Kas Minggu 9 / Jaket Angkatan" required
                        class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                    @error('title')
                        <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                    @enderror
                </label>

                <div class="flex flex-col gap-2.5 rounded-2xl border border-line bg-canvas/40 p-4">
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                        <span>Kategori Tagihan</span>
                        <select name="category_id"
                            class="min-h-11 rounded-xl border border-black/15 bg-white px-3 text-sm font-normal text-black focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="flex flex-col gap-1 text-xs text-muted">
                        <span class="font-medium">Atau buat kategori baru:</span>
                        <input type="text" name="new_category" value="{{ old('new_category') }}"
                            placeholder="Ketik nama kategori baru jika belum ada"
                            class="min-h-10 rounded-xl border border-black/15 bg-white px-3 text-xs text-black placeholder:text-muted/60 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                        @error('new_category')
                            <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                    <span>Nominal per Anggota (Rp)</span>
                    <input type="number" name="amount" id="series-amount" value="{{ old('amount') }}"
                        placeholder="10000" required
                        class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                    @error('amount')
                        <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                    @enderror
                </label>


                {{-- Mode Pembayaran --}}
                <div class="space-y-3">

                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold text-black">
                                Mode pembayaran
                            </p>
                            <p class="mt-1 text-[11px] text-muted">
                                Pilih pembayaran biasa atau bertahap.
                            </p>
                        </div>

                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <span class="text-xs font-medium text-muted">
                                Bertahap
                            </span>

                            <input type="checkbox" id="installment-toggle" class="peer sr-only"
                                @checked((int) old('installments', 1) > 1)>

                            <span
                                class="relative h-6 w-11 rounded-full bg-black/15 transition peer-checked:bg-brand
                after:absolute after:left-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:shadow-sm after:transition-transform
                peer-checked:after:translate-x-5">
                            </span>
                        </label>
                    </div>

                    {{-- Pembayaran Biasa --}}
                    <div id="single-payment-field">
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                            <span>Jatuh tempo</span>

                            <input type="date" name="due_date" id="due-date-input" value="{{ old('due_date') }}"
                                required
                                class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">

                            <span class="text-[11px] font-normal text-muted">
                                Berlaku sampai pukul 23.59 WIB.
                            </span>

                            @error('due_date')
                                <span role="alert" class="text-xs font-medium text-[#8f1d0e]">
                                    {{ $message }}
                                </span>
                            @enderror
                        </label>
                    </div>

                    {{-- Pembayaran Bertahap --}}
                    <div id="series-trigger-wrapper" class="hidden">
                        <div class="rounded-2xl border border-line bg-canvas/40 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-black">
                                        Pembayaran bertahap
                                    </p>

                                    <p id="series-trigger-description" class="mt-1 text-xs text-muted">
                                        Bagi tagihan menjadi beberapa pembayaran.
                                    </p>
                                </div>

                                <button type="button" id="series-open"
                                    class="flex min-h-10 shrink-0 items-center gap-2 rounded-xl border border-black/10 bg-white px-3 text-xs font-semibold text-black transition hover:border-brand hover:text-brand focus-visible:outline-2 focus-visible:outline-brand">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 5v14M5 12h14" />
                                    </svg>

                                    <span id="series-trigger-label">Atur</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Floating Modal --}}
                <div id="series-modal"
                    class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/40 p-3 backdrop-blur-[3px] sm:p-5"
                    role="dialog" aria-modal="true" aria-labelledby="series-modal-title" aria-hidden="true">
                    <div id="series-modal-panel"
                        class="relative flex max-h-[90dvh] w-full max-w-lg translate-y-3 flex-col overflow-hidden rounded-2xl border border-black/5 bg-white opacity-0 shadow-2xl transition-all duration-200 sm:rounded-3xl">
                        {{-- Modal Header --}}
                        <div class="flex shrink-0 items-center justify-between border-b border-line px-5 py-4">
                            <div>
                                <h3 id="series-modal-title" class="text-sm font-bold text-black">
                                    Pembayaran bertahap
                                </h3>
                                <p class="mt-1 text-xs text-muted">
                                    Atur jumlah dan jadwal pembayaran.
                                </p>
                            </div>

                            <button type="button" id="series-close" aria-label="Tutup pengaturan pembayaran"
                                class="flex size-9 items-center justify-center rounded-xl text-muted transition hover:bg-canvas hover:text-black focus-visible:outline-2 focus-visible:outline-brand">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round">
                                    <path d="m18 6-12 12M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Modal Content --}}
                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain px-5 py-5">

                            <p class="text-xs leading-relaxed text-muted">
                                Buat tagihan harian, mingguan, atau bulanan sekaligus.
                                Setiap tagihan memiliki tenggat sendiri pukul 23.59 WIB.
                            </p>

                            {{-- Jumlah dan Periode --}}
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                                    <span>Jumlah pembayaran</span>

                                    <input type="number" name="installments" id="series-count" min="1"
                                        max="31" value="{{ old('installments', 1) }}"
                                        class="min-h-11 w-full rounded-xl border border-black/15 bg-white px-3 text-sm font-normal text-black outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">

                                    @error('installments')
                                        <span role="alert" class="text-xs font-medium text-[#8f1d0e]">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </label>

                                <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                                    <span>Tenggat tiap</span>

                                    <select name="installment_unit" id="series-unit"
                                        class="min-h-11 w-full rounded-xl border border-black/15 bg-white px-3 text-sm font-normal text-black outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                        @foreach (['week' => 'Minggu', 'day' => 'Hari', 'month' => 'Bulan'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('installment_unit', 'week') === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('installment_unit')
                                        <span role="alert" class="text-xs font-medium text-[#8f1d0e]">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </label>
                            </div>

                            {{-- Tanggal Mulai --}}
                            <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                                <span>Mulai tanggal</span>

                                <input type="date" name="starts_on" id="series-start"
                                    value="{{ old('starts_on') }}"
                                    class="min-h-11 w-full rounded-xl border border-black/15 bg-white px-3 text-sm font-normal text-black outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">

                                <span class="text-xs font-normal text-muted">
                                    Kosongkan untuk mulai hari ini.
                                </span>

                                @error('starts_on')
                                    <span role="alert" class="text-xs font-medium text-[#8f1d0e]">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </label>

                            {{-- Metode Nominal --}}
                            <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                                <span>Nominal di atas adalah</span>

                                <select name="amount_mode" id="series-mode"
                                    class="min-h-11 w-full rounded-xl border border-black/15 bg-white px-3 text-sm font-normal text-black outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    <option value="each" @selected(old('amount_mode', 'each') === 'each')>
                                        Per pembayaran
                                    </option>
                                    <option value="total" @selected(old('amount_mode') === 'total')>
                                        Total, dibagi rata
                                    </option>
                                </select>
                            </label>


                            {{-- Live Preview --}}
                            <div class="overflow-hidden rounded-xl border border-line bg-canvas/40">

                                {{-- Ringkasan --}}
                                <div class="flex items-center justify-between border-b border-line px-3.5 py-3">
                                    <div>
                                        <p class="text-xs font-semibold text-black">
                                            Simulasi pembayaran
                                        </p>
                                        <p class="mt-0.5 text-[10px] text-muted">
                                            Estimasi nominal dan jadwal tagihan
                                        </p>
                                    </div>

                                    <span class="rounded-md bg-white px-2 py-1 text-[10px] font-medium text-muted">
                                        Preview
                                    </span>
                                </div>

                                {{-- Nominal --}}
                                <div class="grid grid-cols-2 divide-x divide-line border-b border-line bg-white">
                                    <div class="px-3.5 py-3">
                                        <p class="text-[10px] text-muted">
                                            Per pembayaran
                                        </p>
                                        <p id="preview-amount" class="mt-1 text-sm font-bold text-black">
                                            Rp0
                                        </p>
                                    </div>

                                    <div class="px-3.5 py-3">
                                        <p class="text-[10px] text-muted">
                                            Total tagihan
                                        </p>
                                        <p id="preview-total" class="mt-1 text-sm font-bold text-black">
                                            Rp0
                                        </p>
                                    </div>
                                </div>

                                {{-- Jadwal --}}
                                <div class="px-3.5 py-3">
                                    <div class="mb-3 flex items-center justify-between">
                                        <p class="text-[11px] font-semibold text-black">
                                            Jadwal pembayaran
                                        </p>

                                        <span id="preview-count" class="text-[10px] text-muted">
                                            0 pembayaran
                                        </span>
                                    </div>

                                    <div id="preview-dates" class="space-y-2">
                                        <p class="text-xs text-muted">
                                            Atur jumlah pembayaran untuk melihat jadwal.
                                        </p>
                                    </div>
                                </div>

                                {{-- Footer --}}
                                <div class="border-t border-line px-3.5 py-2.5">
                                    <p id="preview-note" class="text-[10px] leading-relaxed text-muted">
                                        Tenggat dihitung berdasarkan tanggal mulai pembayaran.
                                    </p>
                                </div>
                            </div>

                        </div>

                        {{-- Modal Footer --}}
                        <div
                            class="flex shrink-0 items-center justify-between gap-3 border-t border-line bg-white px-5 py-4">
                            <button type="button" id="series-reset"
                                class="min-h-10 rounded-xl px-3 text-xs font-semibold text-muted transition hover:bg-canvas hover:text-black">
                                Atur ulang
                            </button>

                            <button type="button" id="series-save"
                                class="min-h-10 rounded-xl bg-brand px-5 text-xs font-semibold text-white transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                Simpan pengaturan
                            </button>
                        </div>
                    </div>
                </div>
                <script>

document.addEventListener('DOMContentLoaded', () => {
    const $ = (id) => document.getElementById(id);

    // Mode pembayaran
    const installmentToggle = $('installment-toggle');
    const singlePaymentField = $('single-payment-field');
    const triggerWrapper = $('series-trigger-wrapper');
    const due = $('due-date-input');

    // Modal
    const modal = $('series-modal');
    const panel = $('series-modal-panel');
    const openBtn = $('series-open');
    const closeBtn = $('series-close');
    const saveBtn = $('series-save');
    const resetBtn = $('series-reset');

    // Trigger
    const triggerLabel = $('series-trigger-label');
    const triggerDescription = $('series-trigger-description');

    // Pengaturan cicilan
    const count = $('series-count');
    const unit = $('series-unit');
    const start = $('series-start');
    const mode = $('series-mode');
    const amount = $('series-amount');

    // Preview
    const previewAmount = $('preview-amount');
    const previewTotal = $('preview-total');
    const previewCount = $('preview-count');
    const previewDates = $('preview-dates');
    const previewNote = $('preview-note');

    if (
        !installmentToggle ||
        !modal ||
        !count ||
        !unit ||
        !start ||
        !mode ||
        !amount ||
        !due ||
        !previewDates
    ) {
        return;
    }

    let previousFocus = null;
    let closeTimeout = null;

    const currency = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    });

    const dateFormatter = new Intl.DateTimeFormat('id-ID', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });

    const formatCurrency = (value) => currency.format(value || 0);

    const getStartDate = () => {
        if (start.value) {
            const [year, month, day] = start.value.split('-').map(Number);

            return new Date(year, month - 1, day);
        }

        const today = new Date();

        return new Date(
            today.getFullYear(),
            today.getMonth(),
            today.getDate()
        );
    };

    /**
     * Menghitung tenggat setiap pembayaran.
     *
     * Harian:
     * Dimulai dari tanggal mulai.
     *
     * Mingguan:
     * Tenggat pertama adalah hari Minggu pada atau
     * setelah tanggal mulai, kemudian berulang setiap 7 hari.
     *
     * Bulanan:
     * Tenggat adalah hari terakhir setiap bulan.
     */
    const getDeadline = (from, kind, installmentNumber) => {
        const date = new Date(
            from.getFullYear(),
            from.getMonth(),
            from.getDate()
        );

        if (kind === 'day') {
            date.setDate(date.getDate() + installmentNumber - 1);
            return date;
        }

        if (kind === 'week') {
            // 0 = Minggu, 1 = Senin, ..., 6 = Sabtu.
            // Jika mulai hari Minggu, tenggat pertama hari itu juga.
            const daysUntilSunday = (7 - date.getDay()) % 7;

            date.setDate(
                date.getDate() +
                daysUntilSunday +
                (installmentNumber - 1) * 7
            );

            return date;
        }

        if (kind === 'month') {
            return new Date(
                date.getFullYear(),
                date.getMonth() + installmentNumber,
                0
            );
        }

        return date;
    };

    const updatePaymentMode = () => {
        const isInstallment = installmentToggle.checked;

        singlePaymentField?.classList.toggle('hidden', isInstallment);
        triggerWrapper?.classList.toggle('hidden', !isInstallment);

        // Jatuh tempo biasa tidak wajib dan tidak dikirim
        // ketika pembayaran bertahap aktif.
        due.required = !isInstallment;
        due.disabled = isInstallment;

        if (isInstallment) {
            const currentCount = Number.parseInt(count.value, 10) || 1;

            if (currentCount < 2) {
                count.value = 2;
            }
        } else {
            count.value = 1;
        }

        updatePreview();
    };

    const updateTrigger = (totalCount) => {
        if (totalCount > 1) {
            const unitLabels = {
                day: 'harian',
                week: 'mingguan',
                month: 'bulanan',
            };

            triggerLabel.textContent = 'Ubah';

            triggerDescription.textContent =
                `${totalCount} pembayaran ${unitLabels[unit.value]}.`;
        } else {
            triggerLabel.textContent = 'Atur';
            triggerDescription.textContent =
                'Bagi tagihan menjadi beberapa pembayaran.';
        }
    };

    const createScheduleItem = (number, date, nominal) => {
        const row = document.createElement('div');

        row.className = 'flex items-center justify-between gap-3';

        const left = document.createElement('div');
        left.className = 'flex min-w-0 items-center gap-2.5';

        const badge = document.createElement('span');
        badge.className =
            'flex size-6 shrink-0 items-center justify-center rounded-full bg-white text-[10px] font-semibold text-muted ring-1 ring-black/5';
        badge.textContent = String(number).padStart(2, '0');

        const dateWrapper = document.createElement('div');
        dateWrapper.className = 'min-w-0';

        const dateLabel = document.createElement('p');
        dateLabel.className =
            'truncate text-[11px] font-medium text-black';
        dateLabel.textContent = date;

        const timeLabel = document.createElement('p');
        timeLabel.className = 'text-[10px] text-muted';
        timeLabel.textContent = '23.59 WIB';

        dateWrapper.append(dateLabel, timeLabel);
        left.append(badge, dateWrapper);

        const amountLabel = document.createElement('span');
        amountLabel.className =
            'shrink-0 text-[11px] font-semibold tabular-nums text-black';
        amountLabel.textContent = formatCurrency(nominal);

        row.append(left, amountLabel);

        return row;
    };

    const renderSchedule = (dates, amounts) => {
        previewDates.replaceChildren();

        const total = dates.length;

        // Maksimal 3 jadwal pertama dan 1 jadwal terakhir.
        const indexes = total <= 4
            ? Array.from({ length: total }, (_, index) => index)
            : [0, 1, 2, total - 1];

        indexes.forEach((index, position) => {
            if (position === 3 && total > 4) {
                const omitted = document.createElement('p');

                omitted.className =
                    'pl-8 text-[10px] text-muted';

                omitted.textContent =
                    `+ ${total - 4} jadwal pembayaran lainnya`;

                previewDates.appendChild(omitted);
            }

            previewDates.appendChild(
                createScheduleItem(
                    index + 1,
                    dates[index],
                    amounts[index]
                )
            );
        });
    };

    const updatePreview = () => {
        const totalCount = Math.min(
            31,
            Math.max(1, Number.parseInt(count.value, 10) || 1)
        );

        count.value = totalCount;

        updateTrigger(totalCount);

        if (totalCount <= 1) {
            previewAmount.textContent = formatCurrency(
                Number.parseInt(amount.value, 10) || 0
            );

            previewTotal.textContent = formatCurrency(
                Number.parseInt(amount.value, 10) || 0
            );

            previewCount.textContent = '1 pembayaran';
            previewDates.replaceChildren();

            const hint = document.createElement('p');
            hint.className = 'text-xs text-muted';
            hint.textContent =
                'Aktifkan pembayaran bertahap untuk melihat jadwal cicilan.';

            previewDates.appendChild(hint);
            return;
        }

        const value = Math.max(
            0,
            Number.parseInt(amount.value, 10) || 0
        );

        const isTotalMode = mode.value === 'total';

        let amounts;

        if (isTotalMode) {
            const base = Math.floor(value / totalCount);
            const remainder = value % totalCount;

            amounts = Array(totalCount).fill(base);

            // Selisih pembagian ditambahkan ke cicilan terakhir.
            amounts[totalCount - 1] += remainder;
        } else {
            amounts = Array(totalCount).fill(value);
        }

        const grandTotal = amounts.reduce(
            (sum, nominal) => sum + nominal,
            0
        );

        const firstAmount = amounts[0];
        const lastAmount = amounts[totalCount - 1];

        if (firstAmount !== lastAmount) {
            previewAmount.textContent =
                `${formatCurrency(firstAmount)} – ${formatCurrency(lastAmount)}`;
        } else {
            previewAmount.textContent = formatCurrency(firstAmount);
        }

        previewTotal.textContent = formatCurrency(grandTotal);
        previewCount.textContent = `${totalCount} pembayaran`;

        const from = getStartDate();

        const dates = Array.from(
            { length: totalCount },
            (_, index) => {
                return dateFormatter.format(
                    getDeadline(from, unit.value, index + 1)
                );
            }
        );

        renderSchedule(dates, amounts);

        const noteLabels = {
            day: 'setiap hari',
            week: 'setiap akhir minggu (Minggu)',
            month: 'setiap akhir bulan',
        };

        previewNote.textContent =
            `Tenggat ${noteLabels[unit.value] || ''}, dimulai berdasarkan tanggal yang dipilih. Pukul 23.59 WIB.`;
    };

    // Modal
    const openModal = () => {
        previousFocus = document.activeElement;

        if (closeTimeout) {
            clearTimeout(closeTimeout);
            closeTimeout = null;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');

        document.body.classList.add('overflow-hidden');

        requestAnimationFrame(() => {
            panel.classList.remove('translate-y-3', 'opacity-0');
            panel.classList.add('translate-y-0', 'opacity-100');

            closeBtn?.focus();
        });
    };

    const closeModal = () => {
        if (modal.classList.contains('hidden')) return;

        panel.classList.add('translate-y-3', 'opacity-0');
        panel.classList.remove('translate-y-0', 'opacity-100');

        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');

        closeTimeout = setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');

            previousFocus?.focus();
            closeTimeout = null;
        }, 200);
    };

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    saveBtn?.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (
            event.key === 'Escape' &&
            !modal.classList.contains('hidden')
        ) {
            closeModal();
        }
    });

    // Toggle mode pembayaran
    installmentToggle.addEventListener('change', updatePaymentMode);

    // Reset pengaturan cicilan
    resetBtn?.addEventListener('click', () => {
        count.value = 2;
        unit.value = 'week';
        start.value = '';
        mode.value = 'each';

        updatePreview();
    });

    // Update preview ketika input berubah
    [count, unit, start, mode, amount].forEach((element) => {
        element.addEventListener('input', updatePreview);
        element.addEventListener('change', updatePreview);
    });

    // Kondisi awal berdasarkan nilai toggle
    updatePaymentMode();
});

                </script>
                <div class="flex flex-col gap-3 rounded-2xl border border-line bg-canvas/40 p-4">
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                        <span>QRIS Pembayaran</span>
                        @if ($existingQrisList->isNotEmpty())
                            <select name="existing_qris" id="existing_qris_select"
                                class="min-h-11 rounded-xl border border-black/15 bg-white px-3 text-sm font-normal text-black focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                                @foreach ($existingQrisList as $item)
                                    @php
                                        $isDyn = str_starts_with($item->qris_url, '000201');
                                        $tag = $isDyn ? 'Dinamis DANA' : 'Gambar/URL';
                                    @endphp
                                    <option value="{{ $item->qris_url }}" @selected(old('existing_qris', $latestQrisUrl) == $item->qris_url)>
                                        [{{ $tag }}] {{ $item->title }}
                                    </option>
                                @endforeach
                                <option value="" @selected(old('existing_qris') === '')>+ Upload / Masukkan QRIS Baru
                                </option>
                            </select>
                            <span class="text-[11px] font-medium text-[#14532d]">Dapat langsung memakai QRIS dari
                                tagihan
                                sebelumnya atau pilih opsi baru di bawah.</span>
                        @else
                            <span class="text-xs text-muted">Belum ada QRIS tersimpan. Masukkan QRIS baru di
                                bawah.</span>
                        @endif
                        @error('existing_qris')
                            <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                        @enderror
                    </label>

                    <div id="new_qris_container" class="flex flex-col gap-3 border-t border-line/60 pt-3">
                        <div class="flex flex-col gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-wider text-muted">
                                {{ $existingQrisList->isNotEmpty() ? 'Atau gunakan QRIS baru:' : 'Pilih metode input QRIS:' }}
                            </span>

                            <div role="tablist" aria-label="Metode input QRIS baru"
                                class="grid grid-cols-3 gap-1 rounded-xl border border-line bg-white p-1">
                                <button type="button" role="tab" id="tab_upload" aria-selected="true"
                                    aria-controls="panel_upload" tabindex="0" onclick="switchQrisMethod('upload')"
                                    class="qris-tab flex min-h-9 items-center justify-center rounded-lg px-2 text-xs font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-brand bg-black text-white">
                                    Upload File
                                </button>
                                <button type="button" role="tab" id="tab_payload" aria-selected="false"
                                    aria-controls="panel_payload" tabindex="-1"
                                    onclick="switchQrisMethod('payload')"
                                    class="qris-tab flex min-h-9 items-center justify-center rounded-lg px-2 text-xs font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-brand text-muted hover:text-black">
                                    String DANA
                                </button>
                                <button type="button" role="tab" id="tab_url" aria-selected="false"
                                    aria-controls="panel_url" tabindex="-1" onclick="switchQrisMethod('url')"
                                    class="qris-tab flex min-h-9 items-center justify-center rounded-lg px-2 text-xs font-semibold transition-colors focus-visible:outline-brand text-muted hover:text-black">
                                    Link URL
                                </button>
                            </div>
                        </div>

                        {{-- Panel 1: Upload File --}}
                        <div id="panel_upload" role="tabpanel" aria-labelledby="tab_upload"
                            class="qris-panel flex flex-col gap-1">
                            <label class="flex flex-col gap-1 text-xs font-semibold text-black">
                                <span>Upload File Gambar QRIS (PNG/JPG)</span>
                                <input type="file" id="qris_image_input" name="qris_image"
                                    accept="image/png,image/jpeg,image/webp"
                                    class="min-h-11 rounded-xl border border-black/15 bg-white px-3 py-2 text-xs font-normal focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                            </label>
                            <span class="text-[11px] text-muted">File gambar dari DANA Bisnis atau bank. Otomatis
                                dipindai jika didukung browser.</span>
                            @error('qris_image')
                                <span role="alert"
                                    class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Panel 2: String EMVCo / DANA Bisnis --}}
                        <div id="panel_payload" role="tabpanel" aria-labelledby="tab_payload"
                            class="qris-panel hidden flex flex-col gap-1">
                            <label class="flex flex-col gap-1 text-xs font-semibold text-black">
                                <span class="flex items-center justify-between">
                                    <span>Kode String QRIS (EMVCo)</span>
                                    <span
                                        class="rounded bg-brand-soft px-1.5 py-0.5 text-[10px] font-semibold text-brand">Rekomendasi
                                        Dinamis</span>
                                </span>
                                <textarea id="qris_payload_input" name="qris_payload" rows="2"
                                    placeholder="00020101021126580016ID.CO.DANA.WWW..."
                                    class="rounded-xl border border-black/15 bg-white p-2.5 font-mono text-xs font-normal focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">{{ old('qris_payload') }}</textarea>
                            </label>
                            <span class="text-[11px] text-muted">Paste string QRIS dari DANA Bisnis (diawali 000201)
                                untuk nominal terkunci otomatis.</span>
                            @error('qris_payload')
                                <span role="alert"
                                    class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Panel 3: Link URL --}}
                        <div id="panel_url" role="tabpanel" aria-labelledby="tab_url"
                            class="qris-panel hidden flex flex-col gap-1">
                            <label class="flex flex-col gap-1 text-xs font-semibold text-black">
                                <span>Tautan URL Gambar QRIS</span>
                                <input type="text" id="qris_url_input" name="qris_url"
                                    value="{{ old('qris_url') }}" placeholder="https://.../qris.png"
                                    class="min-h-11 rounded-xl border border-black/15 bg-white px-3 text-xs font-normal focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                            </label>
                            <span class="text-[11px] text-muted">URL gambar QRIS online yang dapat diakses
                                publik.</span>
                            @error('qris_url')
                                <span role="alert"
                                    class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <script>
                    function switchQrisMethod(method) {
                        const panels = {
                            upload: document.getElementById('panel_upload'),
                            payload: document.getElementById('panel_payload'),
                            url: document.getElementById('panel_url'),
                        };
                        const tabs = {
                            upload: document.getElementById('tab_upload'),
                            payload: document.getElementById('tab_payload'),
                            url: document.getElementById('tab_url'),
                        };

                        Object.keys(panels).forEach(key => {
                            if (key === method) {
                                panels[key]?.classList.remove('hidden');
                                tabs[key]?.classList.add('bg-black', 'text-white');
                                tabs[key]?.classList.remove('text-muted');
                                tabs[key]?.setAttribute('aria-selected', 'true');
                                tabs[key]?.setAttribute('tabindex', '0');
                            } else {
                                panels[key]?.classList.add('hidden');
                                tabs[key]?.classList.remove('bg-black', 'text-white');
                                tabs[key]?.classList.add('text-muted');
                                tabs[key]?.setAttribute('aria-selected', 'false');
                                tabs[key]?.setAttribute('tabindex', '-1');
                            }
                        });
                    }

                    // Keyboard navigation for tablist
                    const tabOrder = ['upload', 'payload', 'url'];
                    tabOrder.forEach((tabId, idx) => {
                        const tabEl = document.getElementById('tab_' + tabId);
                        tabEl?.addEventListener('keydown', (e) => {
                            let nextIdx = null;
                            if (e.key === 'ArrowRight') {
                                nextIdx = (idx + 1) % tabOrder.length;
                            } else if (e.key === 'ArrowLeft') {
                                nextIdx = (idx - 1 + tabOrder.length) % tabOrder.length;
                            }
                            if (nextIdx !== null) {
                                e.preventDefault();
                                const nextTab = document.getElementById('tab_' + tabOrder[nextIdx]);
                                nextTab?.focus();
                                switchQrisMethod(tabOrder[nextIdx]);
                            }
                        });
                    });

                    document.getElementById('qris_image_input')?.addEventListener('change', async function(e) {
                        const file = e.target.files[0];
                        if (!file || !('BarcodeDetector' in window)) return;
                        try {
                            const detector = new BarcodeDetector({
                                formats: ['qr_code']
                            });
                            const bitmap = await createImageBitmap(file);
                            const barcodes = await detector.detect(bitmap);
                            if (barcodes.length > 0 && barcodes[0].rawValue.startsWith('000201')) {
                                switchQrisMethod('payload');
                                const payloadInput = document.getElementById('qris_payload_input');
                                if (payloadInput) {
                                    payloadInput.value = barcodes[0].rawValue;
                                    payloadInput.classList.add('ring-2', 'ring-brand');
                                    setTimeout(() => payloadInput.classList.remove('ring-2', 'ring-brand'), 2000);
                                }
                            }
                        } catch (err) {
                            // Non-blocking fallback if browser barcode detection fails
                        }
                    });
                </script>

                <button type="submit"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Buat
                    tagihan</button>
            </form>
        </section>
    </div>
</x-layouts.app>
