@props(['tone' => 'neutral', 'dot' => false])

@php
    // Linear-style tag: neutral chip, status color only in the leading dot.
    $tones = [
        'neutral' => $dot ? 'ui-tag' : 'ui-tag ui-tag-plain',
        'success' => 'ui-tag ui-tag-success',
        'warning' => 'ui-tag ui-tag-warning',
        'danger' => 'ui-tag ui-tag-danger',
        'info' => 'ui-tag ui-tag-info',
        'ink' => 'ui-tag ui-tag-plain border-brand/40 text-ink',
    ];
@endphp

<span {{ $attributes->class([$tones[$tone] ?? $tones['neutral']]) }}>{{ $slot }}</span>
