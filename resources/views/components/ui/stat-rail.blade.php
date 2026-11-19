@props(['label' => 'Ringkasan', 'grid' => 'md:grid-cols-4'])

{{--
    Summary cards: a swipeable, snap-scrolling rail on small screens (next card peeks in),
    a plain grid from md up. Dots are driven by resources/js/app.js ([data-stat-rail]).
--}}
<section aria-label="{{ $label }}" data-stat-rail {{ $attributes->class('flex min-w-0 flex-col gap-2.5') }}>
    <div data-stat-track
        class="scroll-row -mx-4 flex snap-x snap-mandatory scroll-px-4 gap-3 px-4 sm:-mx-6 sm:scroll-px-6 sm:px-6 md:mx-0 md:grid md:overflow-visible md:px-0 lg:gap-4 {{ $grid }} *:w-[80%] *:shrink-0 *:snap-start sm:*:w-[46%] md:*:w-auto">
        {{ $slot }}
    </div>
    <div data-stat-dots wire:ignore class="flex justify-center gap-1.5 md:hidden" aria-hidden="true"></div>
</section>
