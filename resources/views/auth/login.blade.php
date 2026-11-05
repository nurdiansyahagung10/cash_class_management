<x-layouts.guest title="Masuk" lead="Pakai No. absen / NIM dan password dari staff kelas.">
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
        @csrf

        <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
            <span>No. absen / NIM</span>
            <input type="text" name="student_no" value="{{ old('student_no') }}" required autofocus autocomplete="username"
                   placeholder="Contoh: 29 atau NIM Anda"
                   class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
            @error('student_no')
                <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
            @enderror
            @if (session('error') && !$errors->has('student_no'))
                <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ session('error') }}</span>
            @endif
        </label>

        <label class="flex flex-col gap-1.5 text-xs font-semibold text-black">
            <span>Password</span>
            <input type="password" name="password" required autocomplete="current-password"
                   placeholder="••••••••"
                   class="min-h-11 rounded-xl border border-black/15 bg-canvas/30 px-3.5 text-sm font-normal text-black placeholder:text-muted/60 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20">
            @error('password')
                <span role="alert" class="text-xs font-medium text-[#8f1d0e]">{{ $message }}</span>
            @enderror
        </label>

        <label class="flex min-h-11 cursor-pointer items-center gap-2.5 text-xs font-medium text-muted hover:text-black">
            <input type="checkbox" name="remember" class="size-4 rounded border-black/20 text-brand focus:ring-brand">
            <span>Ingat saya</span>
        </label>

        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand px-4 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
            Masuk
        </button>
    </form>
</x-layouts.guest>
