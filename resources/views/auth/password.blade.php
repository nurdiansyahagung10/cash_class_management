<x-layouts.guest title="Ganti password" lead="Password awal dari staff hanya untuk login pertama. Buat password barumu sendiri.">
    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
        @csrf
        @method('PUT')

        @foreach ([['current_password', 'Password saat ini', 'current-password', 'Masukkan password saat ini'], ['password', 'Password baru (min. 8 karakter)', 'new-password', 'Minimal 8 karakter'], ['password_confirmation', 'Ulangi password baru', 'new-password', 'Ketik ulang password baru']] as [$field, $label, $autocomplete, $placeholder])
            <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
                <span>{{ $label }}</span>
                <input type="password" name="{{ $field }}" required autocomplete="{{ $autocomplete }}"
                       placeholder="{{ $placeholder }}"
                       class="min-h-9 rounded-xl border border-line bg-canvas/30 px-3 text-sm font-normal text-ink placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">
                @error($field)
                    <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
                @enderror
            </label>
        @endforeach

        <button type="submit" class="ui-btn ui-btn-primary">
            Simpan password
        </button>
    </form>
</x-layouts.guest>
