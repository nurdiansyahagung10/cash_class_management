@php
    $rupiah = fn (int $value) => 'Rp' . number_format($value, 0, ',', '.');

    if ($user->isAdmin()) {
        $stats = [
            ['label' => 'Saldo kas', 'value' => $rupiah($balance), 'hint' => 'Dihitung langsung dari ledger', 'icon' => 'wallet'],
            ['label' => 'Tagihan belum lunas', 'value' => $rupiah($unpaidTotal), 'hint' => 'Total sisa seluruh anggota', 'icon' => 'receipt'],
            ['label' => 'Tingkat pelunasan', 'value' => $paidRate . '%', 'hint' => 'Invoice yang sudah lunas', 'icon' => 'chart', 'progress' => $paidRate],
            ['label' => 'Anggota aktif', 'value' => $activeMembers, 'hint' => 'Anggota yang dapat ditagih', 'icon' => 'users'],
        ];
        $actions = [
            ['label' => 'Bukti menunggu review', 'count' => $pendingPayments, 'href' => route('payments.review'), 'icon' => 'clock'],
            ['label' => 'Menunggu verifikasi anggota', 'count' => $pendingMembers, 'href' => route('members.index'), 'icon' => 'users'],
            ['label' => 'Periode tagihan', 'count' => $periodCount, 'href' => route('periods.index'), 'icon' => 'calendar'],
        ];
    } elseif ($user->canManageMembers()) {
        $stats = [
            ['label' => 'Saldo kas', 'value' => $rupiah($balance), 'hint' => 'Sumber data: ledger', 'icon' => 'wallet'],
            ['label' => 'Anggota aktif', 'value' => $activeMembers, 'hint' => 'Terdaftar dan aktif', 'icon' => 'users'],
            ['label' => 'Bukti menunggu review', 'value' => $pendingPayments, 'hint' => 'Pembayaran siap diperiksa', 'icon' => 'clock'],
            ['label' => 'Menunggu verifikasi anggota', 'value' => $pendingMembers, 'hint' => 'Perlu keputusan admin', 'icon' => 'check'],
        ];
        $actions = [
            ['label' => 'Laporan & bantu bayar siswa', 'count' => null, 'href' => route('reports.payments'), 'icon' => 'chart'],
            ['label' => 'Antrean anggota', 'count' => $pendingMembers, 'href' => route('members.index'), 'icon' => 'users'],
            ['label' => 'Tagihan kelas', 'count' => null, 'href' => route('periods.index'), 'icon' => 'calendar'],
            ['label' => 'Kategori tagihan', 'count' => null, 'href' => route('categories.index'), 'icon' => 'receipt'],
        ];
    } else {
        $stats = [
            ['label' => 'Tagihan belum lunas', 'value' => $rupiah($unpaidTotal), 'hint' => 'Jumlah yang perlu dibayar', 'icon' => 'receipt', 'tone' => $unpaidTotal > 0 ? 'danger' : 'default'],
            ['label' => 'Tagihan lunas', 'value' => $paidInvoices, 'hint' => 'Invoice yang sudah dibayar', 'icon' => 'check'],
            ['label' => 'Bukti menunggu review', 'value' => $pendingPayments, 'hint' => 'Bukti pembayaran yang dikirim', 'icon' => 'clock'],
            ['label' => 'Saldo kas kelas', 'value' => $rupiah($balance), 'hint' => 'Transparan, dihitung dari ledger', 'icon' => 'wallet'],
        ];
        $actions = [];
    }

    $paymentStatus = fn ($payment) => match (true) {
        $payment->status === 'paid' => ['Lunas', 'success'],
        $payment->status === 'reversed' => ['Dibatalkan', 'danger'],
        (bool) $payment->proof_path => ['Menunggu verifikasi', 'warning'],
        default => ['Belum dibayar', 'neutral'],
    };
@endphp

