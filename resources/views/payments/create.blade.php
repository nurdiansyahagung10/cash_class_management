<x-layouts.app title="Bayar kas">
    <header class="flex flex-col gap-2">
        <span class="text-xs font-semibold uppercase tracking-wider text-brand">Pembayaran kas</span>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-black">Bayar secara QRIS</h1>
        <p class="max-w-2xl font-serif text-base sm:text-lg leading-relaxed text-muted">Scan QRIS Dana milik kelas, lalu kirim bukti pembayaran. Admin akan memeriksa sebelum saldo dicatat.</p>
    </header>

    @php
        $isDynamicQris = str_starts_with($payment->qr_content ?? '', '000201');
        $qrImageUrl = $isDynamicQris
            ? 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=8&data=' . urlencode($payment->qr_content)
            : $payment->qr_content;
    @endphp

    <div class="grid max-w-4xl gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <section class="flex flex-col items-center gap-6 rounded-2xl border border-line bg-white p-6 text-center shadow-xs sm:p-8">
            <div class="flex w-full items-center justify-between text-left">
                <span class="flex items-center gap-2 text-xs font-semibold text-black"><span class="size-2 rounded-full bg-[#f59e0b]"></span>Menunggu verifikasi</span>
                @if ($isDynamicQris)
                    <span class="rounded-full bg-[#dcfce7] px-3 py-1 text-xs font-semibold text-[#14532d] border border-[#bbf7d0]">QRIS dinamis (Nominal terkunci)</span>
                @else
                    <span class="rounded-full bg-[#fff1d6] px-3 py-1 text-xs font-semibold text-[#7a4a00] border border-[#fed7aa]">QRIS statis</span>
                @endif
            </div>

            @if ($payment->qr_content)
                <div class="flex flex-col items-center gap-3">
                    <div class="rounded-2xl border border-line bg-white p-3 shadow-xs">
                        <img src="{{ $qrImageUrl }}" alt="QRIS pembayaran {{ $invoice->period->title }}" class="size-64 object-contain sm:size-72">
                    </div>
                    <a href="{{ $qrImageUrl }}" download="qris-kas-kelas-{{ $invoice->id }}.png" target="_blank" rel="noopener"
                       class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-line bg-white px-4 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        <svg class="size-4 text-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Unduh gambar QRIS
                    </a>
                </div>
            @else
                <div class="flex size-64 flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-line bg-canvas/60 p-6 text-center sm:size-72">
                    <span class="text-sm font-semibold text-black">QRIS belum diatur</span>
                    <span class="text-xs text-muted leading-relaxed">Pengurus kelas belum mengunggah kode QRIS. Silakan hubungi bendahara kelas untuk konfirmasi pembayaran.</span>
                </div>
            @endif

            <div class="flex flex-col items-center gap-1.5">
                <span class="rounded-full bg-brand-soft px-3 py-0.5 text-xs font-semibold text-brand border border-brand/20">
                    {{ $payment->category->name ?? $invoice->period->category->name ?? 'Uang Kas' }}
                </span>
                <span class="text-xs font-medium text-muted">{{ $invoice->period->title }}</span>
                <strong class="text-3xl sm:text-4xl font-bold tracking-tight text-black tabular-nums">Rp{{ number_format($invoice->amount, 0, ',', '.') }}</strong>
                @if (! $isDynamicQris)
                    <button type="button"
                            onclick="navigator.clipboard.writeText('{{ $invoice->amount }}'); this.innerText = 'Nominal tersalin!'; setTimeout(() => this.innerText = 'Salin nominal angka', 2000)"
                            class="mt-1 inline-flex min-h-9 items-center rounded-xl border border-line bg-canvas/60 px-3 text-xs font-semibold text-muted hover:text-black transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        Salin nominal angka
                    </button>
                @endif
            </div>

            <div class="flex w-full flex-col gap-2 rounded-2xl border border-line bg-canvas/40 p-4.5 text-left">
                @if ($isDynamicQris)
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted">Petunjuk Pembayaran QRIS Dinamis:</span>
                    <ol class="list-decimal list-inside space-y-1.5 text-xs text-muted leading-relaxed">
                        <li>Buka aplikasi m-Banking atau e-wallet (DANA, GoPay, OVO, BCA, Livin Mandiri, dll).</li>
                        <li>Scan QRIS di atas (jika di ponsel yang sama, unduh gambar lalu gunakan fitur <em>Scan dari Galeri</em>).</li>
                        <li><strong class="text-black font-semibold">Nominal otomatis:</strong> Tagihan <strong>Rp{{ number_format($invoice->amount, 0, ',', '.') }}</strong> langsung terkunci dan tidak perlu diketik manual.</li>
                        <li>Konfirmasi pembayaran di aplikasi Anda dan simpan tangkapan layar (screenshot) bukti transfer.</li>
                        <li>Unggah foto bukti pembayaran pada formulir di bawah ini.</li>
                    </ol>
                @else
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted">Petunjuk Pembayaran QRIS Statis:</span>
                    <ol class="list-decimal list-inside space-y-1.5 text-xs text-muted leading-relaxed">
                        <li>Buka aplikasi m-Banking atau e-wallet (DANA, GoPay, OVO, BCA, dll).</li>
                        <li>Scan QRIS di atas (jika di ponsel yang sama, unduh gambar lalu gunakan fitur <em>Scan dari Galeri</em>).</li>
                        <li><strong class="text-black font-semibold">PENTING:</strong> Masukkan nominal transfer <strong>tepat Rp{{ number_format($invoice->amount, 0, ',', '.') }}</strong> saat diminta di aplikasi.</li>
                        <li>Simpan tangkapan layar (screenshot) bukti transfer, lalu unggah pada formulir di bawah.</li>
                    </ol>
                @endif
            </div>

            @if ($payment->rejection_note)
                <div class="flex w-full flex-col gap-1.5 rounded-2xl border border-[#fde4e0] bg-[#fff5f5] p-4 text-left">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#8f1d0e]">Bukti sebelumnya ditolak</span>
                    <p class="text-xs font-medium text-[#8f1d0e]">{{ $payment->rejection_note }}</p>
                    <span class="text-[11px] text-muted">Silakan unggah ulang foto bukti transfer yang valid di bawah.</span>
                </div>
            @endif

            @if ($payment->proof_path)
                <div class="flex w-full flex-col items-center gap-3 rounded-2xl border border-line bg-canvas/40 p-4 text-left sm:flex-row sm:justify-between">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('payments.proof.show', $payment) }}" target="_blank" rel="noopener" class="size-14 shrink-0 overflow-hidden rounded-xl border border-line bg-white shadow-xs">
                            <img src="{{ route('payments.proof.show', $payment) }}" alt="Bukti transfer terkirim" class="size-full object-cover">
                        </a>
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-black">Bukti sudah diunggah</span>
                            <span class="text-xs text-muted">Menunggu pengecekan oleh admin</span>
                        </div>
                    </div>
                    <a href="{{ route('payments.proof.show', $payment) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-brand hover:underline">Lihat foto &rarr;</a>
                </div>
            @endif

            <form method="POST" action="{{ route('payments.proof', $invoice) }}" enctype="multipart/form-data" class="flex w-full max-w-md flex-col gap-3.5 text-left">
                @csrf
                <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                    <span>{{ $payment->proof_path ? 'Unggah ulang bukti baru' : 'Upload bukti pembayaran' }}</span>
                    <input type="file" name="proof" accept="image/jpeg,image/png,image/webp" required class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 py-2 text-xs font-normal text-black focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                </label>
                @error('proof')<span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>@enderror
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-5 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    {{ $payment->proof_path ? 'Kirim ulang bukti' : 'Kirim bukti pembayaran' }}
                </button>
            </form>
        </section>

        <aside class="flex h-fit flex-col gap-5 rounded-2xl border border-line bg-white p-6 shadow-xs">
            <div class="flex flex-col gap-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-muted">Detail tagihan</span>
                <h2 class="text-base font-bold text-black">{{ $invoice->period->title }}</h2>
            </div>
            <dl class="flex flex-col gap-3 border-y border-line py-4 text-xs">
                <div class="flex justify-between gap-4"><dt class="text-muted">Nominal</dt><dd class="font-bold text-black tabular-nums">Rp{{ number_format($invoice->amount, 0, ',', '.') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Jatuh tempo</dt><dd class="font-medium text-black">{{ $invoice->period->due_date->format('d M Y') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Order ID</dt><dd class="font-mono text-xs text-black">{{ $payment->order_id }}</dd></div>
            </dl>
        </aside>
    </div>
</x-layouts.app>
