@props(['label' => false])

<button type="button" data-theme-toggle aria-label="Ganti tema terang/gelap" title="Ganti tema"
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-2 rounded-lg text-muted transition-colors hover:bg-ink/[0.06] hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand',
        'size-8' => ! $label,
        'min-h-8 px-2 text-[13px]' => $label,
    ]) }}>
    {{-- Shown in dark mode: switch to light --}}
    <svg class="size-4 light:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
    </svg>
    {{-- Shown in light mode: switch to dark --}}
    <svg class="hidden size-4 light:block" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
    </svg>
    @if ($label)
        <span class="light:hidden">Mode terang</span>
        <span class="hidden light:inline">Mode gelap</span>
    @endif
</button>
