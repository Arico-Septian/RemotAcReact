<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Log Aktivitas — SmartAC</title>
    <style>
        /* dompdf tidak mendukung flexbox/grid — seluruh tata letak memakai tabel. */
        @page {
            margin: 28px 32px 46px 32px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111827;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #335fc2;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .header h1 {
            margin: 0;
            font-size: 16px;
            color: #111827;
        }

        .header .sub {
            margin: 3px 0 0;
            font-size: 9px;
            color: #6b7280;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .meta td {
            font-size: 8.5px;
            padding: 2px 0;
            vertical-align: top;
        }

        .meta .label {
            color: #6b7280;
            width: 90px;
        }

        .meta .value {
            color: #111827;
            font-weight: bold;
        }

        .notice {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
            padding: 6px 8px;
            font-size: 8.5px;
            margin-bottom: 10px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data thead th {
            background: #335fc2;
            color: #ffffff;
            font-size: 8.5px;
            text-align: left;
            padding: 6px 7px;
            border: 1px solid #2a4fa3;
        }

        table.data tbody td {
            font-size: 8.5px;
            padding: 5px 7px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        /* Baris selang-seling supaya mudah diikuti saat dicetak hitam-putih. */
        table.data tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        .col-no {
            width: 32px;
            text-align: right;
        }

        .col-time {
            width: 92px;
        }

        .col-user {
            width: 110px;
        }

        .col-room {
            width: 100px;
        }

        .col-ac {
            width: 120px;
        }

        .empty {
            text-align: center;
            padding: 24px;
            color: #6b7280;
            font-style: italic;
        }

        table.data.page-break {
            page-break-after: always;
        }

        /* Footer tetap di setiap halaman + nomor halaman otomatis dompdf. */
        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 26px;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
            font-size: 7.5px;
            color: #6b7280;
        }

        .footer .page:after {
            content: counter(page);
        }
    </style>
</head>

<body>
    <div class="footer">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="font-size:7.5px; color:#6b7280;">SmartAC — Sistem Kontrol AC Berbasis IoT</td>
                <td style="font-size:7.5px; color:#6b7280; text-align:right;">Halaman <span class="page"></span></td>
            </tr>
        </table>
    </div>

    <div class="header">
        <h1>Laporan Log Aktivitas</h1>
        <p class="sub">SmartAC — Sistem Kontrol AC Ruang Server Berbasis IoT</p>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Dibuat pada</td>
            <td class="value">{{ $generatedAt }}</td>
            <td class="label" style="width:70px;">Oleh</td>
            <td class="value">{{ $generatedBy }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah data</td>
            <td class="value" colspan="3">
                {{ number_format($shown, 0, ',', '.') }} baris
                @if ($truncated)
                    (dari total {{ number_format($total, 0, ',', '.') }})
                @endif
            </td>
        </tr>
        @foreach ($filters as $label => $value)
            <tr>
                <td class="label">{{ $label }}</td>
                <td class="value" colspan="3">{{ $value }}</td>
            </tr>
        @endforeach
        @if (empty($filters))
            <tr>
                <td class="label">Filter</td>
                <td class="value" colspan="3">Tanpa filter (seluruh data)</td>
            </tr>
        @endif
    </table>

    @if ($truncated)
        <div class="notice">
            <strong>Catatan:</strong> Laporan dibatasi {{ number_format($shown, 0, ',', '.') }} baris terbaru dari
            total {{ number_format($total, 0, ',', '.') }} data yang cocok. Persempit rentang tanggal atau filter
            untuk memperoleh laporan yang utuh.
        </div>
    @endif

    @if ($rows->isEmpty())
        <table class="data">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-time">Waktu</th>
                    <th class="col-user">User</th>
                    <th class="col-room">Ruangan</th>
                    <th class="col-ac">Unit AC</th>
                    <th>Aktivitas</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" class="empty">Tidak ada data aktivitas untuk filter yang dipilih.</td>
                </tr>
            </tbody>
        </table>
    @else
        {{--
            Tabel sengaja dipecah per halaman, bukan satu tabel panjang.
            dompdf me-reflow seluruh tabel sebagai satu unit, sehingga satu
            tabel raksasa berskala kuadratik — diukur 1000 baris: 14,1 detik
            sebagai satu tabel vs 7,4 detik saat dipecah. Header ikut diulang
            di tiap halaman sebagai efek sampingnya.
        --}}
        @foreach ($rows->chunk($perPage) as $chunkIndex => $chunk)
            <table class="data {{ !$loop->last ? 'page-break' : '' }}">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-time">Waktu</th>
                        <th class="col-user">User</th>
                        <th class="col-room">Ruangan</th>
                        <th class="col-ac">Unit AC</th>
                        <th>Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($chunk as $i => $row)
                        <tr>
                            <td class="col-no">{{ $i + 1 }}</td>
                            <td>{{ $row['datetime'] }}</td>
                            <td>{{ $row['user'] }}</td>
                            <td>{{ $row['room'] }}</td>
                            <td>{{ $row['ac'] }}</td>
                            <td>{{ $row['activity'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif
</body>

</html>
