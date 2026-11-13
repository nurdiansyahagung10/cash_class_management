@extends('reports.pdf.layout')

@section('content')
    @php($rp = fn ($n) => 'Rp'.number_format($n, 0, ',', '.'))
    <table class="summary">
        <tr>
            <td><div class="lbl">Total Penerimaan</div><div class="val ok">{{ $rp($bkuData['totalDebit']) }}</div></td>
            <td><div class="lbl">Total Pengeluaran</div><div class="val bad">{{ $rp($bkuData['totalCredit']) }}</div></td>
            <td><div class="lbl">Saldo Kas</div><div class="val">{{ $rp($bkuData['endingBalance']) }}</div></td>
            <td><div class="lbl">Jumlah Transaksi</div><div class="val">{{ count($bkuData['entries']) }}</div></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:4%">No</th><th style="width:9%">Tanggal</th><th style="width:14%">No. Bukti</th><th>Uraian Transaksi</th>
                <th style="width:13%">Penerimaan</th><th style="width:13%">Pengeluaran</th><th style="width:13%">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bkuData['entries'] as $e)
                <tr>
                    <td class="c">{{ $e['no'] }}</td>
                    <td class="c">{{ $e['date'] }}</td>
                    <td style="font-size:7px">{{ $e['ref'] }}</td>
                    <td>{{ $e['description'] }}</td>
                    <td class="r num ok">{{ $e['debit'] > 0 ? $rp($e['debit']) : '-' }}</td>
                    <td class="r num bad">{{ $e['credit'] > 0 ? $rp($e['credit']) : '-' }}</td>
                    <td class="r num"><strong>{{ $rp($e['balance']) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="7" class="c muted">Belum ada transaksi.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="r">TOTAL</td>
                <td class="r num ok">{{ $rp($bkuData['totalDebit']) }}</td>
                <td class="r num bad">{{ $rp($bkuData['totalCredit']) }}</td>
                <td class="r num">{{ $rp($bkuData['endingBalance']) }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
