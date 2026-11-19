{{--
    Public dashboard: same layout as the signed-in dashboard, but only class-level aggregates.
    Never shown here: member names, personal invoices, proofs awaiting review, pending sign-ups, expense notes/receipts.
--}}
@php
    $rupiah = fn (int $value) => 'Rp' . number_format($value, 0, ',', '.');
    $ledgerOk = $brokenSeq === null && $anchorsIntact;
    $stats = [
        ['label' => 'Saldo kas', 'value' => $rupiah($balance), 'hint' => 'Dihitung langsung dari ledger', 'icon' => 'wallet'],
        ['label' => 'Iuran terkumpul', 'value' => $rupiah($overall['collected']), 'hint' => 'Dari target ' . $rupiah($overall['target']), 'icon' => 'receipt', 'progress' => $overall['rate']],
        ['label' => 'Tingkat pelunasan', 'value' => $paidRate . '%', 'hint' => $overall['paid_count'] . ' dari ' . $overall['invoice_count'] . ' tagihan lunas', 'icon' => 'chart', 'progress' => $paidRate],
        ['label' => 'Anggota aktif', 'value' => $activeMembers, 'hint' => 'Anggota yang ditagih kas', 'icon' => 'users'],
    ];
    $shortcuts = [
        ['label' => 'Ledger publik', 'href' => route('ledger'), 'icon' => 'shield', 'count' => $totalEntries],
        ['label' => 'Pengeluaran kas', 'href' => route('expenses.index'), 'icon' => 'receipt', 'count' => null],
        ['label' => 'Masuk untuk bayar kas', 'href' => route('login'), 'icon' => 'users', 'count' => null],
    ];
@endphp

