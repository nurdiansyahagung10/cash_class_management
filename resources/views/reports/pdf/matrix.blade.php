@extends('reports.pdf.layout')

@section('content')
    @php
        $rp = fn ($n) => 'Rp'.number_format($n, 0, ',', '.');
        $chunks = $matrixData['periods']->chunk(7);
    @endphp
    <table class="summary">
        <tr>
            <td><div class="lbl">Total Target</div><div class="val">{{ $rp($matrixData['totalTarget']) }}</div></td>
            <td><div class="lbl">Terkumpul</div><div class="val ok">{{ $rp($matrixData['totalPaid']) }}</div></td>
            <td><div class="lbl">Tunggakan</div><div class="val bad">{{ $rp($matrixData['totalRemaining']) }}</div></td>
            <td><div class="lbl">Anggota</div><div class="val">{{ count($matrixData['rows']) }}</div></td>
        </tr>
    </table>

    @foreach ($chunks as $chunk)
        <div class="block-title">Periode: {{ $chunk->first()->title }} &ndash; {{ $chunk->last()->title }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width:4%">No</th><th style="width:7%">NIM</th><th style="width:17%">Nama</th>
                    @foreach ($chunk as $period)
                        <th>{{ \Illuminate\Support\Str::limit($period->title, 14) }}<br>{{ $rp($period->amount) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($matrixData['rows'] as $row)
                    <tr>
                        <td class="c">{{ $row['no'] }}</td>
                        <td class="c">{{ $row['user']->student_no ?? '-' }}</td>
                        <td>{{ $row['user']->name }}</td>
                        @foreach ($chunk as $period)
                            @php($cell = $row['cells'][$period->id])
                            <td class="c">
                                @if ($cell['status'] === 'none')
                                    <span class="muted">-</span>
                                @elseif ($cell['status'] === 'paid')
                                    <span class="pill pill-ok">Lunas</span>
                                @elseif ($cell['status'] === 'partial')
                                    <span class="pill pill-warn">Cicil {{ $rp($cell['paid']) }}</span>
                                @else
                                    <span class="pill pill-bad">Belum</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="block-title">Rekap Total per Anggota</div>
    <table>
        <thead>
            <tr><th style="width:4%">No</th><th style="width:8%">NIM</th><th>Nama</th><th style="width:15%">Total Setor</th><th style="width:15%">Tunggakan</th><th style="width:11%">Status</th></tr>
        </thead>
        <tbody>
            @foreach ($matrixData['rows'] as $row)
                <tr>
                    <td class="c">{{ $row['no'] }}</td>
                    <td class="c">{{ $row['user']->student_no ?? '-' }}</td>
                    <td>{{ $row['user']->name }}</td>
                    <td class="r num ok">{{ $rp($row['total_paid']) }}</td>
                    <td class="r num {{ $row['total_remaining'] > 0 ? 'bad' : 'muted' }}">{{ $rp($row['total_remaining']) }}</td>
                    <td class="c"><span class="pill {{ $row['status'] === 'Lunas' ? 'pill-ok' : ($row['status'] === 'Menyicil' ? 'pill-warn' : 'pill-bad') }}">{{ $row['status'] === 'Belum Bayar' ? 'Menunggak' : $row['status'] }}</span></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="r">TOTAL</td>
                <td class="r num ok">{{ $rp($matrixData['totalPaid']) }}</td>
                <td class="r num bad">{{ $rp($matrixData['totalRemaining']) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
@endsection
