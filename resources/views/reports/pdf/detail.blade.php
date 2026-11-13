@extends('reports.pdf.layout')

@section('content')
    @php($rp = fn ($n) => 'Rp'.number_format($n, 0, ',', '.'))
    <table class="summary">
        <tr>
            <td><div class="lbl">Total Tagihan</div><div class="val">{{ $rp($totalTarget) }}</div></td>
            <td><div class="lbl">Terkumpul</div><div class="val ok">{{ $rp($totalPaid) }}</div></td>
            <td><div class="lbl">Sisa Tagihan</div><div class="val bad">{{ $rp($totalTarget - $totalPaid) }}</div></td>
            <td><div class="lbl">Jumlah Tagihan</div><div class="val">{{ $invoices->count() }}</div></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:4%">No</th><th style="width:8%">NIM</th><th>Nama Anggota</th><th>Periode</th>
                <th style="width:12%">Tagihan</th><th style="width:12%">Dibayar</th><th style="width:12%">Sisa</th>
                <th style="width:9%">Tenggat</th><th style="width:10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $invoice)
                <tr>
                    <td class="c">{{ $loop->iteration }}</td>
                    <td class="c">{{ $invoice->user->student_no ?? '-' }}</td>
                    <td>{{ $invoice->user->name }}</td>
                    <td>{{ $invoice->period->title }}<br><span class="muted">{{ $invoice->period->category->name ?? 'Uang Kas' }}</span></td>
                    <td class="r num">{{ $rp($invoice->amount) }}</td>
                    <td class="r num ok">{{ $rp($invoice->paidAmount()) }}</td>
                    <td class="r num {{ $invoice->remainingAmount() > 0 ? 'bad' : 'muted' }}">{{ $rp($invoice->remainingAmount()) }}</td>
                    <td class="c">{{ $invoice->period->due_date->format('d/m/Y') }}</td>
                    <td class="c">
                        @if ($invoice->isPaid())
                            <span class="pill pill-ok">Lunas</span>
                        @elseif ($invoice->isPartial())
                            <span class="pill pill-warn">Menyicil</span>
                        @else
                            <span class="pill pill-bad">{{ $invoice->isOverdue() ? 'Telat' : 'Belum Bayar' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="c muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="r">TOTAL</td>
                <td class="r num">{{ $rp($totalTarget) }}</td>
                <td class="r num ok">{{ $rp($totalPaid) }}</td>
                <td class="r num bad">{{ $rp($totalTarget - $totalPaid) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
@endsection