<x-layouts.app title="Dashboard">
    <x-ui.page-header :eyebrow="'Dashboard · ' . now()->translatedFormat('l, d F Y')" :title="'Halo, ' . $user->name"
        :description="$user->isAdmin()
            ? 'Ringkasan kas, tagihan, dan antrean administrasi kelas.'
            : ($user->canManageMembers()
                ? 'Ringkasan verifikasi anggota dan pembayaran yang perlu ditinjau.'
                : 'Pantau tagihan dan riwayat pembayaran kas kelasmu.')">
        <x-slot:actions>
            <a href="{{ route('ledger') }}" class="ui-btn ui-btn-outline">Lihat ledger</a>
            @if ($user->canManageMembers())
                <a href="{{ route('payments.review') }}" class="ui-btn ui-btn-primary">
                    Review pembayaran
                    <span class="rounded-full bg-white/15 px-1.5 font-mono text-[11px] tabular-nums">{{ $pendingPayments }}</span>
                </a>
            @elseif ($unpaidInvoices->count() > 1)
                <a href="{{ route('payments.bulk', $unpaidInvoices->first()) }}" class="ui-btn ui-btn-primary">Bayar {{ $unpaidInvoices->count() }} tagihan sekaligus</a>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- KPI --}}
    <x-ui.stat-rail grid="md:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $stat)
            <x-ui.stat :label="$stat['label']" :value="$stat['value']" :hint="$stat['hint']" :tone="$stat['tone'] ?? 'default'">
                <x-slot:icon><x-ui.icon :name="$stat['icon']" /></x-slot:icon>
                @isset($stat['progress'])
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-canvas-2" role="progressbar" aria-valuenow="{{ $stat['progress'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $stat['label'] }}">
                        <div class="h-full rounded-full bg-brand" style="width: {{ $stat['progress'] }}%"></div>
                    </div>
                @endisset
            </x-ui.stat>
        @endforeach
    </x-ui.stat-rail>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-cash-flow-chart :flow="$cashFlow" class="lg:col-span-2" />

        <div class="flex flex-col gap-4">
            {{-- Integritas ledger --}}
            <section aria-label="Status integritas ledger" class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <span @class([
                        'flex size-9 shrink-0 items-center justify-center rounded-lg border',
                        'border-line bg-canvas-2 text-ink' => $brokenSeq === null,
                        'border-line bg-surface-2 text-danger' => $brokenSeq !== null,
                    ])><x-ui.icon name="shield" /></span>
                    <div class="flex min-w-0 flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-semibold text-ink">Integritas ledger: {{ $brokenSeq === null ? 'Utuh' : 'Terputus' }}</h2>
                            <x-ui.badge :tone="$brokenSeq === null ? 'success' : 'danger'" dot>{{ $brokenSeq === null ? 'Valid' : 'Audit Error' }}</x-ui.badge>
                        </div>
                        <p class="text-xs leading-relaxed text-muted">
                            {{ $brokenSeq === null ? 'Rantai hash SHA-256 tervalidasi.' : 'Entri pertama yang tidak cocok: #' . $brokenSeq . '.' }}
                            Setiap entri terkunci berurutan sehingga tidak bisa diubah sepihak.
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('ledger.verify') }}">
                    @csrf
                    <button type="submit" class="ui-btn ui-btn-outline w-full">Periksa ledger</button>
                </form>
            </section>

            @if ($actions !== [])
                <section aria-labelledby="perlu-tindakan" class="ui-card flex flex-col">
                    <h2 id="perlu-tindakan" class="border-b border-line px-4 py-3 text-sm font-semibold text-ink sm:px-5">
                        {{ $user->isAdmin() ? 'Perlu tindakan' : 'Pintasan' }}
                    </h2>
                    <ul class="flex flex-col p-1.5">
                        @foreach ($actions as $action)
                            <li>
                                <a href="{{ $action['href'] }}" class="group flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-[13px] text-ink transition-colors hover:bg-canvas-2">
                                    <span class="text-muted"><x-ui.icon :name="$action['icon']" /></span>
                                    <span class="min-w-0 flex-1 truncate">{{ $action['label'] }}</span>
                                    @if ($action['count'] !== null)
                                        <x-ui.badge :tone="$action['count'] > 0 && $action['icon'] !== 'calendar' ? 'ink' : 'neutral'" class="font-mono tabular-nums">{{ $action['count'] }}</x-ui.badge>
                                    @endif
                                    <span class="text-line-strong transition-transform group-hover:translate-x-0.5"><x-ui.icon name="arrow" /></span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

    {{-- Konten per peran --}}
    @if ($user->canManageMembers())
        <div class="grid gap-4 lg:grid-cols-5">
            @if ($user->isAdmin())
                <section aria-labelledby="periode-terbaru" class="ui-card flex flex-col lg:col-span-3">
                    <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                        <div class="min-w-0">
                            <h2 id="periode-terbaru" class="text-sm font-semibold text-ink">Periode terbaru</h2>
                            <p class="text-xs text-muted">Invoice yang sudah lunas per tagihan.</p>
                        </div>
                        <a href="{{ route('periods.index') }}" class="ui-btn ui-btn-ghost min-h-9 px-2.5 text-xs">Kelola &rarr;</a>
                    </div>
                    <ul class="divide-y divide-line">
                        @forelse ($latestPeriods as $period)
                            @php($pct = $period->invoices_count > 0 ? (int) round($period->paid_count * 100 / $period->invoices_count) : 0)
                            <li class="flex flex-col gap-2.5 px-4 py-3.5 sm:px-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-ink">{{ $period->title }}</p>
                                        <p class="text-xs text-muted">Jatuh tempo {{ $period->due_date->format('d M Y') }} · {{ $rupiah($period->amount) }}</p>
                                    </div>
                                    <span class="shrink-0 font-mono text-xs tabular-nums text-muted">{{ $period->paid_count }}/{{ $period->invoices_count }} lunas</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-canvas-2" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Lunas {{ $pct }}%">
                                        <div class="h-full rounded-full {{ $pct === 100 ? 'bg-success' : 'bg-brand' }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="w-9 text-right font-mono text-[11px] tabular-nums text-ink">{{ $pct }}%</span>
                                </div>
                            </li>
                        @empty
                            <li class="px-6 py-10 text-center text-xs text-muted">Belum ada periode tagihan.</li>
                        @endforelse
                    </ul>
                </section>
            @else
                {{-- Tagihan kas pribadi staff (bendahara) --}}
                <section aria-labelledby="tagihan-kas-staff" class="ui-card flex flex-col lg:col-span-3">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                        <div class="min-w-0">
                            <h2 id="tagihan-kas-staff" class="text-sm font-semibold text-ink">Tagihan kas pribadimu</h2>
                            <p class="text-xs text-muted">Sebagai pengurus kelas, kamu juga membayar iuran kas.</p>
                        </div>
                        @if ($unpaidInvoices->count() > 1)
                            <a href="{{ route('payments.bulk', $unpaidInvoices->first()) }}" class="ui-btn ui-btn-outline min-h-9 text-xs">Bayar {{ $unpaidInvoices->count() }} sekaligus</a>
                        @endif
                    </div>
                    @include('dashboard._invoices', ['invoices' => $unpaidInvoices, 'payLabel' => 'Bayar Kas'])
                </section>
            @endif

            <section aria-labelledby="antrean-review" class="ui-card flex flex-col lg:col-span-2">
                <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                    <h2 id="antrean-review" class="text-sm font-semibold text-ink">Bukti terbaru</h2>
                    <a href="{{ route('payments.review') }}" class="ui-btn ui-btn-ghost min-h-9 px-2.5 text-xs">Semua &rarr;</a>
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($reviewQueue as $payment)
                        <li class="flex items-center gap-3 px-4 py-3 sm:px-5">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-canvas-2 font-mono text-[10px] text-body" aria-hidden="true">
                                {{ \Illuminate\Support\Str::of($payment->invoice?->user?->name ?? '?')->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-medium text-ink">{{ $payment->invoice?->user?->name ?? 'Anggota' }}</p>
                                <p class="truncate text-xs text-muted">{{ $payment->invoice?->period?->title }} · {{ $payment->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="shrink-0 text-[13px] font-medium tabular-nums text-ink">{{ $rupiah($payment->amount) }}</span>
                        </li>
                    @empty
                        <li class="px-6 py-10 text-center text-xs text-muted">Tidak ada bukti yang menunggu review.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-2">
            <section aria-labelledby="tagihan-belum-lunas" class="ui-card flex flex-col">
                <div class="border-b border-line px-4 py-3 sm:px-5">
                    <h2 id="tagihan-belum-lunas" class="text-sm font-semibold text-ink">Tagihan belum lunas</h2>
                    <p class="text-xs text-muted">Pilih tagihan untuk membuka QRIS dan mengirim bukti transfer.</p>
                </div>
                @include('dashboard._invoices', ['invoices' => $unpaidInvoices, 'payLabel' => 'Bayar'])
            </section>

            <section aria-labelledby="riwayat-pembayaran" class="ui-card flex flex-col">
                <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                    <div>
                        <h2 id="riwayat-pembayaran" class="text-sm font-semibold text-ink">Riwayat pembayaran</h2>
                        <p class="text-xs text-muted">Lima transaksi terakhirmu.</p>
                    </div>
                    <a href="{{ route('payments.history') }}" class="ui-btn ui-btn-ghost min-h-9 px-2.5 text-xs">Semua &rarr;</a>
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($latestPayments as $payment)
                        @php([$statusLabel, $statusTone] = $paymentStatus($payment))
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-medium text-ink">{{ $payment->invoice->period->title }}</p>
                                <p class="text-xs text-muted">
                                    {{ $payment->created_at->format('d M Y') }}
                                    @if ($payment->category)
                                        · {{ $payment->category->name }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <span class="text-[13px] font-medium tabular-nums text-ink">{{ $rupiah($payment->amount) }}</span>
                                <x-ui.badge :tone="$statusTone">{{ $statusLabel }}</x-ui.badge>
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-10 text-center text-xs text-muted">Belum ada riwayat pembayaran.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    @endif

    <livewire:cash-calendar />
</x-layouts.app>
