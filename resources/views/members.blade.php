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
            class="flex min-w-0 flex-[999_1_30rem] flex-col rounded-2xl border border-line bg-white shadow-xs">
            <h2 id="judul-pending" class="border-b border-line px-6 py-5 text-lg font-bold text-black">Calon anggota
            </h2>

            <div class="divide-y divide-black/5">
                @forelse ($pending as $candidate)
                    @php
                        $hoursLeft = (int) floor(now()->diffInHours($candidate->expires_at));
                        $canDecide = $me->isAdmin() && $candidate->created_by !== $me->id;
                    @endphp
                    <div
                        class="flex flex-wrap items-center gap-x-4 gap-y-3 px-6 py-4.5 hover:bg-black/[0.015] transition-colors">
                        <div class="flex min-w-0 flex-1 basis-48 flex-col gap-0.5">
                            <span class="text-[15px] font-semibold text-black">{{ $candidate->name }}</span>
                            <span class="text-xs text-muted">No. absen <span
                                    class="font-mono text-black">{{ $candidate->student_no }}</span> · ditambahkan
                                {{ $candidate->creator->name }}</span>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $hoursLeft < 24 ? 'bg-[#fde4e0] text-[#8f1d0e] border border-[#fecaca]' : 'bg-black/5 text-[#3d3a37]' }}">
                            Sisa
                            {{ intdiv($hoursLeft, 24) > 0 ? intdiv($hoursLeft, 24) . ' hari ' : '' }}{{ $hoursLeft % 24 }}
                            jam
                        </span>
                        @if ($candidate)
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('members.reject', $candidate) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex min-h-11 items-center justify-center rounded-xl border border-black/15 bg-white px-4 text-xs font-semibold text-black transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Tolak</button>
                                </form>
                                <form method="POST" action="{{ route('members.approve', $candidate) }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Verifikasi</button>
                                </form>
                            </div>
                        @else
                            <span
                                class="text-xs font-medium text-muted">{{ $me->isAdmin() ? 'Ditambahkan olehmu, admin lain yang memverifikasi' : 'Menunggu admin' }}</span>
                        @endif
                    </div>
                @empty
                    <p class="px-6 py-12 text-center text-xs text-muted">Tidak ada calon yang menunggu.</p>
                @endforelse
            </div>
        </section>

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
                                <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
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
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
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

                        @if ($me->isAdmin() && $member->id !== $me->id)
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
</x-layouts.app>
