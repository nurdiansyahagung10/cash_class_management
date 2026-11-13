<x-layouts.app title="Kategori tagihan">
    <header class="flex flex-col gap-2">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-xs font-semibold text-muted">
                <span class="hover:text-black transition-colors">Kas Kelas 1A</span>
                <span class="text-black/20">/</span>
                <span class="text-black">Kategori tagihan</span>
                <span class="text-black/20">·</span>
                <span class="font-normal">{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </header>

    <div class="flex lg:flex-wrap flex-wrap-reverse items-start gap-6">
        <section aria-labelledby="judul-daftar"
            class="flex min-w-0 flex-[999_1_30rem] flex-col rounded-2xl border border-line bg-white shadow-xs">
            <div class="flex flex-col gap-3 border-b border-line px-4 py-5 sm:px-6">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <h2 id="judul-daftar" class="text-base font-bold text-black sm:text-lg">Daftar kategori</h2>
                    <span
                        class="inline-flex items-center rounded-full border border-brand/20 bg-brand-soft px-3 py-1 text-xs font-semibold tabular-nums text-brand">{{ $categories->total() }} kategori</span>
                </div>
                <form method="GET" action="{{ route('categories.index') }}" class="flex min-w-0 gap-2 sm:max-w-sm">
                    <label for="search-category" class="sr-only">Cari kategori</label>
                    <input id="search-category" type="search" name="q" value="{{ $search }}" placeholder="Cari nama / keterangan..."
                        class="min-h-11 min-w-0 flex-1 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-xs placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                    <button type="submit"
                        class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-canvas">Cari</button>
                </form>
            </div>

            <div class="divide-y divide-black/5">
                @forelse ($categories as $category)
                    <div
                        class="flex items-start justify-between gap-4 px-4 py-4 transition-colors hover:bg-black/1.5 sm:px-6">
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                                <span class="text-[15px] font-semibold text-black wrap-break-word">{{ $category->name }}</span>
                                <span
                                    class="rounded-full bg-brand-soft px-2.5 py-0.5 text-xs font-semibold text-brand border border-brand/20">
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
            class="flex min-w-72 flex-[1_1_18rem] flex-col gap-5 rounded-2xl border border-line bg-white p-6 shadow-xs">
            <div class="flex flex-col gap-1">
                <h2 id="judul-tambah" class="text-lg font-bold text-black">Tambah kategori</h2>
                <span class="text-xs text-muted">Bisa digunakan saat membuat tagihan baru.</span>
            </div>

            <form method="POST" action="{{ route('categories.store') }}" class="flex flex-col gap-4">
                @csrf
                <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                    <span>Nama kategori</span>
                    <input type="text" name="name" value="{{ old('name') }}"
                        placeholder="Contoh: Study Tour / Jaket Angkatan" required
                        class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                    @error('name')
                        <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                    @enderror
                </label>

                <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                    <span>Keterangan (opsional)</span>
                    <textarea name="description" rows="2" placeholder="Deskripsi peruntukan tagihan"
                        class="rounded-xl border border-black/15 bg-canvas/30 p-3 text-sm font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">{{ old('description') }}</textarea>
                    @error('description')
                        <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                    @enderror
                </label>

                <button type="submit"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Simpan
                    kategori</button>
            </form>
        </section>
    </div>
</x-layouts.app>
