{{-- Desktop topbar above the content: breadcrumb left, date right (Linear-style issue header). --}}
<div class="sticky top-0 z-10 hidden h-11 shrink-0 items-center justify-between gap-4 border-b border-line bg-canvas/85 px-4 backdrop-blur-md lg:flex lg:px-6">
    <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-1.5 text-[13px]">
        <a href="{{ route('dashboard') }}" class="shrink-0 text-muted transition-colors hover:text-ink">Kas Kelas 1A</a>
        <svg class="size-3.5 shrink-0 text-faint" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M6.22 4.22a.75.75 0 0 1 1.06 0l3.25 3.25a.75.75 0 0 1 0 1.06l-3.25 3.25a.75.75 0 0 1-1.06-1.06L8.94 8 6.22 5.28a.75.75 0 0 1 0-1.06Z" />
        </svg>
        <span class="truncate font-medium text-ink" aria-current="page">{{ $title }}</span>
    </nav>
    <span class="shrink-0 font-mono text-xs text-muted">{{ now()->translatedFormat('d M Y') }}</span>
</div>
