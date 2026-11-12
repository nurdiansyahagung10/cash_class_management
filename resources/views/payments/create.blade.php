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
        $paidAmount = $paidAmount ?? $invoice->paidAmount();
        $remainingAmount = $remainingAmount ?? $invoice->remainingAmount();
        $paidPayments = $paidPayments ?? $invoice->payments()->where('status', 'paid')->latest('id')->get();
        $isOverdue = $invoice->isOverdue();
        $progressPct = $invoice->amount > 0 ? (int) round($paidAmount * 100 / $invoice->amount) : 0;
        $isAssisting = $isAssisting ?? (auth()->user()?->isStaff() && $invoice->user_id !== auth()->id());
    @endphp

    @if ($isAssisting)
        <section aria-label="Mode bantuan bendahara" class="flex max-w-4xl items-start gap-3.5 rounded-2xl border border-brand/30 bg-brand-soft p-5 shadow-xs">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand text-white font-bold">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
            <div class="flex flex-col gap-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-sm font-bold text-black">
                        Bantu Pembayaran Kas Siswa (Mode Bendahara Kelas)
                    </h2>
                    <span class="rounded-full bg-navy text-white px-2.5 py-0.5 text-[10px] font-semibold">
                        Bendahara: {{ auth()->user()->name }}
                    </span>
                </div>
                <p class="text-xs text-muted leading-relaxed">
                    Anda sedang membantu membayarkan iuran kas atas nama siswa <strong>{{ $invoice->user->name }}</strong> (No. Absen/NIM: <span class="font-mono font-bold text-black">{{ $invoice->user->student_no ?? '-' }}</span>). Anda dapat mengatur nominal cicilan siswa atau mengunggah bukti pembayaran QRIS / tanda terima setor tunai.
                </p>
            </div>
        </section>
    @endif

    @if ($isOverdue)
        <section role="alert" class="flex max-w-4xl items-start gap-3.5 rounded-2xl border border-[#fecaca] bg-[#fff5f5] p-5 shadow-xs">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#fde4e0] text-[#8f1d0e] font-bold">
                !
            </div>
            <div class="flex flex-col gap-1">
                <h2 class="text-sm font-bold text-[#8f1d0e]">
                    Lewat Tenggat Waktu ({{ $invoice->overdueDays() }} hari) — Fitur Menyicil Ditutup
                </h2>
                <p class="text-xs text-[#8f1d0e]/90 leading-relaxed">
                    Jatuh tempo tagihan ini adalah <strong>{{ $invoice->period->due_date->format('d M Y') }}</strong>. Karena sudah melewati tenggat waktu, pembayaran bertahap (cicilan) tidak lagi dapat diajukan dan Anda wajib melunasi seluruh sisa tagihan sebesar <strong>Rp{{ number_format($remainingAmount, 0, ',', '.') }}</strong> sekaligus.
                </p>
            </div>
        </section>
    @endif

    <div class="grid max-w-4xl gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <section class="flex flex-col items-center gap-6 rounded-2xl border border-line bg-white p-6 text-center shadow-xs sm:p-8">
            <div class="flex w-full items-center justify-between text-left">
                <span class="flex items-center gap-2 text-xs font-semibold text-black">
                    <span class="size-2 rounded-full {{ $invoice->isPartial() ? 'bg-brand' : 'bg-[#f59e0b]' }}"></span>
                    {{ $invoice->isPartial() ? 'Menyicil (' . $progressPct . '% terbayar)' : 'Menunggu verifikasi' }}
                </span>
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
                <strong class="text-3xl sm:text-4xl font-bold tracking-tight text-black tabular-nums">Rp{{ number_format($payment->amount, 0, ',', '.') }}</strong>
                @if ($payment->amount < $remainingAmount)
                    <span class="rounded-full bg-[#fff1d6] px-2.5 py-0.5 text-[11px] font-semibold text-[#7a4a00] border border-[#fed7aa]">
                        Nominal Cicilan (Sisa tagihan: Rp{{ number_format($remainingAmount, 0, ',', '.') }})
                    </span>
                @elseif ($paidAmount > 0)
                    <span class="rounded-full bg-[#dcfce7] px-2.5 py-0.5 text-[11px] font-semibold text-[#14532d] border border-[#bbf7d0]">
                        Pelunasan Sisa Tagihan (Sudah dicicil Rp{{ number_format($paidAmount, 0, ',', '.') }})
                    </span>
                @endif
                @if (!$isDynamicQris)
                    <button type="button"
                            onclick="navigator.clipboard.writeText('{{ $payment->amount }}'); this.innerText = 'Nominal tersalin!'; setTimeout(() => this.innerText = 'Salin nominal angka', 2000)"
                            class="mt-1 inline-flex min-h-9 items-center rounded-xl border border-line bg-canvas/60 px-3 text-xs font-semibold text-muted hover:text-black transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        Salin nominal angka
                    </button>
                @endif
            </div>

            {{-- Fitur Menyicil Sampai Tenggat --}}
            @if ($invoice->canInstallment() && $remainingAmount > 1000)
                <div class="flex w-full flex-col gap-3 rounded-2xl border border-brand/20 bg-brand-soft/30 p-4 text-left">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-bold text-black">Opsi Cicilan Uang Kas (Aktif s/d {{ $invoice->period->due_date->format('d M Y') }})</span>
                        <span class="text-[11px] font-medium text-muted">Sisa: Rp{{ number_format($remainingAmount, 0, ',', '.') }}</span>
                    </div>
                    <p class="text-xs text-muted leading-relaxed">
                        Anda dapat mencicil pembayaran sebagian sebelum tenggat waktu. Pilih nominal cicilan untuk memperbarui nominal tagihan & QRIS:
                    </p>
                    <form method="POST" action="{{ route('payments.installment', $invoice) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="number" name="amount" value="{{ old('amount', $payment->amount) }}" min="1000" max="{{ $remainingAmount }}" step="500"
                               class="min-h-10 flex-1 rounded-xl border border-black/15 bg-white px-3 text-xs font-semibold text-black focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-black px-3.5 text-xs font-semibold text-white transition-colors hover:bg-black/80">
                            Atur Nominal Cicilan
                        </button>
                        @if ($payment->amount !== $remainingAmount)
                            <button type="submit" name="amount" value="{{ $remainingAmount }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-line bg-white px-3 text-xs font-semibold text-black hover:bg-canvas">
                                Bayar Penuh
                            </button>
                        @endif
                    </form>
                    @error('amount')<span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>@enderror
                </div>
            @endif

            <div class="flex w-full flex-col gap-2 rounded-2xl border border-line bg-canvas/40 p-4.5 text-left">
                @if ($isDynamicQris)
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted">Petunjuk Pembayaran QRIS Dinamis:</span>
                    <ol class="list-decimal list-inside space-y-1.5 text-xs text-muted leading-relaxed">
                        <li>Buka aplikasi m-Banking atau e-wallet (DANA, GoPay, OVO, BCA, Livin Mandiri, dll).</li>
                        <li>Scan QRIS di atas (jika di ponsel yang sama, unduh gambar lalu gunakan fitur <em>Scan dari Galeri</em>).</li>
                        <li><strong class="text-black font-semibold">Nominal otomatis:</strong> Tagihan <strong>Rp{{ number_format($payment->amount, 0, ',', '.') }}</strong> langsung terkunci dan tidak perlu diketik manual.</li>
                        <li>Konfirmasi pembayaran di aplikasi Anda dan simpan tangkapan layar (screenshot) bukti transfer.</li>
                        <li>Unggah foto bukti pembayaran pada formulir di bawah ini.</li>
                    </ol>
                @else
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted">Petunjuk Pembayaran QRIS Statis:</span>
                    <ol class="list-decimal list-inside space-y-1.5 text-xs text-muted leading-relaxed">
                        <li>Buka aplikasi m-Banking atau e-wallet (DANA, GoPay, OVO, BCA, dll).</li>
                        <li>Scan QRIS di atas (jika di ponsel yang sama, unduh gambar lalu gunakan fitur <em>Scan dari Galeri</em>).</li>
                        <li><strong class="text-black font-semibold">PENTING:</strong> Masukkan nominal transfer <strong>tepat Rp{{ number_format($payment->amount, 0, ',', '.') }}</strong> saat diminta di aplikasi.</li>
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

            <form method="POST" action="{{ route('payments.proof', $invoice) }}" enctype="multipart/form-data" class="flex w-full flex-col gap-3.5 text-left">
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
                @if ($isAssisting)
                    <div class="flex justify-between gap-4"><dt class="text-muted">Nama siswa</dt><dd class="font-bold text-black">{{ $invoice->user->name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">No. Absen / NIM</dt><dd class="font-mono font-bold text-black">{{ $invoice->user->student_no ?? '-' }}</dd></div>
                @endif
                <div class="flex justify-between gap-4"><dt class="text-muted">Total tagihan</dt><dd class="font-bold text-black tabular-nums">Rp{{ number_format($invoice->amount, 0, ',', '.') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Sudah dicicil</dt><dd class="font-semibold text-[#14532d] tabular-nums">Rp{{ number_format($paidAmount, 0, ',', '.') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Sisa tagihan</dt><dd class="font-bold text-brand tabular-nums">Rp{{ number_format($remainingAmount, 0, ',', '.') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Dibayar sekarang</dt><dd class="font-bold text-black tabular-nums">Rp{{ number_format($payment->amount, 0, ',', '.') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Jatuh tempo</dt><dd class="font-medium {{ $isOverdue ? 'text-[#8f1d0e] font-bold' : 'text-black' }}">{{ $invoice->period->due_date->format('d M Y') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Status tenggat</dt><dd class="font-semibold {{ $isOverdue ? 'text-[#8f1d0e]' : 'text-[#14532d]' }}">{{ $isOverdue ? 'Lewat tenggat (Wajib lunas)' : 'Bisa menyicil' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Order ID</dt><dd class="font-mono text-xs text-black">{{ $payment->order_id }}</dd></div>
            </dl>

            @if ($paidPayments->isNotEmpty())
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted">Riwayat cicilan lunas ({{ $paidPayments->count() }})</span>
                    <div class="flex flex-col gap-1.5">
                        @foreach ($paidPayments as $hist)
                            <div class="flex items-center justify-between rounded-xl bg-canvas/50 px-3 py-2 text-xs">
                                <span class="font-mono text-[11px] text-muted">{{ $hist->paid_at?->format('d M Y') ?? $hist->created_at->format('d M Y') }}</span>
                                <span class="font-bold text-[#14532d] tabular-nums">+Rp{{ number_format($hist->amount, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>
    </div>
</x-layouts.app>
