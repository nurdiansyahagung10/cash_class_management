<x-layouts.app title="Bayar sekaligus">
    <x-ui.page-header :eyebrow="'Pembayaran · ' . now()->translatedFormat('l, d F Y')" title="Bayar beberapa tagihan sekaligus"
        description="Pilih tagihan, transfer satu kali sesuai total, lalu kirim satu bukti untuk diverifikasi." />

    @if ($isAssisting)
        <section aria-label="Mode bantuan bendahara" class="max-w-4xl rounded-2xl border border-brand/30 bg-brand-soft p-4 text-xs leading-relaxed text-ink shadow-xs">
            Anda membantu <strong>{{ $member->name }}</strong> (No. Absen/NIM <span class="font-mono font-semibold">{{ $member->student_no ?? '-' }}</span>) membayar beberapa tagihan sekaligus.
        </section>
    @endif

    <livewire:bulk-payment :invoice="$anchor" />
</x-layouts.app>
