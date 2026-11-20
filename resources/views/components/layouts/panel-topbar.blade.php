@php
    $currentLink = collect($links ?? [])->first(fn ($link) => request()->routeIs($link['route']));
@endphp
{{-- Desktop topbar on the canvas above the content panel: icon tile + breadcrumb left, date right. --}}
<div class="hidden h-12 shrink-0 items-center justify-between gap-4 bg-canvas px-3 lg:flex">
    <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-2 text-[13px]">
        <span class="ui-tile size-6! rounded-md!" style="--tile: var(--color-tile-coral)" aria-hidden="true">
            <x-dynamic-component :component="'icons.' . ($currentLink['icon'] ?? 'home')" />
        </span>
        <a href="{{ route('dashboard') }}" class="shrink-0 text-muted transition-colors hover:text-ink">Kas Kelas 1A</a>
        @if ($currentLink['group'] ?? null)
            <span class="text-faint" aria-hidden="true">/</span>
            <span class="shrink-0 text-muted">{{ $currentLink['group'] }}</span>
        @endif
        <span class="text-faint" aria-hidden="true">/</span>
        <span class="truncate font-medium text-ink" aria-current="page">{{ $title }}</span>
    </nav>
    <span class="inline-flex h-7 shrink-0 items-center gap-1.5 rounded-md border border-line bg-surface px-2 font-mono text-[11px] text-muted shadow-2xs">
        <x-ui.icon name="calendar" class="size-3.5" />
        {{ now()->translatedFormat('d M Y') }}
    </span>
</div>
