@php($isActive = request()->routeIs($link['route']))
<a href="{{ route($link['route']) }}" @if ($isActive) aria-current="page" @endif
    @class([
        'group flex min-h-9 min-w-0 items-center gap-2 rounded-lg px-2 text-[13px] transition-colors lg:min-h-8',
        'focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-brand',
        'bg-ink/[0.07] font-medium text-ink' => $isActive,
        'text-body hover:bg-ink/[0.04] hover:text-ink' => ! $isActive,
    ])>
    <x-dynamic-component :component="'icons.' . $link['icon']"
        class="size-4 shrink-0 transition-colors {{ $isActive ? 'text-ink' : 'text-muted group-hover:text-body' }}" />
    <span class="truncate">{{ $link['label'] }}</span>
</a>
