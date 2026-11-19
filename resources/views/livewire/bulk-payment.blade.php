<div class="grid max-w-4xl gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
    {{-- Pilih tagihan: every tick re-renders the total + QRIS --}}
    <section aria-labelledby="pilih-tagihan" class="ui-card flex flex-col overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
            <div>
                <h2 id="pilih-tagihan" class="text-base font-semibold text-ink">Pilih tagihan</h2>
                <p class="text-xs text-muted">Diurutkan dari tenggat terdekat. Tiap tagihan dibayar penuh sebesar sisanya.</p>
            </div>
            @if ($invoices->isNotEmpty())
                <div class="flex items-center gap-1.5">
                    <button type="button" wire:click="selectAll" class="ui-btn ui-btn-outline">Pilih semua</button>
                    @if ($selectedIds !== [])
                        <button type="button" wire:click="clearSelection" class="ui-btn ui-btn-ghost text-muted">Kosongkan</button>
                    @endif
                </div>
            @endif
        </div>

        <div class="divide-y divide-line">
            @forelse ($invoices as $item)
                <label wire:key="tagihan-{{ $item->id }}" class="flex min-h-14 cursor-pointer items-center gap-3 px-5 py-3.5 transition-colors hover:bg-ink/[0.02] has-[:checked]:bg-brand/[0.06]">
                    <input type="checkbox" wire:model.live="selected" value="{{ $item->id }}"
                        class="size-5 shrink-0 rounded border-line-strong accent-[var(--color-brand)]">
                    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-ink">{{ $item->period->title }}</span>
                            @if ($item->isOverdue())
                                <span class="ui-tag ui-tag-danger">Lewat tenggat</span>
                            @endif
                            @if ($item->isPartial())
                                <span class="ui-tag ui-tag-warning">Sudah dicicil</span>
                            @endif
                        </span>
                        <span class="text-xs text-muted">Tenggat {{ $item->period->dueAt()->locale('id')->translatedFormat('l, j F Y') }}, 23.59 WIB</span>
                    </span>
                    <span class="shrink-0 text-sm font-semibold tabular-nums text-ink">Rp{{ number_format($item->remainingAmount(), 0, ',', '.') }}</span>
                </label>
            @empty
                <p class="px-6 py-10 text-center text-xs text-muted">Tidak ada tagihan yang bisa dibayar. Semua sudah lunas atau sedang menunggu verifikasi.</p>
            @endforelse
        </div>
    </section>

    {{-- Ringkasan + QRIS --}}
    <aside class="ui-card relative flex h-fit flex-col gap-5 p-6 text-center">
        <div class="flex flex-col items-center gap-1.5">
            <span class="ui-eyebrow">Total {{ count($selectedIds) }} tagihan</span>
            <strong wire:loading.class="opacity-40" wire:target="selected, selectAll, clearSelection"
                class="text-3xl font-semibold tracking-tight text-ink tabular-nums transition-opacity">Rp{{ number_format($total, 0, ',', '.') }}</strong>
        </div>

        @if ($total > 0)
            @if ($qris)
                <div class="flex flex-col items-center gap-3">
                    <div class="relative rounded-2xl border border-line bg-white p-3 shadow-xs">
                        <img src="{{ $qris['src'] }}" alt="QRIS pembayaran sekaligus Rp{{ number_format($total, 0, ',', '.') }}" class="size-56 object-contain">

                        {{-- Loader while the QRIS for the new total is generated --}}
                        <div wire:loading.flex wire:target="selected, selectAll, clearSelection"
                            class="absolute inset-0 flex-col items-center justify-center gap-2 rounded-2xl bg-surface/85 backdrop-blur-sm">
                            <span class="ui-spinner size-7" aria-hidden="true"></span>
                            <span class="text-xs font-medium text-muted">Membuat QRIS…</span>
                        </div>
                    </div>
                    <span @class(['ui-tag', 'ui-tag-success' => $qris['dynamic'], 'ui-tag-warning' => ! $qris['dynamic']])>
                        {{ $qris['dynamic'] ? 'QRIS dinamis: nominal terkunci' : 'QRIS statis: ketik nominal tepat Rp' . number_format($total, 0, ',', '.') }}
                    </span>
                    <a href="{{ $qris['src'] }}" download="qris-kas-kelas-{{ $total }}.png" @unless ($qris['dynamic']) target="_blank" rel="noopener" @endunless
                        class="ui-btn ui-btn-outline w-full">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Unduh gambar QRIS
                    </a>
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-line bg-surface-2 p-4 text-xs leading-relaxed text-muted">QRIS belum diatur. Hubungi bendahara kelas untuk konfirmasi pembayaran.</p>
            @endif
        @else
            <p class="text-xs leading-relaxed text-muted">Centang tagihan di sebelah kiri; total dan QRIS langsung muncul di sini.</p>
        @endif

        @error('ids')<span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>@enderror

        {{-- Always rendered so the FilePond widget survives re-renders; hidden while nothing is selected --}}
        <form method="POST" action="{{ route('payments.bulk.submit', $anchor) }}" enctype="multipart/form-data"
            @class(['flex flex-col gap-3 text-left', 'hidden' => $total === 0])>
            @csrf
            @foreach ($selectedIds as $id)
                <input type="hidden" name="ids[]" value="{{ $id }}" wire:key="ids-{{ $id }}">
            @endforeach
            <div class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                <span>Upload bukti pembayaran</span>
                <div wire:ignore>
                    <input type="file" data-filepond data-max-size="5MB" name="proof" accept="image/png,image/jpeg" required>
                </div>
            </div>
            @error('proof')<span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>@enderror
            <button type="submit" class="ui-btn ui-btn-primary">
                Kirim bukti untuk {{ count($selectedIds) }} tagihan
            </button>
        </form>

        <a href="{{ route('payments.create', $anchor) }}" class="text-xs font-semibold text-brand hover:underline">&larr; Bayar satu tagihan saja</a>
    </aside>
</div>
