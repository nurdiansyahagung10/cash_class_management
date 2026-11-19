<x-layouts.guest title="Masuk" lead="Pakai NIM dan password dari staff kelas.">
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
        @csrf

        <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
            <span>NIM</span>
            <input type="text" name="student_no" value="{{ old('student_no') }}" required autofocus
                autocomplete="username" placeholder="Contoh: 29 atau NIM Anda"
                class="min-h-9 rounded-xl border border-line bg-canvas/30 px-3 text-sm font-normal text-ink placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">
            @error('student_no')
                <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
            @enderror
            @if (session('error') && !$errors->has('student_no'))
                <span role="alert" class="text-xs font-medium text-danger">{{ session('error') }}</span>
            @endif
        </label>

        <label class="flex flex-col gap-1.5 text-xs font-semibold text-ink">
            <span>Password</span>
            <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                class="min-h-9 rounded-xl border border-line bg-canvas/30 px-3 text-sm font-normal text-ink placeholder:text-muted/60 transition-all focus:border-line-strong focus:bg-surface focus:outline-none focus:ring-2 focus:ring-ink/5">
            @error('password')
                <span role="alert" class="text-xs font-medium text-danger">{{ $message }}</span>
            @enderror
        </label>

        <label class="flex min-h-11 cursor-pointer items-center gap-2.5 text-xs font-medium text-muted hover:text-ink">
            <input type="checkbox" name="remember" class="size-4 rounded border-ink/20 text-brand focus:ring-brand">
            <span>Ingat saya</span>
        </label>

        <button type="submit" class="ui-btn ui-btn-primary">
            Masuk
        </button>
    </form>
</x-layouts.guest>
