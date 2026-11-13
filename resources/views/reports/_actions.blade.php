            {{-- Unduh & Cetak: PDF terformat untuk tab yang sedang dibuka --}}
            @php
                $pdfQuery = array_merge(request()->query(), ['tab' => $activeTab]);
                $csvRoute = match ($activeTab) {
                    'matrix' => route('reports.payments.export-matrix'),
                    'bku' => route('reports.payments.export-bku'),
                    default => route('reports.payments.export', request()->query()),
                };
            @endphp
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('reports.payments.pdf', $pdfQuery) }}"
                    class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-brand px-4 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-brand-dark sm:min-h-10">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh PDF
                </a>
                <a href="{{ route('reports.payments.pdf', $pdfQuery + ['inline' => 1]) }}" target="_blank" rel="noopener"
                    class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-navy px-4 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-black sm:min-h-10">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Laporan
                </a>
                <a href="{{ $csvRoute }}"
                    class="inline-flex min-h-11 items-center rounded-xl border border-line bg-white px-3.5 text-xs font-semibold text-muted transition-colors hover:bg-black/5 hover:text-black sm:min-h-10">
                    Data mentah (CSV)
                </a>
            </div>
