<x-layouts.guest title="Ganti password" lead="Password awal dari staff hanya untuk login pertama. Buat password barumu sendiri.">
    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
        @csrf
        @method('PUT')

        @foreach ([['current_password', 'Password saat ini', 'current-password', 'Masukkan password saat ini'], ['password', 'Password baru (min. 8 karakter)', 'new-password', 'Minimal 8 karakter'], ['password_confirmation', 'Ulangi password baru', 'new-password', 'Ketik ulang password baru']] as [$field, $label, $autocomplete, $placeholder])
            <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
                <span>{{ $label }}</span>
                <input type="password" name="{{ $field }}" required autocomplete="{{ $autocomplete }}"
                       placeholder="{{ $placeholder }}"
                       class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
                @error($field)
                    <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
                @enderror
            </label>
        @endforeach

        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
            Simpan password
        </button>
    </form>
</x-layouts.guest>
