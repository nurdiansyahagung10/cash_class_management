@props([
    'paginator',
    'label' => 'data',
    'live' => false,
    'perPageName' => null,
    'perPageModel' => null,
    'perPageOptions' => [],
])

{{--
    Numbered pager. With `live`, links stay real hrefs (no-JS / new tab) but clicks go through Livewire's
    gotoPage() so the table updates without a reload; the page-size select binds to `perPageModel`.
--}}
@php
    $current = $paginator->currentPage();
    $last = max(1, $paginator->lastPage());
    $pageName = $paginator->getPageName();
    $pages = collect([1, $current - 1, $current, $current + 1, $last])
        ->filter(fn ($page) => $page >= 1 && $page <= $last)
        ->unique()
        ->sort()
        ->values();
    $link = 'flex size-11 sm:size-9 items-center justify-center rounded-lg text-xs font-medium tabular-nums transition-colors';
    $goto = fn (int $page) => "gotoPage({$page}, '{$pageName}')";
@endphp

<div class="flex flex-col gap-3 border-t border-line px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6 print:hidden">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
        <p class="text-xs text-muted">
            <span class="font-medium text-ink tabular-nums">{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</span>
            dari <span class="font-medium text-ink tabular-nums">{{ $paginator->total() }}</span> {{ $label }}
        </p>

        @if ($perPageOptions !== [] && $perPageModel)
            <div class="flex items-center gap-2 text-xs text-muted">
                <label for="{{ $perPageModel }}-select">Per halaman</label>
                <div wire:ignore>
                    <select id="{{ $perPageModel }}-select" data-tom-select wire:model.live="{{ $perPageModel }}" class="w-20">
                        @foreach ($perPageOptions as $size)
                            <option value="{{ $loop->first ? '' : $size }}" @selected($paginator->perPage() === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @elseif ($perPageOptions !== [] && $perPageName)
            <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2 text-xs text-muted">
                @foreach (request()->except([$perPageName, $pageName]) as $key => $value)
                    @if (is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="{{ $perPageName }}-select">Per halaman</label>
                <select id="{{ $perPageName }}-select" name="{{ $perPageName }}" data-tom-select data-autosubmit class="w-20">
                    @foreach ($perPageOptions as $size)
                        <option value="{{ $size }}" @selected($paginator->perPage() === $size)>{{ $size }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="ui-btn ui-btn-outline">Terapkan</button></noscript>
            </form>
        @endif
    </div>

    <nav aria-label="Halaman {{ $label }}" class="flex items-center gap-1 self-end sm:self-auto">
        <a href="{{ $paginator->previousPageUrl() ?? '#' }}" aria-label="Halaman sebelumnya"
            @if ($live && $paginator->previousPageUrl()) wire:click.prevent="{{ $goto($current - 1) }}" @endif
            @class([
                $link, 'border border-line bg-surface',
                'pointer-events-none opacity-40' => ! $paginator->previousPageUrl(),
                'hover:bg-canvas-2' => $paginator->previousPageUrl(),
            ])>
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" /></svg>
        </a>

        @foreach ($pages as $index => $page)
            @if ($index > 0 && $page - $pages[$index - 1] > 1)
                <span class="flex size-11 items-center justify-center text-xs text-muted sm:size-9" aria-hidden="true">…</span>
            @endif
            <a href="{{ $paginator->url($page) }}" wire:key="{{ $pageName }}-{{ $page }}"
                @if ($live && $page !== $current) wire:click.prevent="{{ $goto($page) }}" @endif
                @if ($page === $current) aria-current="page" @endif
                @class([
                    $link,
                    'border border-line bg-surface text-ink shadow-xs' => $page === $current,
                    'text-muted hover:bg-canvas-2 hover:text-ink' => $page !== $current,
                ])>{{ $page }}</a>
        @endforeach

        <a href="{{ $paginator->nextPageUrl() ?? '#' }}" aria-label="Halaman berikutnya"
            @if ($live && $paginator->nextPageUrl()) wire:click.prevent="{{ $goto($current + 1) }}" @endif
            @class([
                $link, 'border border-line bg-surface',
                'pointer-events-none opacity-40' => ! $paginator->nextPageUrl(),
                'hover:bg-canvas-2' => $paginator->nextPageUrl(),
            ])>
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6" /></svg>
        </a>
    </nav>
</div>
