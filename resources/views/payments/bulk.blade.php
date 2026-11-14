<x-layouts.app title="Bayar sekaligus">
    <header class="flex flex-col gap-2">
        <span class="text-xs font-semibold uppercase tracking-wider text-brand">Pembayaran kas</span>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-black">Bayar beberapa tagihan sekaligus</h1>
        <p class="max-w-2xl font-serif text-base sm:text-lg leading-relaxed text-muted">Pilih tagihan yang mau dibayar, transfer satu kali sesuai total, lalu kirim satu bukti. Admin memverifikasi semuanya bersamaan.</p>
    </header>

    @php
        $isDynamicQris = str_starts_with($qrContent ?? '', '000201');
        $qrImageUrl = $isDynamicQris
            ? 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=8&data=' . urlencode($qrContent)
            : $qrContent;
        $allIds = $invoices->pluck('id')->all();
    @endphp

    @if ($isAssisting)
        <section aria-label="Mode bantuan bendahara" class="max-w-4xl rounded-2xl border border-brand/30 bg-brand-soft p-4 text-xs leading-relaxed text-black shadow-xs">
            Anda membantu <strong>{{ $member->name }}</strong> (No. Absen/NIM <span class="font-mono font-bold">{{ $member->student_no ?? '-' }}</span>) membayar beberapa tagihan sekaligus.
        </section>
    @endif

    <div class="grid max-w-4xl gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section aria-labelledby="pilih-tagihan" class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
            <form method="GET" action="{{ route('payments.bulk', $anchor) }}" class="flex flex-col">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                    <div>
                        <h2 id="pilih-tagihan" class="text-base font-bold text-black">Pilih tagihan</h2>
                        <p class="text-xs text-muted">Diurutkan dari tenggat terdekat. Tiap tagihan dibayar penuh sebesar sisanya.</p>
                    </div>
                    <a href="{{ route('payments.bulk', ['invoice' => $anchor, 'ids' => $allIds]) }}"
                       class="inline-flex min-h-11 items-center rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Pilih semua</a>
                </div>

                <div class="divide-y divide-black/5">
                    @forelse ($invoices as $item)
                        <label class="flex min-h-14 cursor-pointer items-center gap-3 px-5 py-3.5 transition-colors hover:bg-black/[0.02]">
                            <input type="checkbox" name="ids[]" value="{{ $item->id }}" @checked(in_array($item->id, $selectedIds, true))
                                   class="size-5 shrink-0 rounded border-black/20 text-brand focus:ring-brand">
                            <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-black">{{ $item->period->title }}</span>
                                    @if ($item->isOverdue())
                                        <span class="rounded-full border border-[#fecaca] bg-[#fde4e0] px-2 py-0.5 text-[10px] font-semibold text-[#8f1d0e]">Lewat tenggat</span>
                                    @endif
                                    @if ($item->isPartial())
                                        <span class="rounded-full border border-[#fed7aa] bg-[#fff1d6] px-2 py-0.5 text-[10px] font-semibold text-[#7a4a00]">Sudah dicicil</span>
                                    @endif
                                </span>
                                <span class="text-xs text-muted">Tenggat {{ $item->period->dueAt()->locale('id')->translatedFormat('l, j F Y') }}, 23.59 WIB</span>
                            </span>
                            <span class="shrink-0 text-sm font-bold tabular-nums text-black">Rp{{ number_format($item->remainingAmount(), 0, ',', '.') }}</span>
                        </label>
                    @empty
                        <p class="px-6 py-10 text-center text-xs text-muted">Tidak ada tagihan yang bisa dibayar. Semua sudah lunas atau sedang menunggu verifikasi.</p>
                    @endforelse
                </div>

                @if ($invoices->isNotEmpty())
                    <div class="border-t border-line px-5 py-4">
                        <button type="submit"
                                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-black px-5 text-xs font-semibold text-white transition-colors hover:bg-black/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            Hitung total
                        </button>
                    </div>
                @endif
            </form>
        </section>

        <aside class="flex h-fit flex-col gap-5 rounded-2xl border border-line bg-white p-6 text-center shadow-xs">
            @if ($total > 0)
                <div class="flex flex-col items-center gap-1.5">
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted">Total {{ count($selectedIds) }} tagihan</span>
                    <strong class="text-3xl font-bold tracking-tight text-black tabular-nums">Rp{{ number_format($total, 0, ',', '.') }}</strong>
                </div>

                @if ($qrContent)
                    <div class="flex flex-col items-center gap-3">
                        <div class="rounded-2xl border border-line bg-white p-3 shadow-xs">
                            <img src="{{ $qrImageUrl }}" alt="QRIS pembayaran sekaligus Rp{{ number_format($total, 0, ',', '.') }}" class="size-56 object-contain">
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $isDynamicQris ? 'border border-[#bbf7d0] bg-[#dcfce7] text-[#14532d]' : 'border border-[#fed7aa] bg-[#fff1d6] text-[#7a4a00]' }}">
                            {{ $isDynamicQris ? 'QRIS dinamis (nominal terkunci)' : 'QRIS statis: ketik nominal tepat Rp'.number_format($total, 0, ',', '.') }}
                        </span>
                    </div>
                @else
                    <p class="rounded-2xl border border-dashed border-line bg-canvas/60 p-4 text-xs leading-relaxed text-muted">QRIS belum diatur. Hubungi bendahara kelas untuk konfirmasi pembayaran.</p>
                @endif

                <form method="POST" action="{{ route('payments.bulk.submit', $anchor) }}" enctype="multipart/form-data" class="flex flex-col gap-3 text-left">
                    @csrf
                    @foreach ($selectedIds as $id)
                        <input type="hidden" name="ids[]" value="{{ $id }}">
                    @endforeach
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                        <span>Upload bukti pembayaran</span>
                        <input type="file" name="proof" accept="image/jpeg,image/png,image/webp" required
                               class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3 py-2 text-xs font-normal text-black focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                    </label>
                    @error('proof')<span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>@enderror
                    @error('ids')<span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>@enderror
                    <button type="submit"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-5 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        Kirim bukti untuk {{ count($selectedIds) }} tagihan
                    </button>
                </form>
            @else
                <p class="text-xs leading-relaxed text-muted">Centang tagihan di sebelah kiri lalu tekan <strong class="text-black">Hitung total</strong> untuk menampilkan QRIS dan formulir bukti.</p>
                @error('ids')<span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>@enderror
            @endif

            <a href="{{ route('payments.create', $anchor) }}" class="text-xs font-semibold text-brand hover:underline">&larr; Bayar satu tagihan saja</a>
        </aside>
    </div>
</x-layouts.app>
