@php
    $me = auth()->user();
@endphp
<x-layouts.app title="Verifikasi anggota">
    <header class="flex flex-col gap-2">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-xs font-semibold text-muted">
                <span class="hover:text-black transition-colors">Kas Kelas 1A</span>
                <span class="text-black/20">/</span>
                <span class="text-black">Anggota</span>
                <span class="text-black/20">·</span>
                <span class="font-normal">{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </header>

    <section aria-label="Ringkasan" class="grid gap-4 sm:grid-cols-3">
        <div class="flex flex-col gap-1 rounded-2xl border border-line bg-white p-5 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-muted">Menunggu verifikasi</span>
            <span
                class="text-2xl sm:text-3xl font-bold tracking-tight text-black tabular-nums my-1">{{ $pending->count() }}</span>
            <span class="text-xs text-muted">Pendaftar calon anggota</span>
        </div>
        <div class="flex flex-col gap-1 rounded-2xl border border-line bg-white p-5 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-muted">Anggota aktif</span>
            <span
                class="text-2xl sm:text-3xl font-bold tracking-tight text-[#14532d] tabular-nums my-1">{{ $activeMembers }}</span>
            <span class="text-xs text-muted">Dapat ditagih kas</span>
        </div>
        <div class="flex flex-col gap-1 rounded-2xl border border-line bg-white p-5 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-muted">Anggota nonaktif</span>
            <span
                class="text-2xl sm:text-3xl font-bold tracking-tight text-muted tabular-nums my-1">{{ $inactiveMembers }}</span>
            <span class="text-xs text-muted">Akun dinonaktifkan</span>
        </div>
    </section>

    @if (session('import_errors'))
        <div
            class="flex flex-col gap-2 rounded-2xl border border-amber/30 bg-[#fffbeb] p-5 text-xs text-[#7a4a00] shadow-xs">
            <div class="flex items-center gap-2 font-bold text-sm text-[#7a4a00]">
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <span>Catatan baris yang dilewati / gagal saat impor:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 pl-1">
                @foreach (session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap items-start gap-6">

        <section aria-labelledby="judul-pending"
            class="relative flex min-w-0 flex-[999_1_30rem] flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-xs">

            <div class="flex items-center justify-between gap-3 border-b border-line px-6 py-5">
                <div>
                    <h2 id="judul-pending" class="text-lg font-bold text-black">
                        Calon anggota
                    </h2>
                    <p class="mt-1 text-xs text-muted">
                        Verifikasi calon anggota yang menunggu persetujuan.
                    </p>
                </div>

                <span class="rounded-full bg-black/5 px-3 py-1 text-xs font-semibold text-black">
                    {{ $pending->total() }} menunggu
                </span>
            </div>

            {{-- Header --}}
            <div
                class="grid grid-cols-[36px_minmax(0,1fr)_auto_auto] items-center gap-4 border-b border-line bg-[#fafafa] px-6 py-3 text-[11px] font-semibold uppercase tracking-wider text-muted">
                <div>
                    @if ($me->isAdmin())
                        <input type="checkbox" data-pending-select-all
                            aria-label="Pilih semua calon anggota di halaman ini"
                            class="size-4 rounded border-gray-300 text-brand focus:ring-brand">
                    @endif
                </div>
                <span>Informasi anggota</span>
                <span class="hidden sm:block">Masa berlaku</span>
                <span>Aksi</span>
            </div>

            {{-- Data --}}
            <div class="divide-y divide-black/5">
                @forelse ($pending as $candidate)
                    @php
                        $hoursLeft = (int) floor(now()->diffInHours($candidate->expires_at));
                        $canDecide = $me->isAdmin() && $candidate->created_by !== $me->id;
                    @endphp

                    <div
                        class="grid grid-cols-[36px_minmax(0,1fr)_auto_auto] items-center gap-4 px-6 py-4 transition-colors hover:bg-black/[0.015]">

                        {{-- Checkbox --}}
                        <div>
                            @if ($canDecide)
                                <input type="checkbox" data-pending-checkbox value="{{ $candidate->id }}"
                                    aria-label="Pilih {{ $candidate->name }}"
                                    class="size-4 rounded border-gray-300 text-brand focus:ring-brand">
                            @endif
                        </div>

                        {{-- Informasi --}}
                        <div class="flex min-w-0 flex-col gap-1">
                            <span class="truncate text-sm font-semibold text-black">
                                {{ $candidate->name }}
                            </span>

                            <span class="text-xs text-muted">
                                No. absen
                                <span class="font-mono text-black">{{ $candidate->student_no }}</span>
                            </span>

                            <span class="truncate text-[11px] text-muted">
                                Ditambahkan oleh {{ $candidate->creator->name }}
                            </span>
                        </div>

                        {{-- Masa berlaku --}}
                        <div class="hidden sm:block">
                            <span
                                class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold
                        {{ $hoursLeft < 24 ? 'border border-[#fecaca] bg-[#fde4e0] text-[#8f1d0e]' : 'bg-black/5 text-[#3d3a37]' }}">
                                {{ intdiv($hoursLeft, 24) > 0 ? intdiv($hoursLeft, 24) . ' hari ' : '' }}{{ $hoursLeft % 24 }}
                                jam
                            </span>
                        </div>

                        {{-- Individual action --}}
                        <div>
                            @if ($canDecide)
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('members.reject', $candidate) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="rounded-lg border border-black/15 bg-white px-3 py-2 text-xs font-semibold text-black transition hover:bg-red-50 hover:text-red-700">
                                            Tolak
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('members.approve', $candidate) }}">
                                        @csrf
                                        <button type="submit"
                                            class="rounded-lg bg-brand px-3 py-2 text-xs font-semibold text-white transition hover:bg-brand-dark">
                                            Verifikasi
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-muted">
                                    {{ $me->isAdmin() ? 'Menunggu admin lain' : 'Menunggu admin' }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-14 text-center">
                        <div class="mx-auto mb-3 flex size-11 items-center justify-center rounded-full bg-black/5">
                            <svg class="size-5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7">
                                <path d="M9 12l2 2 4-4M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-black">Tidak ada calon anggota</p>
                        <p class="mt-1 text-xs text-muted">Semua pengajuan sudah diproses.</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-line px-6 py-4">
                <div class="text-xs text-muted">
                    Menampilkan
                    <span
                        class="font-semibold text-black">{{ $pending->firstItem() ?? 0 }}–{{ $pending->lastItem() ?? 0 }}</span>
                    dari
                    <span class="font-semibold text-black">{{ $pending->total() }}</span>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold tabular-nums text-black">
                        {{ $pending->currentPage() }} / {{ max(1, $pending->lastPage()) }}
                    </span>

                    <a href="{{ $pending->previousPageUrl() ?? '#' }}" @class([
                        'flex size-9 items-center justify-center rounded-lg border border-line transition',
                        'pointer-events-none opacity-30' => !$pending->previousPageUrl(),
                        'hover:bg-black/5' => $pending->previousPageUrl(),
                    ])
                        aria-label="Halaman sebelumnya">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                    </a>

                    <a href="{{ $pending->nextPageUrl() ?? '#' }}" @class([
                        'flex size-9 items-center justify-center rounded-lg border border-line transition',
                        'pointer-events-none opacity-30' => !$pending->nextPageUrl(),
                        'hover:bg-black/5' => $pending->nextPageUrl(),
                    ])
                        aria-label="Halaman berikutnya">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        {{-- Bulk action form --}}
        @if ($me->isAdmin())
            <form id="bulk-pending-form" method="POST" action="{{ route('members.bulk-action') }}">
                @csrf
                <div id="pending-selected-inputs"></div>
            </form>

            {{-- Floating action --}}
            <div id="pending-floating-action"
                class="fixed bottom-6 left-1/2 z-50 hidden w-[calc(100%-2rem)] max-w-xl -translate-x-1/2">

                <div
                    class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-[#20231f] p-3 text-white shadow-2xl">

                    <div class="flex min-w-0 items-center gap-3 pl-2">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/10">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7" />
                            </svg>
                        </span>

                        <div class="min-w-0">
                            <p class="text-sm font-semibold">
                                <span id="pending-selected-count">0</span> dipilih
                            </p>
                            <p class="text-[10px] text-white/60">Aksi untuk anggota terpilih</p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <button type="submit" form="bulk-pending-form" name="action" value="reject"
                            class="rounded-xl border border-white/20 px-3 py-2 text-xs font-semibold transition hover:bg-white/10">
                            Tolak
                        </button>

                        <button type="submit" form="bulk-pending-form" name="action" value="approve"
                            class="rounded-xl bg-[#D0FA86] px-3 py-2 text-xs font-bold text-[#00362D] transition hover:bg-[#bde86c]">
                            Approve
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex min-w-72 flex-[1_1_20rem] flex-col gap-6">
            <section aria-labelledby="judul-tambah"
                class="flex flex-col gap-5 rounded-2xl border border-line bg-white p-6 shadow-xs">
                <div class="flex flex-col gap-1">
                    <h2 id="judul-tambah" class="text-lg font-bold text-black">Tambah calon anggota</h2>
                    <span class="text-xs text-muted">Masuk antrean verifikasi admin.</span>
                </div>

                <form method="POST" action="{{ route('members.store') }}" class="flex flex-col gap-4">
                    @csrf
                    @foreach ([['name', 'Nama lengkap', 'text', 'Nama sesuai absen'], ['student_no', 'No. absen / NIM', 'text', 'Harus unik'], ['password', 'Password awal (min. 8 karakter)', 'text', 'Diberikan ke anggota']] as [$field, $label, $type, $placeholder])
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                            <span>{{ $label }}</span>
                            <input type="{{ $type }}" name="{{ $field }}"
                                value="{{ $field === 'password' ? '' : old($field) }}"
                                placeholder="{{ $placeholder }}" required autocomplete="off"
                                class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                            @error($field)
                                <span role="alert"
                                    class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                            @enderror
                        </label>
                    @endforeach
                    <button type="submit"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Kirim
                        ke antrean</button>
                </form>

                <p class="text-xs leading-relaxed text-muted">Siapa pun yang menambahkan calon tidak bisa
                    memverifikasinya
                    sendiri.</p>
            </section>

            <section aria-labelledby="judul-import"
                class="flex flex-col gap-5 rounded-2xl border border-line bg-white p-6 shadow-xs">
                <div class="flex flex-col gap-1">
                    <h2 id="judul-import" class="text-lg font-bold text-black">Impor dari Excel / Sheet</h2>
                    <span class="text-xs text-muted">Unggah daftar calon anggota sekaligus.</span>
                </div>

                <div class="flex items-center justify-between rounded-xl bg-canvas/60 border border-line p-3">
                    <div class="flex flex-col">
                        <span class="text-xs font-semibold text-black">Format Kolom</span>
                        <span class="text-[11px] text-muted">Nama, NIM, Password Awal</span>
                    </div>
                    <a href="{{ route('members.template') }}"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-black/10 bg-white px-2.5 py-1.5 text-xs font-semibold text-brand shadow-2xs hover:bg-canvas transition-colors">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Unduh Template
                    </a>
                </div>

                <form method="POST" action="{{ route('members.import') }}" enctype="multipart/form-data"
                    class="flex flex-col gap-4">
                    @csrf
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                        <span>Pilih file (.xlsx, .xls, .csv)</span>
                        <input type="file" name="file" accept=".xlsx,.xlsm,.xls,.csv" required
                            class="block w-full text-xs text-muted file:mr-3 file:min-h-10 file:rounded-xl file:border file:border-line file:bg-canvas file:px-3 file:py-2 file:text-xs file:font-semibold file:text-black hover:file:bg-black/5 file:cursor-pointer cursor-pointer focus:outline-none">
                        @error('file')
                            <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                        @enderror
                    </label>

                    <button type="submit"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-navy px-4 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-black focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy">
                        Unggah & Impor
                    </button>
                </form>

                <p class="text-xs leading-relaxed text-muted">Baris dengan NIM yang sudah terdaftar akan otomatis
                    dilewati dan dilaporkan.</p>
            </section>
        </div>
    </div>

    <section aria-labelledby="judul-semua-anggota"
        class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-6 py-4.5">
            <h2 id="judul-semua-anggota" class="text-lg font-bold text-black">Daftar anggota kelas
                ({{ $users->count() }})</h2>

            <form method="GET" action="{{ route('members.index') }}" class="flex items-center gap-2">
                <input type="search" name="q" value="{{ $search ?? '' }}"
                    placeholder="Cari nama atau No. absen..."
                    class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-xs placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                <button type="submit"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Cari</button>
                @if (!empty($search))
                    <a href="{{ route('members.index') }}"
                        class="min-h-11 inline-flex items-center rounded-xl px-3 text-xs font-semibold text-muted hover:text-black transition-colors">Reset</a>
                @endif
            </form>
        </div>

        <div class="divide-y divide-black/5">
            @forelse ($users as $member)
                <div
                    class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 hover:bg-black/[0.015] transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="inline-flex min-w-16 shrink-0 items-center justify-center rounded-lg  px-2.5 py-1 font-mono text-xs font-bold text-muted tabular-nums"
                            aria-hidden="true">
                            {{ $member->student_no ?? '—' }}
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-sm font-semibold text-black {{ !$member->is_active ? 'line-through text-muted' : '' }}">{{ $member->name }}</span>
                            <span class="text-xs text-muted capitalize">{{ $member->role->value }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        @if ($member->is_active)
                            <span
                                class="rounded-full bg-[#dcfce7] px-2.5 py-0.5 text-xs font-semibold text-[#14532d] border border-[#bbf7d0]">Aktif</span>
                        @else
                            <span
                                class="rounded-full bg-black/5 px-2.5 py-0.5 text-xs font-semibold text-muted">Nonaktif</span>
                        @endif

                        @if ($me->isAdmin() && !$member->isAdmin() && $member->id !== $me->id)
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('members.toggle-staff', $member) }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex cursor-pointer min-h-11 items-center justify-center rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                        {{ $member->role->name == 'Staff' ? 'Nonaktifkan Staff' : 'Aktifkan Staff' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('members.toggle-status', $member) }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex cursor-pointer min-h-11 items-center justify-center rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                        {{ $member->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-6 py-12 text-center text-xs text-muted">
                    {{ !empty($search) ? 'Tidak ditemukan anggota yang sesuai pencarian.' : 'Belum ada anggota terdaftar.' }}
                </p>
            @endforelse
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.querySelector('[data-pending-select-all]');
            const checkboxes = [...document.querySelectorAll('[data-pending-checkbox]')];

            const floatingAction = document.getElementById('pending-floating-action');
            const selectedCount = document.getElementById('pending-selected-count');
            const selectedInputs = document.getElementById('pending-selected-inputs');
            const bulkForm = document.getElementById('bulk-pending-form');

            if (!floatingAction || !bulkForm) return;

            function updateSelection() {
                const selected = checkboxes.filter(item => item.checked);
                const count = selected.length;

                floatingAction.classList.toggle('hidden', count === 0);
                selectedCount.textContent = count;

                if (selectAll) {
                    selectAll.checked = count === checkboxes.length && count > 0;
                    selectAll.indeterminate = count > 0 && count < checkboxes.length;
                }

                // Sync selected IDs to bulk form
                selectedInputs.replaceChildren();

                selected.forEach(item => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'candidate_ids[]';
                    input.value = item.value;

                    selectedInputs.appendChild(input);
                });
            }

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelection);
            });

            selectAll?.addEventListener('change', () => {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });

                updateSelection();
            });

            bulkForm.addEventListener('submit', event => {
                const count = checkboxes.filter(item => item.checked).length;
                const action = event.submitter?.value;

                if (count === 0) {
                    event.preventDefault();
                    return;
                }

                const message = action === 'approve' ?
                    `Approve ${count} calon anggota terpilih?` :
                    `Tolak ${count} calon anggota terpilih?`;

                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });

            updateSelection();
        });
    </script>
</x-layouts.app>
