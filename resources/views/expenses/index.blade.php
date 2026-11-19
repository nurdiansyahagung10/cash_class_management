<x-dynamic-component :component="$isGuest ? 'layouts.public' : 'layouts.app'" title="Pengeluaran kas">
    <x-ui.page-header :eyebrow="'Keuangan · ' . now()->translatedFormat('l, d F Y')" title="Pengeluaran kas"
        description="Catat belanja keperluan kelas dan pantau sisa saldo kas yang tersedia." />

    @php
        $expenseStats = [
            ['Saldo kas tersedia', 'Rp' . number_format($stats['balance'], 0, ',', '.'), 'Bisa digunakan untuk keperluan kelas'],
            ['Total pengeluaran tercatat', 'Rp' . number_format($stats['totalExpense'], 0, ',', '.'), 'Dari seluruh kegiatan'],
            ['Jumlah transaksi', $stats['unpaidInvoices'], 'Pengeluaran disetujui'],
        ];
    @endphp
    <x-ui.stat-rail grid="md:grid-cols-3">
        @foreach ($expenseStats as [$label, $value, $detail])
            <x-ui.stat :label="$label" :value="$value" :hint="$detail" />
        @endforeach
    </x-ui.stat-rail>



    <div class="flex lg:flex-wrap flex-wrap-reverse items-start gap-6">
        <section aria-labelledby="judul-daftar"
            class="flex min-w-0 flex-[999_1_30rem] flex-col rounded-2xl border border-line bg-surface shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-6 py-4.5">
                <h2 id="judul-daftar" class="text-lg font-semibold text-ink">Riwayat pengeluaran</h2>

                <form method="GET" action="{{ route('expenses.index') }}" class="flex items-center gap-2">
                    <label for="search-expense" class="sr-only">Cari nama pengeluaran</label>
                    <input id="search-expense" type="search" name="q" value="{{ $search ?? '' }}"
                        placeholder="Cari nama barang..."
                        class="min-h-9 rounded-xl border border-line bg-canvas/30 px-3 text-xs placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">
                    <button type="submit"
                        class="ui-btn ui-btn-outline">Cari</button>
                    @if (!empty($search))
                        <a href="{{ route('expenses.index') }}"
                            class="min-h-11 inline-flex items-center rounded-xl px-3 text-xs font-semibold text-muted hover:text-ink transition-colors">Reset</a>
                    @endif
                </form>
            </div>

            <div class="divide-y divide-ink/5">
                @forelse ($expenses as $expense)
                    <div
                        class="flex flex-wrap items-start justify-between gap-4 px-6 py-4.5 hover:bg-surface/[0.015] transition-colors">
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-[15px] font-semibold text-ink">{{ $expense->title }}</span>
                                @if ($expense->receipt_path && $isGuest)
                                    <span
                                        class="ui-tag ui-tag-plain">
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
                                    class="font-medium text-ink">{{ $isGuest ? 'Pengurus kelas' : $expense->creator->name }}</span>
                                ·
                                {{ $expense->spent_at->format('d M Y') }}</span>
                        </div>
                        <span
                            class="shrink-0 font-semibold text-danger tabular-nums text-sm sm:text-base">-Rp{{ number_format($expense->amount, 0, ',', '.') }}</span>
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
                class="flex min-w-72 flex-[1_1_18rem] flex-col gap-5 rounded-2xl border border-line bg-surface p-6 shadow-xs">
                <div class="flex flex-col gap-1">
                    <h2 id="judul-tambah" class="text-lg font-semibold text-ink">Catat pengeluaran</h2>
                    <span class="text-xs text-muted">Otomatis mencatat EXPENSE_APPROVED ke ledger.</span>
                </div>

                <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data"
                    class="flex flex-col gap-4">
                    @csrf
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                        <span>Keperluan / Nama barang</span>
                        <input type="text" name="title" value="{{ old('title') }}"
                            placeholder="Contoh: Spidol & penghapus papan" required
                            class="min-h-9 rounded-xl border border-line bg-canvas/30 px-3 text-sm font-normal text-ink placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">
                        @error('title')
                            <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                        <span>Nominal (Rp)</span>
                        <input type="number" name="amount" value="{{ old('amount') }}" placeholder="15000"
                            min="500" required
                            class="min-h-9 rounded-xl border border-line bg-canvas/30 px-3 text-sm font-normal text-ink placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">
                        @error('amount')
                            <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                        <span>Tanggal pengeluaran</span>
                        <input type="date" name="spent_at" value="{{ old('spent_at', now()->toDateString()) }}"
                            max="{{ now()->toDateString() }}" required
                            class="min-h-11 rounded-xl border border-line bg-canvas/30 px-3.5 text-sm font-normal text-ink transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">
                        @error('spent_at')
                            <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                        @enderror
                    </label>

                    <div class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                        <span>Foto nota / struk (opsional)</span>
                        <input type="file" data-filepond data-max-size="5MB" name="receipt" accept="image/png,image/jpeg"
                           >
                        @error('receipt')
                            <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                        <span>Catatan tambahan (opsional)</span>
                        <textarea name="notes" rows="2" placeholder="Keterangan toko atau rincian barang"
                            class="rounded-xl border border-line bg-canvas/30 p-3 text-sm font-normal text-ink placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">{{ old('notes') }}</textarea>
                        @error('notes')
                            <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                        @enderror
                    </label>

                    <button type="submit"
                        class="ui-btn ui-btn-primary">Simpan
                        pengeluaran</button>
                </form>
            </section>
        @endif
    </div>

    @if (auth()->user()?->canManageMembers())
        <livewire:expense-assistant />
    @endif
</x-dynamic-component>
