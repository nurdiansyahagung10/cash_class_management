@props(['paginator', 'label' => 'data'])

<div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3 sm:px-6 print:hidden">
    <p class="text-xs text-muted">
        <span class="font-semibold text-black tabular-nums">{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</span>
        dari <span class="font-semibold text-black tabular-nums">{{ $paginator->total() }}</span> {{ $label }}
    </p>

    <div class="flex items-center gap-2">
        <span class="text-xs font-semibold tabular-nums text-black">
            {{ $paginator->currentPage() }} / {{ max(1, $paginator->lastPage()) }}
        </span>

        <a href="{{ $paginator->previousPageUrl() ?? '#' }}" aria-label="Halaman sebelumnya" @class([
            'flex size-11 items-center justify-center rounded-xl border border-line transition sm:size-9',
            'pointer-events-none opacity-30' => ! $paginator->previousPageUrl(),
            'hover:bg-black/5' => $paginator->previousPageUrl(),
        ])>
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m15 18-6-6 6-6" />
            </svg>
        </a>

        <a href="{{ $paginator->nextPageUrl() ?? '#' }}" aria-label="Halaman berikutnya" @class([
            'flex size-11 items-center justify-center rounded-xl border border-line transition sm:size-9',
            'pointer-events-none opacity-30' => ! $paginator->nextPageUrl(),
            'hover:bg-black/5' => $paginator->nextPageUrl(),
        ])>
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m9 18 6-6-6-6" />
            </svg>
        </a>
    </div>
</div>
