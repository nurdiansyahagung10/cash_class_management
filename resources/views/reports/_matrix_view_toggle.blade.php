{{-- Switch between the per-period board and the classic matrix inside the "Iuran siswa" tab. --}}
@php
    $currentMatrixView = $matrixLayout ?? 'period';
    $matrixViews = ['period' => 'Per periode', 'grid' => 'Matriks'];
@endphp
<div class="inline-flex shrink-0 gap-0.5 rounded-lg border border-line bg-surface p-0.5 shadow-2xs print:hidden" role="group" aria-label="Tampilan iuran siswa">
    @foreach ($matrixViews as $viewKey => $viewLabel)
        @php($isCurrentView = $currentMatrixView === $viewKey)
        <a href="{{ route('reports.payments', array_merge(array_diff_key($query, array_flip(['m_view'])), ['tab' => 'matrix'], $viewKey === 'grid' ? ['m_view' => 'grid'] : [])) }}"
            wire:click.prevent="showMatrixView('{{ $viewKey }}')"
            @if ($isCurrentView) aria-current="true" @endif
            @class([
                'inline-flex min-h-7 items-center rounded-md px-2.5 text-xs font-medium transition-colors',
                'bg-primary text-on-primary' => $isCurrentView,
                'text-muted hover:bg-surface-3 hover:text-ink' => ! $isCurrentView,
            ])>{{ $viewLabel }}</a>
    @endforeach
</div>
