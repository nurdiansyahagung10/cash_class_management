@php
    $visibleLinks = collect($links)->where('show', true);
    $topLinks = $visibleLinks->whereNull('group');
    $groupedLinks = $visibleLinks->whereNotNull('group')->groupBy('group');
    $initials = $user
        ? \Illuminate\Support\Str::of($user->name)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')
        : null;
@endphp

{{-- Mobile: floating, rounded bar that hovers over the content; a spacer below keeps content clear of it. --}}
<header data-topnav class="fixed inset-x-3 top-3 z-20 rounded-2xl border border-line bg-surface/80 shadow-lg backdrop-blur-xl lg:hidden">
    <div class="flex h-13 items-center justify-between gap-2 pl-2 pr-1.5">
        <a href="{{ route('dashboard') }}" class="flex min-h-10 items-center gap-2 rounded-lg px-1.5">
            <span class="flex size-5 items-center justify-center rounded-[5px] bg-brand text-[10px] font-semibold text-white" aria-hidden="true">K</span>
            <span class="text-[13px] font-semibold text-ink">Kas Kelas 1A</span>
        </a>

        <div class="flex items-center gap-1">
            <x-theme-toggle class="size-10" />
            <button type="button" data-sidebar-toggle aria-controls="sidebar-utama" aria-expanded="false" aria-label="Buka menu"
                class="inline-flex size-10 items-center justify-center rounded-lg text-muted transition-colors hover:bg-ink/[0.06] hover:text-ink">
                <svg class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
            </button>
        </div>
    </div>
</header>


<div class="h-16 lg:hidden" aria-hidden="true"></div>

<div class="flex min-h-dvh flex-row">
    <a href="#konten-utama"
        class="sr-only rounded-lg bg-surface px-3 py-2 text-sm font-medium text-ink shadow-md focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[1001]">Lewati
        ke konten utama</a>

    {{-- Sidebar: off-canvas below lg, sticky column from lg up --}}
    <nav id="sidebar-utama" aria-label="Navigasi utama"
        class="fixed left-0 top-0 z-[1000] h-dvh w-60 shrink-0 overflow-y-auto border-r border-line bg-canvas transition-[left] duration-300 ease-out lg:sticky">
        <div class="flex min-h-full flex-col px-2 py-2.5">
            {{-- Workspace row --}}
            <div class="flex items-center justify-between gap-1 pb-3">
                <a href="{{ route('dashboard') }}" class="flex min-h-8 min-w-0 items-center gap-2 rounded-lg px-2">
                    <span class="flex size-5 shrink-0 items-center justify-center rounded-[5px] bg-brand text-[10px] font-semibold text-white" aria-hidden="true">K</span>
                    <span class="truncate text-[13px] font-semibold text-ink">Kas Kelas 1A</span>
                </a>
                <div class="flex items-center">
                    <x-theme-toggle />
                    <button type="button" aria-label="Tutup menu" data-sidebar-toggle
                        class="inline-flex size-8 items-center justify-center rounded-lg text-muted transition-colors hover:bg-ink/[0.06] hover:text-ink lg:hidden">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Ungrouped links --}}
            <div class="flex flex-col gap-px">
                @foreach ($topLinks as $link)
                    @include('components.layouts.nav-link', ['link' => $link])
                @endforeach
            </div>

            {{-- Collapsible groups (Linear "Workspace ▾") --}}
            @foreach ($groupedLinks as $group => $groupLinks)
                @php($groupHasActive = $groupLinks->contains(fn ($l) => request()->routeIs($l['route'])))
                <details class="group/section mt-4" open>
                    <summary class="flex min-h-7 cursor-pointer list-none items-center gap-1 rounded-md px-2 text-xs font-medium text-muted transition-colors hover:text-ink [&::-webkit-details-marker]:hidden">
                        {{ $group }}
                        <svg class="size-3 transition-transform group-open/section:rotate-0 -rotate-90" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M4.427 6.427a.6.6 0 0 1 .849 0L8 9.151l2.724-2.724a.6.6 0 1 1 .849.849l-3.149 3.148a.6.6 0 0 1-.848 0L4.427 7.276a.6.6 0 0 1 0-.849Z" />
                        </svg>
                        @if ($groupHasActive)
                            <span class="sr-only">(halaman aktif ada di sini)</span>
                        @endif
                    </summary>
                    <div class="mt-0.5 flex flex-col gap-px">
                        @foreach ($groupLinks as $link)
                            @include('components.layouts.nav-link', ['link' => $link])
                        @endforeach
                    </div>
                </details>
            @endforeach

            {{-- Account --}}
            <div class="mt-auto pt-4">
                @if ($user)
                    <div class="flex items-center gap-2 rounded-lg px-2 py-1.5">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-surface-3 text-[10px] font-medium text-body ring-1 ring-line" aria-hidden="true">{{ $initials }}</span>
                        <span class="flex min-w-0 flex-1 flex-col leading-tight">
                            <span class="truncate text-[13px] font-medium text-ink">{{ $user->name }}</span>
                            <span class="flex items-center gap-1.5 text-[11px] capitalize text-muted">
                                <span class="inline-block size-1.5 rounded-full {{ $user->isAdmin() ? 'bg-brand' : ($user->canManageMembers() ? 'bg-amber' : 'bg-success') }}" aria-hidden="true"></span>
                                {{ $user->role->value }}
                            </span>
                        </span>
                        <a href="{{ route('password.edit') }}" title="Ganti password" aria-label="Ganti password"
                            class="inline-flex size-8 items-center justify-center rounded-lg text-muted transition-colors hover:bg-ink/[0.06] hover:text-ink">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                            </svg>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Keluar" aria-label="Keluar"
                                class="inline-flex size-8 items-center justify-center rounded-lg text-muted transition-colors hover:bg-ink/[0.06] hover:text-danger">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                </svg>
                            </button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="ui-btn ui-btn-primary w-full">Masuk</a>
                @endif
            </div>
        </div>
    </nav>
