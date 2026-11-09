<x-layouts.app title="Periode tagihan">
    <header class="flex flex-col gap-2">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-xs font-semibold text-muted">
                <span class="hover:text-black transition-colors">Kas Kelas 1A</span>
                <span class="text-black/20">/</span>
                <span class="text-black">Tagihan</span>
                <span class="text-black/20">·</span>
                <span class="font-normal">{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </header>

    <div class="flex flex-wrap items-start gap-6">
        <section aria-labelledby="judul-periode"
            class="flex min-w-0 flex-[999_1_30rem] flex-col rounded-2xl border border-line bg-white shadow-xs">
            <div class="flex flex-col gap-3.5 border-b border-line px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="judul-periode" class="text-lg font-bold text-black">Riwayat periode
                        ({{ $periods->count() }})</h2>
                    <form method="GET" action="{{ route('periods.index') }}" class="flex items-center gap-2">
                        @if ($statusFilter)
                            <input type="hidden" name="status" value="{{ $statusFilter }}">
                        @endif
                        <label for="search-period" class="sr-only">Cari judul periode</label>
                        <input id="search-period" type="search" name="q" value="{{ $search }}"
                            placeholder="Cari judul periode..."
                            class="min-h-11 w-48 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-xs placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20 sm:w-60">
                        <button type="submit"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            Cari
                        </button>
                    </form>
                </div>

                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                    <a href="{{ route('periods.index', array_filter(['q' => $search])) }}"
                        class="rounded-xl px-3 py-1.5 font-semibold transition-colors {{ empty($statusFilter) ? 'bg-black text-white' : 'text-muted hover:bg-canvas' }}">
                        Semua
                    </a>
                    <a href="{{ route('periods.index', array_filter(['status' => 'active', 'q' => $search])) }}"
                        class="rounded-xl px-3 py-1.5 font-semibold transition-colors {{ $statusFilter === 'active' ? 'bg-brand text-white shadow-xs' : 'text-muted hover:bg-canvas' }}">
                        Berjalan
                    </a>
                    <a href="{{ route('periods.index', array_filter(['status' => 'overdue', 'q' => $search])) }}"
                        class="rounded-xl px-3 py-1.5 font-semibold transition-colors {{ $statusFilter === 'overdue' ? 'bg-[#8f1d0e] text-white shadow-xs' : 'text-muted hover:bg-canvas' }}">
                        Lewat Tempo
                    </a>
                    <a href="{{ route('periods.index', array_filter(['status' => 'completed', 'q' => $search])) }}"
                        class="rounded-xl px-3 py-1.5 font-semibold transition-colors {{ $statusFilter === 'completed' ? 'bg-[#14532d] text-white shadow-xs' : 'text-muted hover:bg-canvas' }}">
                        Lunas Semua
                    </a>
                </div>
            </div>

            <div class="divide-y divide-black/5">
                @forelse ($periods as $period)
                    @php
                        $isCompleted = $period->invoices_count > 0 && $period->paid_count === $period->invoices_count;
                        $isOverdue = $period->due_date->isPast() && !$isCompleted;
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
                    <input type="number" name="amount" value="{{ old('amount') }}" placeholder="10000" required
                        class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                    @error('amount')
                        <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                    @enderror
                </label>

                <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                    <span>Jatuh tempo</span>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" required
                        class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                    @error('due_date')
                        <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                    @enderror
                </label>

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
