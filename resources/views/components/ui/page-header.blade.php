@props(['title', 'eyebrow' => null, 'description' => null])

<header {{ $attributes->class('flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between') }}>
    <div class="flex min-w-0 flex-col gap-1.5">
        @if ($eyebrow)
            <p class="ui-eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="text-2xl font-semibold tracking-[-0.04em] text-ink sm:text-[32px] sm:leading-10">{{ $title }}</h1>
        @if ($description)
            <p class="max-w-2xl text-sm leading-relaxed text-muted">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</header>
