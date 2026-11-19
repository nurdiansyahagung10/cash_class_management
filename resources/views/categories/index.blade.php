<x-layouts.app title="Kategori tagihan">
    <x-ui.page-header :eyebrow="'Kelola · ' . now()->translatedFormat('l, d F Y')" title="Kategori tagihan"
        description="Kelompokkan tagihan kelas, misalnya uang kas, acara, atau merchandise." />

    <div class="flex lg:flex-wrap flex-wrap-reverse items-start gap-6">
        <section aria-labelledby="judul-daftar"
            class="flex min-w-0 flex-[999_1_30rem] flex-col rounded-2xl border border-line bg-surface shadow-xs">
            <div class="flex flex-col gap-3 border-b border-line px-4 py-5 sm:px-6">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <h2 id="judul-daftar" class="text-base font-semibold text-ink">Daftar kategori</h2>
                    <span
                        class="ui-tag ui-tag-plain tabular-nums font-mono">{{ $categories->total() }} kategori</span>
                </div>
                <form method="GET" action="{{ route('categories.index') }}" class="flex min-w-0 gap-2 sm:max-w-sm">
                    <label for="search-category" class="sr-only">Cari kategori</label>
                    <input id="search-category" type="search" name="q" value="{{ $search }}" placeholder="Cari nama / keterangan..."
                        class="min-h-9 min-w-0 flex-1 rounded-xl border border-line bg-canvas/30 px-3 text-xs placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">
                    <button type="submit"
                        class="ui-btn ui-btn-outline shrink-0">Cari</button>
                </form>
            </div>

            <div class="divide-y divide-ink/5">
                @forelse ($categories as $category)
                    <div
                        class="flex items-start justify-between gap-4 px-4 py-4 transition-colors hover:bg-ink/1.5 sm:px-6">
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                                <span class="text-[15px] font-semibold text-ink wrap-break-word">{{ $category->name }}</span>
                                <span
                                    class="ui-tag ui-tag-info">
                                    {{ $category->billing_periods_count }} tagihan
                                </span>
                            </div>
                            @if ($category->description)
                                <p class="text-xs leading-relaxed text-muted wrap-break-word">{{ $category->description }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-12 text-center text-xs text-muted">{{ $search ? 'Tidak ada kategori yang cocok.' : 'Belum ada kategori tagihan.' }}</p>
                @endforelse
            </div>

            <x-report-pager :paginator="$categories" label="kategori" />
        </section>

        <section aria-labelledby="judul-tambah"
            class="flex min-w-72 flex-[1_1_18rem] flex-col gap-5 rounded-2xl border border-line bg-surface p-6 shadow-xs">
            <div class="flex flex-col gap-1">
                <h2 id="judul-tambah" class="text-lg font-semibold text-ink">Tambah kategori</h2>
                <span class="text-xs text-muted">Bisa digunakan saat membuat tagihan baru.</span>
            </div>

            <form method="POST" action="{{ route('categories.store') }}" class="flex flex-col gap-4">
                @csrf
                <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                    <span>Nama kategori</span>
                    <input type="text" name="name" value="{{ old('name') }}"
                        placeholder="Contoh: Study Tour / Jaket Angkatan" required
                        class="min-h-9 rounded-xl border border-line bg-canvas/30 px-3 text-sm font-normal text-ink placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">
                    @error('name')
                        <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                    @enderror
                </label>

                <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                    <span>Keterangan (opsional)</span>
                    <textarea name="description" rows="2" placeholder="Deskripsi peruntukan tagihan"
                        class="rounded-xl border border-line bg-canvas/30 p-3 text-sm font-normal text-ink placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">{{ old('description') }}</textarea>
                    @error('description')
                        <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                    @enderror
                </label>

                <button type="submit"
                    class="ui-btn ui-btn-primary">Simpan
                    kategori</button>
            </form>
        </section>
    </div>
</x-layouts.app>
