<x-layouts.app title="Periode tagihan">
    <x-ui.page-header :eyebrow="'Kelola · ' . now()->translatedFormat('l, d F Y')" title="Tagihan kelas"
        description="Buat periode tagihan, atur cicilan, dan pantau progres pelunasan tiap periode." />

    <div class="flex lg:flex-wrap flex-wrap-reverse items-start gap-6">
        <section aria-labelledby="judul-periode"
            class="flex min-w-0 flex-[999_1_30rem] flex-col rounded-2xl border border-line bg-surface shadow-xs">
            <div class="flex flex-col gap-3.5 border-b border-line px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <h2 id="judul-periode" class="text-base font-semibold text-ink">Riwayat periode</h2>
                        <span class="text-xs font-medium tabular-nums text-muted">{{ $periods->total() }} periode</span>
                    </div>
                    <form method="GET" action="{{ route('periods.index') }}"
                        class="flex w-full min-w-0 items-center gap-2 sm:w-auto">
                        @if ($statusFilter)
                            <input type="hidden" name="status" value="{{ $statusFilter }}">
                        @endif
                        <label for="search-period" class="sr-only">Cari judul periode</label>
                        <input id="search-period" type="search" name="q" value="{{ $search }}"
                            placeholder="Cari judul periode..."
                            class="min-h-9 min-w-0 flex-1 rounded-xl border border-line bg-canvas/30 px-3 text-xs placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5 sm:w-60 sm:flex-none">
                        <button type="submit"
                            class="ui-btn ui-btn-outline shrink-0">
                            Cari
                        </button>
                    </form>
                </div>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 border-b border-line text-xs">
                    <a href="{{ route('periods.index', array_filter(['q' => $search])) }}"
                        class="inline-flex min-h-9 items-center border-b-2 px-0 font-semibold transition-colors {{ empty($statusFilter) ? 'border-ink text-ink' : 'border-transparent text-muted hover:border-ink/20 hover:text-ink' }}">
                        Semua
                    </a>
                    <a href="{{ route('periods.index', array_filter(['status' => 'active', 'q' => $search])) }}"
                        class="inline-flex min-h-9 items-center border-b-2 px-0 font-semibold transition-colors {{ $statusFilter === 'active' ? 'border-brand text-brand' : 'border-transparent text-muted hover:border-ink/20 hover:text-ink' }}">
                        Berjalan
                    </a>
                    <a href="{{ route('periods.index', array_filter(['status' => 'overdue', 'q' => $search])) }}"
                        class="inline-flex min-h-9 items-center border-b-2 px-0 font-semibold transition-colors {{ $statusFilter === 'overdue' ? 'border-line text-danger' : 'border-transparent text-muted hover:border-ink/20 hover:text-ink' }}">
                        Lewat Tempo
                    </a>
                    <a href="{{ route('periods.index', array_filter(['status' => 'completed', 'q' => $search])) }}"
                        class="inline-flex min-h-9 items-center border-b-2 px-0 font-semibold transition-colors {{ $statusFilter === 'completed' ? 'border-line text-success' : 'border-transparent text-muted hover:border-ink/20 hover:text-ink' }}">
                        Lunas Semua
                    </a>
                </div>
            </div>

            <div class="divide-y divide-ink/5">
                @forelse ($periods as $period)
                    @php
                        $isCompleted = $period->invoices_count > 0 && $period->paid_count === $period->invoices_count;
                        $isOverdue = $period->isPastDue() && !$isCompleted;
                    @endphp
                    <div
                        class="flex flex-wrap items-center gap-x-4 gap-y-2 px-6 py-4.5 hover:bg-surface/[0.015] transition-colors">
                        <div class="flex min-w-0 flex-1 basis-48 flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('periods.show', $period) }}"
                                    class="text-[15px] font-semibold text-brand hover:underline">{{ $period->title }}
                                    &rarr;</a>
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-muted">
                                    <span class="size-1.5 bg-brand"></span>{{ $period->category->name ?? 'Uang Kas' }}
                                </span>
                                @if ($isCompleted)
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-success">
                                        <span class="size-1.5 bg-success"></span>Lunas Semua</span>
                                @elseif ($isOverdue)
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-danger">
                                        <span class="size-1.5 bg-danger"></span>Lewat Tempo</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-warning">
                                        <span class="size-1.5 bg-warning"></span>Berjalan</span>
                                @endif
                                @if ($period->qris_url && str_starts_with($period->qris_url, '000201'))
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-brand">QRIS Dinamis</span>
                                @endif
                            </div>
                            <span class="text-xs text-muted">Jatuh tempo
                                {{ $period->due_date->format('d M Y') }}</span>
                        </div>
                        <span
                            class="text-sm font-semibold text-ink tabular-nums">Rp{{ number_format($period->amount, 0, ',', '.') }}</span>
                        <a href="{{ route('periods.show', $period) }}"
                            class="border-b border-ink/20 pb-0.5 text-xs font-semibold text-body transition-colors hover:border-line-strong hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            {{ $period->paid_count }}/{{ $period->invoices_count }} lunas
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
            class="flex min-w-72 flex-[1_1_18rem] flex-col gap-5 rounded-2xl border border-line bg-surface p-6 shadow-xs">
            <div class="flex flex-col gap-1">
                <h2 id="judul-buat" class="text-lg font-semibold text-ink">Buat periode</h2>
                <span class="text-xs text-muted">Berlaku untuk {{ $activeMembers }} anggota aktif.</span>
            </div>

            <form method="POST" action="{{ route('periods.store') }}" enctype="multipart/form-data"
                class="flex flex-col gap-4">
                @csrf
                <div class="flex flex-col gap-2.5 rounded-2xl border border-line bg-canvas/40 p-4">
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                        <span>Kategori Tagihan</span>
                        <select name="category_id" id="category-select" data-tom-select
                            class="min-h-9 rounded-xl border border-line bg-surface px-3 text-sm font-normal text-ink focus:border-line-strong focus:outline-none focus:ring-2 focus:ring-ink/5">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" data-cash="{{ $cat->isCash() ? 1 : 0 }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="flex flex-col gap-1 text-xs text-muted">
                        <span class="font-medium">Atau buat kategori baru:</span>
                        <input type="text" name="new_category" id="new-category-input" value="{{ old('new_category') }}"
                            placeholder="Ketik nama kategori baru jika belum ada"
                            class="min-h-10 rounded-xl border border-line bg-surface px-3 text-xs text-ink placeholder:text-muted/60 focus:border-line-strong focus:outline-none focus:ring-2 focus:ring-ink/5">
                        @error('new_category')
                            <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <div id="title-field" class="flex flex-col gap-1.5">
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                        <span>Nama Periode Tagihan</span>
                        <input type="text" id="title-input" name="title" value="{{ old('title') }}"
                            placeholder="Contoh: Jaket Angkatan / Study Tour" required
                            class="min-h-9 rounded-xl border border-line bg-canvas/30 px-3 text-sm font-normal text-ink placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">
                        @error('title')
                            <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                        @enderror
                    </label>
                </div>
                <p id="cash-note" class="hidden rounded-xl border border-brand/20 bg-brand-soft px-3.5 py-2.5 text-[11px] leading-relaxed text-brand">
                    Kategori kas dinamai otomatis (Kas Minggu ke 1, 2, dst.), dibayar bertahap, dan tenggatnya tiap minggu.
                </p>

                <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                    <span>Nominal per Anggota (Rp)</span>
                    <input type="number" name="amount" id="series-amount" value="{{ old('amount') }}"
                        placeholder="10000" required
                        class="min-h-9 rounded-xl border border-line bg-canvas/30 px-3 text-sm font-normal text-ink placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">
                    @error('amount')
                        <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                    @enderror
                </label>


                {{-- Mode Pembayaran --}}
                <div class="space-y-3">

                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold text-ink">
                                Mode pembayaran
                            </p>
                            <p class="mt-1 text-[11px] text-muted">
                                <span id="mode-hint">Pilih pembayaran biasa atau bertahap.</span>
                            </p>
                        </div>

                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <span class="text-xs font-medium text-muted">
                                Bertahap
                            </span>

                            <input type="checkbox" id="installment-toggle" class="peer sr-only"
                                @checked((int) old('installments', 1) > 1)>

                            <span
                                class="relative h-6 w-11 rounded-full bg-ink/15 transition peer-checked:bg-brand
                after:absolute after:left-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:shadow-sm after:transition-transform
                peer-checked:after:translate-x-5">
                            </span>
                        </label>
                    </div>

                    {{-- Pembayaran Biasa --}}
                    <div id="single-payment-field">
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                            <span>Jatuh tempo</span>

                            <input type="date" name="due_date" id="due-date-input" value="{{ old('due_date') }}"
                                required
                                class="min-h-9 rounded-xl border border-line bg-canvas/30 px-3 text-sm font-normal text-ink transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">

                            <span class="text-[11px] font-normal text-muted">
                                Berlaku sampai pukul 23.59 WIB.
                            </span>

                            @error('due_date')
                                <span role="alert" class="text-xs font-medium text-danger">
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
                                    <p class="text-xs font-semibold text-ink">
                                        Pembayaran bertahap
                                    </p>

                                    <p id="series-trigger-description" class="mt-1 text-xs text-muted">
                                        Bagi tagihan menjadi beberapa pembayaran.
                                    </p>
                                </div>

                                <button type="button" id="series-open"
                                    class="ui-btn ui-btn-outline flex shrink-0 gap-2 border-ink/10">
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
                        class="relative flex max-h-[90dvh] w-full max-w-lg translate-y-3 flex-col overflow-hidden rounded-2xl border border-ink/5 bg-surface opacity-0 shadow-2xl transition-all duration-200 sm:rounded-3xl">
                        {{-- Modal Header --}}
                        <div class="flex shrink-0 items-center justify-between border-b border-line px-5 py-4">
                            <div>
                                <h3 id="series-modal-title" class="text-sm font-semibold text-ink">
                                    Pembayaran bertahap
                                </h3>
                                <p class="mt-1 text-xs text-muted">
                                    Atur jumlah dan jadwal pembayaran.
                                </p>
                            </div>

                            <button type="button" id="series-close" aria-label="Tutup pengaturan pembayaran"
                                class="flex size-9 items-center justify-center rounded-xl text-muted transition hover:bg-canvas hover:text-ink focus-visible:outline-2 focus-visible:outline-brand">
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
                                <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                                    <span>Jumlah pembayaran</span>

                                    <input type="number" name="installments" id="series-count" min="1"
                                        max="31" value="{{ old('installments', 1) }}"
                                        class="min-h-9 w-full rounded-xl border border-line bg-surface px-3 text-sm font-normal text-ink outline-none transition focus:border-line-strong focus:ring-2 focus:ring-ink/5">

                                    @error('installments')
                                        <span role="alert" class="text-xs font-medium text-danger">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </label>

                                <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                                    <span>Tenggat tiap</span>

                                    <select name="installment_unit" id="series-unit" data-tom-select
                                        class="min-h-9 w-full rounded-xl border border-line bg-surface px-3 text-sm font-normal text-ink outline-none transition focus:border-line-strong focus:ring-2 focus:ring-ink/5">
                                        @foreach (['week' => 'Minggu', 'day' => 'Hari', 'month' => 'Bulan'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('installment_unit', 'week') === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('installment_unit')
                                        <span role="alert" class="text-xs font-medium text-danger">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </label>
                            </div>

                            {{-- Rentang Tanggal --}}
                            <div class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                                <span id="range-title">Rentang tanggal</span>

                                <input type="hidden" name="starts_on" id="series-start" value="{{ old('starts_on') }}">

                                <div class="rounded-xl border border-line bg-surface p-3">
                                    <div class="mb-2 flex items-center justify-between">
                                        <button type="button" id="range-prev" aria-label="Bulan sebelumnya"
                                            class="flex size-8 items-center justify-center rounded-lg text-muted transition hover:bg-canvas hover:text-ink">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>
                                        </button>
                                        <span id="range-month" class="text-xs font-semibold text-ink" aria-live="polite"></span>
                                        <button type="button" id="range-next" aria-label="Bulan berikutnya"
                                            class="flex size-8 items-center justify-center rounded-lg text-muted transition hover:bg-canvas hover:text-ink">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-7 text-center text-[10px] font-semibold text-muted">
                                        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $day)
                                            <span class="py-1">{{ $day }}</span>
                                        @endforeach
                                    </div>
                                    <div id="range-grid" class="grid grid-cols-7 gap-y-0.5"></div>
                                </div>

                                <span id="range-summary" class="text-xs font-normal text-muted">
                                    Pilih satu tanggal, seluruh rentangnya ikut terpilih.
                                </span>

                                @error('starts_on')
                                    <span role="alert" class="text-xs font-medium text-danger">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            {{-- Metode Nominal --}}
                            <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                                <span>Nominal di atas adalah</span>

                                <select name="amount_mode" id="series-mode" data-tom-select
                                    class="min-h-9 w-full rounded-xl border border-line bg-surface px-3 text-sm font-normal text-ink outline-none transition focus:border-line-strong focus:ring-2 focus:ring-ink/5">
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
                                        <p class="text-xs font-semibold text-ink">
                                            Simulasi pembayaran
                                        </p>
                                        <p class="mt-0.5 text-[10px] text-muted">
                                            Estimasi nominal dan jadwal tagihan
                                        </p>
                                    </div>

                                    <span class="rounded-md bg-surface px-2 py-1 text-[10px] font-medium text-muted">
                                        Preview
                                    </span>
                                </div>

                                {{-- Nominal --}}
                                <div class="grid grid-cols-2 divide-x divide-line border-b border-line bg-surface">
                                    <div class="px-3.5 py-3">
                                        <p class="text-[10px] text-muted">
                                            Per pembayaran
                                        </p>
                                        <p id="preview-amount" class="mt-1 text-sm font-semibold text-ink">
                                            Rp0
                                        </p>
                                    </div>

                                    <div class="px-3.5 py-3">
                                        <p class="text-[10px] text-muted">
                                            Total tagihan
                                        </p>
                                        <p id="preview-total" class="mt-1 text-sm font-semibold text-ink">
                                            Rp0
                                        </p>
                                    </div>
                                </div>

                                {{-- Jadwal --}}
                                <div class="px-3.5 py-3">
                                    <div class="mb-3 flex items-center justify-between">
                                        <p class="text-[11px] font-semibold text-ink">
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
                            class="flex shrink-0 items-center justify-between gap-3 border-t border-line bg-surface px-5 py-4">
                            <button type="button" id="series-reset"
                                class="min-h-10 rounded-xl px-3 text-xs font-semibold text-muted transition hover:bg-canvas hover:text-ink">
                                Atur ulang
                            </button>

                            <button type="button" id="series-save"
                                class="ui-btn ui-btn-primary hover:opacity-90">
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
    // Selects may be upgraded to Tom Select: write through its API so the visible control stays in sync.
    const setSelectValue = (el, value) => el.tomselect ? el.tomselect.setValue(value, true) : (el.value = value);
    const setSelectDisabled = (el, disabled) => {
        el.disabled = disabled;
        el.tomselect?.[disabled ? 'disable' : 'enable']();
    };

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

    // Kategori & nama
    const categorySelect = $('category-select');
    const newCategory = $('new-category-input');
    const titleField = $('title-field');
    const titleInput = $('title-input');
    const cashNote = $('cash-note');
    const modeHint = $('mode-hint');

    // Kalender rentang tanggal
    const rangeGrid = $('range-grid');
    const rangeMonth = $('range-month');
    const rangeSummary = $('range-summary');
    const rangeTitle = $('range-title');
    const rangePrev = $('range-prev');
    const rangeNext = $('range-next');

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
        !rangeGrid ||
        !previewDates
    ) {
        return;
    }

    // Kategori kas: nama otomatis "Kas Minggu ke N", bertahap, tenggat tiap minggu.
    const cashNames = ['kas', 'uang kas'];

    const isCash = () => {
        const typed = (newCategory?.value || '').trim().toLowerCase();

        if (typed) {
            return cashNames.includes(typed);
        }

        return categorySelect?.selectedOptions[0]?.dataset.cash === '1';
    };

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

    const pad = (n) => String(n).padStart(2, '0');

    const toYmd = (date) =>
        `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

    const sameDay = (a, b) => toYmd(a) === toYmd(b);

    const today = () => {
        const now = new Date();

        return new Date(now.getFullYear(), now.getMonth(), now.getDate());
    };

    const addDays = (date, days) =>
        new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);

    /**
     * Rentang yang ikut terpilih saat satu tanggal diklik.
     * Minggu: Senin-Minggu, misal klik 25 -> 23-29. Bulan: tanggal 1 sampai akhir bulan.
     */
    const getRange = (date, kind) => {
        if (kind === 'month') {
            return [
                new Date(date.getFullYear(), date.getMonth(), 1),
                new Date(date.getFullYear(), date.getMonth() + 1, 0),
            ];
        }

        if (kind === 'week') {
            const monday = addDays(date, -((date.getDay() + 6) % 7));

            return [monday, addDays(monday, 6)];
        }

        return [date, date];
    };

    const shortFormatter = new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
    });

    const longFormatter = new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });

    const monthFormatter = new Intl.DateTimeFormat('id-ID', {
        month: 'long',
        year: 'numeric',
    });

    const describeRange = ([from, to]) =>
        sameDay(from, to)
            ? longFormatter.format(to)
            : `${shortFormatter.format(from)} – ${longFormatter.format(to)}`;

    let viewMonth = getStartDate();
    viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth(), 1);

    const renderCalendar = () => {
        if (!rangeGrid) return;

        const [rangeFrom, rangeTo] = getRange(getStartDate(), unit.value);
        const first = new Date(viewMonth.getFullYear(), viewMonth.getMonth(), 1);
        const gridStart = addDays(first, -((first.getDay() + 6) % 7));
        const now = today();

        rangeMonth.textContent = monthFormatter.format(first);
        rangeGrid.replaceChildren();

        for (let i = 0; i < 42; i++) {
            const date = addDays(gridStart, i);
            const inRange = date >= rangeFrom && date <= rangeTo;
            const isEdge = sameDay(date, rangeFrom) || sameDay(date, rangeTo);
            const outside = date.getMonth() !== first.getMonth();
            const past = date < now;

            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = String(date.getDate());
            button.disabled = past;
            button.setAttribute('aria-label', longFormatter.format(date));
            button.setAttribute('aria-pressed', inRange ? 'true' : 'false');

            const classes = ['h-9', 'text-xs', 'font-medium', 'transition-colors'];

            if (inRange) {
                classes.push(isEdge ? 'bg-brand text-white font-semibold' : 'bg-brand-soft text-brand');
                if (sameDay(date, rangeFrom) || date.getDay() === 1) classes.push('rounded-l-lg');
                if (sameDay(date, rangeTo) || date.getDay() === 0) classes.push('rounded-r-lg');
            } else {
                classes.push('rounded-lg', outside ? 'text-ink/30' : 'text-ink');
                if (!past) classes.push('hover:bg-canvas');
            }

            if (past) classes.push('cursor-not-allowed', 'opacity-40');
            if (sameDay(date, now) && !inRange) classes.push('ring-1', 'ring-brand/40');

            button.className = classes.join(' ');

            button.addEventListener('click', () => {
                start.value = toYmd(date);
                viewMonth = new Date(date.getFullYear(), date.getMonth(), 1);
                renderCalendar();
                updatePreview();
            });

            rangeGrid.appendChild(button);
        }

        const labels = { day: 'Tanggal', week: 'Minggu', month: 'Bulan' };

        rangeTitle.textContent = `Rentang tanggal (${labels[unit.value] || 'Minggu'})`;
        rangeSummary.textContent = start.value
            ? `Terpilih ${describeRange([rangeFrom, rangeTo])}. Tenggat ${longFormatter.format(rangeTo)}, 23.59 WIB.`
            : `Belum dipilih, memakai ${describeRange([rangeFrom, rangeTo])} (hari ini).`;
    };

    rangePrev?.addEventListener('click', () => {
        viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() - 1, 1);
        renderCalendar();
    });

    rangeNext?.addEventListener('click', () => {
        viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() + 1, 1);
        renderCalendar();
    });

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

            if (currentCount < (isCash() ? 1 : 2)) {
                count.value = isCash() ? 1 : 2;
            }
        } else {
            count.value = 1;
        }

        updatePreview();
    };

    // Kategori kas mengunci nama, mode, tenggat, dan metode nominal.
    const applyCategoryMode = () => {
        const cash = isCash();

        titleField?.classList.toggle('hidden', cash);
        cashNote?.classList.toggle('hidden', !cash);

        if (titleInput) {
            titleInput.required = !cash;
            titleInput.disabled = cash;
        }

        installmentToggle.disabled = cash;
        setSelectDisabled(unit, cash);
        setSelectDisabled(mode, cash);

        if (cash) {
            installmentToggle.checked = true;
            setSelectValue(unit, 'week');
            setSelectValue(mode, 'each');
        }

        if (modeHint) {
            modeHint.textContent = cash
                ? 'Dikunci: kas selalu bertahap, tenggat tiap minggu.'
                : 'Pilih pembayaran biasa atau bertahap.';
        }

        updatePaymentMode();
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

    const createScheduleItem = (number, date, nominal, rangeText) => {
        const row = document.createElement('div');

        row.className = 'flex items-center justify-between gap-3';

        const left = document.createElement('div');
        left.className = 'flex min-w-0 items-center gap-2.5';

        const badge = document.createElement('span');
        badge.className =
            'flex size-6 shrink-0 items-center justify-center rounded-full bg-surface text-[10px] font-semibold text-muted ring-1 ring-ink/5';
        badge.textContent = String(number).padStart(2, '0');

        const dateWrapper = document.createElement('div');
        dateWrapper.className = 'min-w-0';

        const dateLabel = document.createElement('p');
        dateLabel.className =
            'truncate text-[11px] font-medium text-ink';
        dateLabel.textContent = date;

        const timeLabel = document.createElement('p');
        timeLabel.className = 'text-[10px] text-muted';
        timeLabel.textContent = `${rangeText} · 23.59 WIB`;

        dateWrapper.append(dateLabel, timeLabel);
        left.append(badge, dateWrapper);

        const amountLabel = document.createElement('span');
        amountLabel.className =
            'shrink-0 text-[11px] font-semibold tabular-nums text-ink';
        amountLabel.textContent = formatCurrency(nominal);

        row.append(left, amountLabel);

        return row;
    };

    const renderSchedule = (dates, amounts, ranges) => {
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
                    amounts[index],
                    ranges[index]
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

        const deadlines = Array.from(
            { length: totalCount },
            (_, index) => getDeadline(from, unit.value, index + 1)
        );

        const dates = deadlines.map((deadline) => dateFormatter.format(deadline));

        const ranges = deadlines.map((deadline) => {
            if (unit.value === 'day') return 'Satu hari';

            return describeRange(getRange(deadline, unit.value)).replace(
                /\s\d{4}$/,
                ''
            );
        });

        renderSchedule(dates, amounts, ranges);

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
        setSelectValue(unit, 'week');
        start.value = '';
        setSelectValue(mode, 'each');

        applyCategoryMode();
        renderCalendar();
        updatePreview();
    });

    // Update preview ketika input berubah
    [count, unit, mode, amount].forEach((element) => {
        element.addEventListener('input', updatePreview);
        element.addEventListener('change', updatePreview);
    });

    unit.addEventListener('change', renderCalendar);
    categorySelect?.addEventListener('change', () => {
        applyCategoryMode();
        renderCalendar();
    });
    newCategory?.addEventListener('input', () => {
        applyCategoryMode();
        renderCalendar();
    });

    // Kondisi awal berdasarkan kategori dan nilai toggle
    applyCategoryMode();
    renderCalendar();
});

                </script>
                <div class="flex flex-col gap-3 rounded-2xl border border-line bg-canvas/40 p-4">
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                        <span>QRIS Pembayaran</span>
                        @if ($existingQrisList->isNotEmpty())
                            <select name="existing_qris" id="existing_qris_select" data-tom-select
                                class="min-h-9 rounded-xl border border-line bg-surface px-3 text-sm font-normal text-ink focus:border-line-strong focus:outline-none focus:ring-2 focus:ring-ink/5">
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
                            <span class="text-[11px] font-medium text-success">Dapat langsung memakai QRIS dari
                                tagihan
                                sebelumnya atau pilih opsi baru di bawah.</span>
                        @else
                            <span class="text-xs text-muted">Belum ada QRIS tersimpan. Masukkan QRIS baru di
                                bawah.</span>
                        @endif
                        @error('existing_qris')
                            <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                        @enderror
                    </label>

                    <div id="new_qris_container" class="flex flex-col gap-3 border-t border-line/60 pt-3">
                        <div class="flex flex-col gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-wider text-muted">
                                {{ $existingQrisList->isNotEmpty() ? 'Atau gunakan QRIS baru:' : 'Pilih metode input QRIS:' }}
                            </span>

                            <div role="tablist" aria-label="Metode input QRIS baru"
                                class="grid grid-cols-3 gap-1 rounded-xl border border-line bg-surface p-1">
                                <button type="button" role="tab" id="tab_upload" aria-selected="true"
                                    aria-controls="panel_upload" tabindex="0" onclick="switchQrisMethod('upload')"
                                    class="qris-tab flex min-h-9 items-center justify-center rounded-lg px-2 text-xs font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-brand bg-ink text-canvas">
                                    Upload File
                                </button>
                                <button type="button" role="tab" id="tab_payload" aria-selected="false"
                                    aria-controls="panel_payload" tabindex="-1"
                                    onclick="switchQrisMethod('payload')"
                                    class="qris-tab flex min-h-9 items-center justify-center rounded-lg px-2 text-xs font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-brand text-muted hover:text-ink">
                                    String DANA
                                </button>
                                <button type="button" role="tab" id="tab_url" aria-selected="false"
                                    aria-controls="panel_url" tabindex="-1" onclick="switchQrisMethod('url')"
                                    class="qris-tab flex min-h-9 items-center justify-center rounded-lg px-2 text-xs font-semibold transition-colors focus-visible:outline-brand text-muted hover:text-ink">
                                    Link URL
                                </button>
                            </div>
                        </div>

                        {{-- Panel 1: Upload File --}}
                        <div id="panel_upload" role="tabpanel" aria-labelledby="tab_upload"
                            class="qris-panel flex flex-col gap-1">
                            <div class="flex flex-col gap-1 text-xs font-semibold text-ink">
                                <span>Upload File Gambar QRIS (PNG/JPG)</span>
                                <input type="file" data-filepond data-max-size="2MB" id="qris_image_input" name="qris_image"
                                    accept="image/png,image/jpeg"
                                   >
                            </div>
                            <span class="text-[11px] text-muted">File gambar dari DANA Bisnis atau bank. Otomatis
                                dipindai jika didukung browser.</span>
                            @error('qris_image')
                                <span role="alert"
                                    class="text-xs font-medium text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Panel 2: String EMVCo / DANA Bisnis --}}
                        <div id="panel_payload" role="tabpanel" aria-labelledby="tab_payload"
                            class="qris-panel hidden flex flex-col gap-1">
                            <label class="flex flex-col gap-1 text-xs font-semibold text-ink">
                                <span class="flex items-center justify-between">
                                    <span>Kode String QRIS (EMVCo)</span>
                                    <span
                                        class="rounded bg-brand-soft px-1.5 py-0.5 text-[10px] font-semibold text-brand">Rekomendasi
                                        Dinamis</span>
                                </span>
                                <textarea id="qris_payload_input" name="qris_payload" rows="2"
                                    placeholder="00020101021126580016ID.CO.DANA.WWW..."
                                    class="rounded-xl border border-line bg-surface p-2.5 font-mono text-xs font-normal focus:border-line-strong focus:outline-none focus:ring-2 focus:ring-ink/5">{{ old('qris_payload') }}</textarea>
                            </label>
                            <span class="text-[11px] text-muted">Paste string QRIS dari DANA Bisnis (diawali 000201)
                                untuk nominal terkunci otomatis.</span>
                            @error('qris_payload')
                                <span role="alert"
                                    class="text-xs font-medium text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Panel 3: Link URL --}}
                        <div id="panel_url" role="tabpanel" aria-labelledby="tab_url"
                            class="qris-panel hidden flex flex-col gap-1">
                            <label class="flex flex-col gap-1 text-xs font-semibold text-ink">
                                <span>Tautan URL Gambar QRIS</span>
                                <input type="text" id="qris_url_input" name="qris_url"
                                    value="{{ old('qris_url') }}" placeholder="https://.../qris.png"
                                    class="min-h-9 rounded-xl border border-line bg-surface px-3 text-xs font-normal focus:border-line-strong focus:outline-none focus:ring-2 focus:ring-ink/5">
                            </label>
                            <span class="text-[11px] text-muted">URL gambar QRIS online yang dapat diakses
                                publik.</span>
                            @error('qris_url')
                                <span role="alert"
                                    class="text-xs font-medium text-danger">{{ $message }}</span>
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
                                tabs[key]?.classList.add('bg-ink', 'text-canvas');
                                tabs[key]?.classList.remove('text-muted');
                                tabs[key]?.setAttribute('aria-selected', 'true');
                                tabs[key]?.setAttribute('tabindex', '0');
                            } else {
                                panels[key]?.classList.add('hidden');
                                tabs[key]?.classList.remove('bg-ink', 'text-canvas');
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

                    // FilePond replaces the native input, so the scan listens for its addfile event instead of "change".
                    document.addEventListener('filepond:addfile', async function(e) {
                        if (e.detail.name !== 'qris_image') return;
                        const file = e.detail.file;
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
                    class="ui-btn ui-btn-primary">Buat
                    tagihan</button>
            </form>
        </section>
    </div>
</x-layouts.app>
