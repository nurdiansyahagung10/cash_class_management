@php
    $isActive = request()->routeIs($link['route']);
    $count = ($navCounts ?? [])[$link['route']] ?? 0;
@endphp
<a href="{{ route($link['route']) }}" data-nav-link @if ($isActive) aria-current="page" @endif
    @class([
        'group flex min-h-9 min-w-0 items-center gap-2 rounded-md px-2 text-[13px] transition-colors lg:min-h-[30px]',
        'focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-brand',
        'bg-ink/[0.06] font-medium text-ink' => $isActive,
        'text-body hover:bg-ink/[0.04] hover:text-ink' => ! $isActive,
    ])>
    <x-dynamic-component :component="'icons.' . $link['icon']"
        class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-ink' : 'text-muted group-hover:text-body' }}" />
    <span class="flex-1 truncate" data-nav-label>{{ $link['label'] }}</span>
    @if ($count > 0)
        <span class="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-danger-soft px-1 font-mono text-[10px] font-medium tabular-nums text-danger-ink">
            {{ $count }}<span class="sr-only"> menunggu</span>
        </span>
    @endif
</a>