<x-layouts.public title="Dashboard publik">
    <x-ui.page-header :eyebrow="'Dashboard · ' . now()->translatedFormat('l, d F Y')" title="Kas Kelas 1A"
        description="Transparansi kas kelas: saldo, perkembangan iuran, dan keutuhan ledger. Nama anggota tidak ditampilkan.">
        <x-slot:actions>
            <a href="{{ route('ledger') }}" class="ui-btn ui-btn-outline">Lihat ledger</a>
            <a href="{{ route('login') }}" class="ui-btn ui-btn-primary">Masuk</a>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- KPI --}}
    <x-ui.stat-rail grid="md:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $stat)
            <x-ui.stat :label="$stat['label']" :value="$stat['value']" :hint="$stat['hint']">
                <x-slot:icon><x-ui.icon :name="$stat['icon']" /></x-slot:icon>
                @isset($stat['progress'])
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-canvas-2" role="progressbar" aria-valuenow="{{ $stat['progress'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $stat['label'] }}">
                        <div class="h-full rounded-full bg-brand" style="width: {{ min(100, $stat['progress']) }}%"></div>
                    </div>
                @endisset
            </x-ui.stat>
        @endforeach
    </x-ui.stat-rail>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-cash-flow-chart :flow="$cashFlow" class="lg:col-span-2" />

        <div class="flex flex-col gap-4">
            {{-- Integritas ledger + anchor harian --}}
            <section aria-label="Status integritas ledger" class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <span @class([
                        'flex size-9 shrink-0 items-center justify-center rounded-lg border border-line bg-surface-2',
                        'text-ink' => $ledgerOk,
                        'text-danger' => ! $ledgerOk,
                    ])><x-ui.icon name="shield" /></span>
                    <div class="flex min-w-0 flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-semibold text-ink">Integritas ledger: {{ $ledgerOk ? 'Utuh' : 'Terputus' }}</h2>
                            <x-ui.badge :tone="$ledgerOk ? 'success' : 'danger'">{{ $ledgerOk ? 'Valid' : 'Audit Error' }}</x-ui.badge>
                        </div>
                        <p class="text-xs leading-relaxed text-muted">
                            @if ($brokenSeq !== null)
                                Entri pertama yang tidak cocok: #{{ $brokenSeq }}.
                            @elseif (! $anchorsIntact)
                                Anchor harian tidak cocok dengan rantai saat ini.
                            @else
                                Rantai hash SHA-256 valid · {{ $totalEntries }} entri.
                            @endif
                        </p>
                    </div>
                </div>
                @if ($latestAnchor)
                    <div class="flex flex-col gap-1.5 rounded-lg border border-line bg-surface-2 px-3 py-2.5">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="ui-eyebrow">Anchor harian · {{ $latestAnchor->anchored_on->translatedFormat('d M Y') }} · entri #{{ $latestAnchor->seq }}</span>
                            <span class="shrink-0 font-mono text-sm font-semibold text-ink">{{ substr($latestAnchor->hash, 0, 4) }}…{{ substr($latestAnchor->hash, -4) }}</span>
                        </div>
                        <span class="break-all font-mono text-[11px] leading-relaxed text-body">{{ $latestAnchor->hash }}</span>
                        <p class="text-[11px] leading-relaxed text-muted">Simpan atau tangkap layar anchor ini, lalu bandingkan nanti dengan hash entri #{{ $latestAnchor->seq }} di halaman Ledger.</p>
                    </div>
                @endif
            </section>

            <section aria-labelledby="pintasan-publik" class="ui-card flex flex-col">
                <h2 id="pintasan-publik" class="border-b border-line px-4 py-3 text-sm font-semibold text-ink sm:px-5">Pintasan</h2>
                <ul class="flex flex-col p-1.5">
                    @foreach ($shortcuts as $shortcut)
                        <li>
                            <a href="{{ $shortcut['href'] }}" class="group flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-[13px] text-ink transition-colors hover:bg-canvas-2">
                                <span class="text-muted"><x-ui.icon :name="$shortcut['icon']" /></span>
                                <span class="min-w-0 flex-1 truncate">{{ $shortcut['label'] }}</span>
                                @if ($shortcut['count'] !== null)
                                    <x-ui.badge class="font-mono tabular-nums">{{ $shortcut['count'] }}</x-ui.badge>
                                @endif
                                <span class="text-line-strong transition-transform group-hover:translate-x-0.5"><x-ui.icon name="arrow" /></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-5">
        {{-- Periode tagihan: totals only --}}
        <section aria-labelledby="periode-publik" class="ui-card flex flex-col lg:col-span-3">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                <div class="min-w-0">
                    <h2 id="periode-publik" class="text-sm font-semibold text-ink">Per periode tagihan</h2>
                    <p class="text-xs text-muted">{{ $overall['period_count'] }} periode · hanya angka total, tanpa nama anggota.</p>
                </div>
                <div class="hidden shrink-0 items-center gap-1.5 sm:flex">
                    <x-ui.badge tone="success">{{ $overall['paid_count'] }} lunas</x-ui.badge>
                    <x-ui.badge tone="warning">{{ $overall['partial_count'] }} menyicil</x-ui.badge>
                    <x-ui.badge tone="danger">{{ $overall['unpaid_count'] }} belum</x-ui.badge>
                </div>
            </div>
            <ul class="divide-y divide-line">
                @forelse ($periodSummaries as $period)
                    @php($done = $period['total_count'] > 0 && $period['paid_count'] === $period['total_count'])
                    <li class="flex flex-col gap-2.5 px-4 py-3.5 sm:px-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-ink">{{ $period['title'] }}</p>
                                <p class="text-xs text-muted">
                                    {{ $period['category'] ?? 'Uang Kas' }} · Jatuh tempo {{ $period['due_date']->translatedFormat('d M Y') }}
                                </p>
                            </div>
                            <x-ui.badge :tone="$done ? 'success' : ($period['is_overdue'] ? 'danger' : ($period['is_incoming'] ? 'neutral' : 'warning'))">
                                {{ $done ? 'Selesai' : ($period['is_overdue'] ? 'Lewat tenggat' : ($period['is_incoming'] ? 'Akan datang' : 'Berjalan')) }}
                            </x-ui.badge>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-canvas-2" role="progressbar" aria-valuenow="{{ $period['rate'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Terkumpul {{ $period['title'] }}">
                                <div class="h-full rounded-full {{ $done ? 'bg-success' : 'bg-brand' }}" style="width: {{ min(100, $period['rate']) }}%"></div>
                            </div>
                            <span class="w-9 text-right font-mono text-[11px] tabular-nums text-ink">{{ $period['rate'] }}%</span>
                        </div>
                        <p class="flex flex-wrap justify-between gap-x-3 text-xs tabular-nums text-muted">
                            <span>{{ $rupiah($period['collected']) }} / {{ $rupiah($period['target']) }}</span>
                            <span>{{ $period['paid_count'] }}/{{ $period['total_count'] }} lunas</span>
                        </p>
                    </li>
                @empty
                    <li class="px-6 py-10 text-center text-xs text-muted">Belum ada periode tagihan.</li>
                @endforelse
            </ul>
        </section>

        {{-- Pengeluaran terbaru: same fields as the public expenses page --}}
        <section aria-labelledby="pengeluaran-publik" class="ui-card flex flex-col lg:col-span-2">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                <h2 id="pengeluaran-publik" class="text-sm font-semibold text-ink">Pengeluaran terbaru</h2>
                <a href="{{ route('expenses.index') }}" class="ui-btn ui-btn-ghost min-h-8 px-2.5 text-xs">Semua &rarr;</a>
            </div>
            <ul class="divide-y divide-line">
                @forelse ($latestExpenses as $expense)
                    <li class="flex items-center gap-3 px-4 py-3 sm:px-5">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-surface-2 text-muted ring-1 ring-line" aria-hidden="true"><x-ui.icon name="receipt" class="size-3.5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $expense->title }}</p>
                            <p class="text-xs text-muted">{{ $expense->spent_at->translatedFormat('d M Y') }}</p>
                        </div>
                        <span class="shrink-0 text-[13px] font-medium tabular-nums text-danger">-{{ $rupiah($expense->amount) }}</span>
                    </li>
                @empty
                    <li class="px-6 py-10 text-center text-xs text-muted">Belum ada pengeluaran yang dicatat.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <livewire:cash-calendar />
</x-layouts.public>
