@php
    $me = auth()->user();
@endphp
<x-layouts.app title="Verifikasi anggota">
    <header class="flex flex-col gap-2">
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-black">Verifikasi anggota</h1>
        <p class="max-w-2xl font-serif text-base sm:text-lg leading-relaxed text-muted">Staff menambahkan, admin
            menyetujui. Calon yang tidak diverifikasi dalam 3 hari dihapus otomatis dan tidak pernah masuk ledger.</p>
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
                        @if ($canDecide)
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

        <section aria-labelledby="judul-tambah"
            class="flex min-w-72 flex-[1_1_18rem] flex-col gap-5 rounded-2xl border border-line bg-white p-6 shadow-xs">
            <div class="flex flex-col gap-1">
                <h2 id="judul-tambah" class="text-lg font-bold text-black">Tambah calon anggota</h2>
                <span class="text-xs text-muted">Masuk antrean verifikasi admin.</span>
            </div>

            <form method="POST" action="{{ route('members.store') }}" class="flex flex-col gap-4">
                @csrf
                @foreach ([['name', 'Nama lengkap', 'text', 'Nama sesuai absen'], ['student_no', 'No. absen / NIM', 'text', 'Harus unik'], ['password', 'Password awal (min. 8 karakter)', 'text', 'Diberikan ke anggota']] as [$field, $label, $type, $placeholder])
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                        <span>{{ $label }}</span>
                        <input type="{{ $type }}" name="{{ $field }}" value="{{ $field === 'password' ? '' : old($field) }}"
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

            <p class="text-xs leading-relaxed text-muted">Siapa pun yang menambahkan calon tidak bisa memverifikasinya
                sendiri.</p>
        </section>
    </div>

    <section aria-labelledby="judul-semua-anggota"
        class="flex flex-col rounded-2xl border border-line bg-white shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-6 py-4.5">
            <h2 id="judul-semua-anggota" class="text-lg font-bold text-black">Daftar anggota kelas
                ({{ $users->count() }})</h2>

            <form method="GET" action="{{ route('members.index') }}" class="flex items-center gap-2">
                <input type="search" name="q" value="{{ $search ?? '' }}" placeholder="Cari nama atau No. absen..."
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
                    <div class="flex items-center gap-3.5">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-soft font-mono text-xs font-bold text-brand border border-brand/20"
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
                            <span class="rounded-full bg-black/5 px-2.5 py-0.5 text-xs font-semibold text-muted">Nonaktif</span>
                        @endif

                        @if ($me->isAdmin() && $member->id !== $me->id)


                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('members.toggle-staff', $member) }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                        {{ $member->role->name == 'Staff' ? 'Nonaktifkan Staff' : 'Aktifkan Staff' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('members.toggle-status', $member) }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex min-h-11 items-center justify-center rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-black shadow-xs transition-colors hover:bg-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
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
