<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 90px 36px 70px 36px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 0; }
        header { position: fixed; top: -70px; left: 0; right: 0; height: 60px; border-bottom: 2px solid #02093a; }
        header .org { font-size: 14px; font-weight: bold; color: #02093a; }
        header .sub { font-size: 8px; color: #555; margin-top: 2px; }
        header .right { position: absolute; right: 0; top: 0; text-align: right; font-size: 8px; color: #555; }
        footer { position: fixed; bottom: -48px; left: 0; right: 0; height: 30px; border-top: 1px solid #ccc; font-size: 7.5px; color: #777; padding-top: 5px; }
        h1 { font-size: 13px; margin: 0 0 2px; color: #02093a; }
        .meta { color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #02093a; color: #fff; font-size: 8px; text-transform: uppercase; padding: 5px 4px; border: 1px solid #02093a; }
        td { padding: 4px; border: 1px solid #d4d4d4; vertical-align: top; }
        tbody tr:nth-child(even) td { background: #f7f7f6; }
        tfoot td { background: #e6f3fe; font-weight: bold; border-top: 2px solid #02093a; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .r { text-align: right; } .c { text-align: center; } .num { white-space: nowrap; }
        .muted { color: #777; }
        .ok { color: #14532d; } .bad { color: #8f1d0e; }
        .pill { padding: 1px 5px; border-radius: 8px; font-size: 7.5px; font-weight: bold; }
        .pill-ok { background: #dcfce7; color: #14532d; } .pill-warn { background: #fff1d6; color: #7a4a00; } .pill-bad { background: #fde4e0; color: #8f1d0e; }
        .summary { width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 6px 0; margin-left: -6px; }
        .summary td { border: 1px solid #d4d4d4; background: #fafafa !important; padding: 7px 8px; }
        .summary .lbl { font-size: 7.5px; text-transform: uppercase; color: #777; }
        .summary .val { font-size: 12px; font-weight: bold; margin-top: 2px; }
        .sign { width: 100%; margin-top: 28px; page-break-inside: avoid; }
        .sign td { border: 0; background: none !important; text-align: center; width: 33%; padding: 0 8px; }
        .sign .gap { height: 52px; }
        .sign .name { border-top: 1px solid #111; display: inline-block; min-width: 130px; padding-top: 3px; font-weight: bold; }
        .block-title { font-size: 9px; font-weight: bold; margin: 12px 0 4px; color: #02093a; }
    </style>
</head>

<body>
    <header>
        <div class="org">KAS KELAS 1A</div>
        <div class="sub">Laporan Keuangan Kas Kelas &middot; bersumber dari ledger berantai hash</div>
        <div class="right">Dicetak: {{ $generatedAt->translatedFormat('d F Y, H:i') }}<br>Oleh: {{ $printedBy }}</div>
    </header>
    <footer>
        Dokumen dihasilkan otomatis oleh sistem Kas Kelas.
        <script type="text/php">
            if (isset($pdf)) { $pdf->page_text($pdf->get_width() - 110, $pdf->get_height() - 36, "Halaman {PAGE_NUM} / {PAGE_COUNT}", null, 7.5, [0.45, 0.45, 0.45]); }
        </script>
    </footer>

    <main>
        <h1>{{ $title }}</h1>
        <div class="meta">{{ $subtitle }}</div>

        @yield('content')

        <table class="sign">
            <tr>
                <td>Mengetahui,<br>Wali Kelas<div class="gap"></div><span class="name">{{ $waliKelas }}</span></td>
                <td>Ketua Kelas<div class="gap"></div><span class="name">{{ $ketuaKelas }}</span></td>
                <td>{{ $generatedAt->translatedFormat('d F Y') }}<br>Bendahara<div class="gap"></div><span class="name">{{ $bendaharaKelas }}</span></td>
            </tr>
        </table>
    </main>
</body>

</html>
