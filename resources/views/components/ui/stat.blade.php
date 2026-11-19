@props(['label', 'value', 'hint' => null, 'href' => null, 'tone' => 'default'])

@php($tag = $href ? 'a' : 'article')
<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class([
        'ui-card group flex min-w-0 flex-col gap-3 p-4 sm:p-5',
        'transition-colors hover:border-line-strong/60' => $href,
    ]) }}>
    <div class="flex items-start justify-between gap-3">
        <h3 class="text-[13px] font-medium text-muted">{{ $label }}</h3>
        @isset($icon)
            <span class="text-muted" aria-hidden="true">{{ $icon }}</span>
        @endisset
    </div>
    <p @class([
        'break-words text-xl font-semibold tracking-[-0.03em] tabular-nums sm:text-2xl',
        'text-danger' => $tone === 'danger',
        'text-ink' => $tone !== 'danger',
    ])>{{ $value }}</p>
    {{ $slot }}
    @if ($hint)
        <p class="text-xs leading-snug text-muted">{{ $hint }}</p>
    @endif
</{{ $tag }}>
