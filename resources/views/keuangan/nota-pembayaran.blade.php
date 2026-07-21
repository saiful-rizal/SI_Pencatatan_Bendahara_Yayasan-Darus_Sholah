<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pembayaran</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 2mm;
        }

        html, body {
            width: 58mm;
            margin: 0;
            padding: 0;
        }

        body {
            background: #f2f2f2;
            padding: 0;
            font-family: "Courier New", monospace;
            font-size: 11px;
        }

        .nota {
            background: #fff;
            width: 100%;
            box-sizing: border-box;
            margin: auto;
            padding: 2mm;
            border: 1px solid #000;
        }

        .header {
            text-align: center;
        }

        .logo {
            width: 80px;
            margin-bottom: 10px;
            filter: grayscale(100%) contrast(1.2);
            -webkit-filter: grayscale(100%) contrast(1.2);
        }

        .line {
            border-top: 1px dashed #000;
            margin: 15px 0;
        }

        .flex {
            display: flex;
            justify-content: space-between;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .info-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .info-table td.info-label {
            width: 34%;
            font-size: 9.5px;
            white-space: normal;
            word-break: break-word;
            line-height: 1.2;
        }

        .info-table td.info-sep {
            width: 3%;
        }

        .info-table td.info-value {
            word-break: break-word;
            line-height: 1.2;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th, td {
            padding: 6px 4px;
        }

        th {
            text-align: left;
            border-bottom: 1px dashed #000;
        }

        .text-right {
            text-align: right;
        }

        .total-section {
            margin-top: 8px;
            width: 100%;
            float: right;
        }

        .footer {
            margin-top: 20px;
            text-align: right;
        }

        .item-metode {
            font-size: 9px;
            color: #444;
            font-style: italic;
        }

        .item-row {
            border-bottom: 1px dotted #999;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 4px;
        }

        .item-name {
            flex: 1;
        }

        .status-badge {
            flex-shrink: 0;
            display: inline-block;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.2px;
            padding: 2px 5px;
            border-radius: 3px;
            white-space: nowrap;
            line-height: 1.3;
        }

        .status-lunas {
            background: #0a7d24;
            color: #fff;
        }

        .status-belum {
            background: #b30000;
            color: #fff;
        }

        .metode-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .metode-table td {
            padding: 1px 0;
        }

        @media print {
            body {
                background: #fff;
            }

            .nota {
                border: none;
                margin: 0;
                padding: 0;
            }

            .logo {
                filter: grayscale(100%) contrast(1.2);
                -webkit-filter: grayscale(100%) contrast(1.2);
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body onload="window.print(); window.onafterprint = function(){ if (window.opener && !window.opener.closed) { window.opener.location.reload(); } window.close(); };">
@php
    $siswa = optional(optional($pembayarans->first())->tagihan)->siswa;
    $tanggalCetak = optional($pembayarans->max('tanggal_bayar'));
    $totalBayar = (float) $pembayarans->sum('nominal_bayar');
    $idNotaAcuan = $pembayarans->pluck('id')->min();
    $noTransaksi = 'TRX' . ($tanggalCetak ? $tanggalCetak->format('ymd') : date('ymd')) . str_pad((string) $idNotaAcuan, 4, '0', STR_PAD_LEFT);

    $kategoriLabel = [
        'mondok' => 'Mondok',
        'non_mondok' => 'Non Mondok',
        'alumni' => 'Alumni',
        'non_alumni' => 'Non Alumni',
    ][$siswa->kategori ?? ''] ?? ($siswa->kategori ?? null ? ucfirst(str_replace('_', ' ', $siswa->kategori)) : '-');

    $metodeLabel = [
        'cash' => 'Cash',
        'tunai' => 'Cash',
        'cicil' => 'Cicil',
        'transfer' => 'Transfer',
    ];

    $rekapMetode = $pembayarans
        ->groupBy(fn ($p) => ($p->metode_bayar ?? '-') . '|' . ($p->nama_bank ?? ''))
        ->map(function ($group) {
            $first = $group->first();
            return [
                'metode' => $first->metode_bayar,
                'nama_bank' => $first->nama_bank,
                'total' => $group->sum('nominal_bayar'),
            ];
        })
        ->values();
@endphp
<div class="nota">
    <div class="header">
        <img src="{{ asset('image/logo_pondok.jpeg') }}" class="logo" alt="Logo">
        <h2 style="margin:0;">SMA UNGGULAN BPPT DARUS SHOLAH</h2>
        <p style="margin:0;">Alamat Sekolah</p>
    </div>

    <div class="line"></div>

    <h3 style="text-align:center; margin:10px 0;">BUKTI PEMBAYARAN</h3>

    <div class="line"></div>

    <table class="info-table">
        <tr>
            <td class="info-label">No Transaksi</td>
            <td class="info-sep">:</td>
            <td class="info-value">{{ $noTransaksi }}</td>
        </tr>
        <tr>
            <td class="info-label">Tanggal</td>
            <td class="info-sep">:</td>
            <td class="info-value">{{ $tanggalCetak ? $tanggalCetak->format('d-m-Y H:i:s') : now()->format('d-m-Y H:i:s') }}</td>
        </tr>
        <tr>
            <td class="info-label">No Induk</td>
            <td class="info-sep">:</td>
            <td class="info-value">{{ $siswa->nis ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Kelas</td>
            <td class="info-sep">:</td>
            <td class="info-value">{{ $siswa->kelas ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Nama</td>
            <td class="info-sep">:</td>
            <td class="info-value">{{ $siswa->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Kategori</td>
            <td class="info-sep">:</td>
            <td class="info-value">{{ $kategoriLabel }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Item</th>
                <th width="20%" class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pembayarans as $index => $pembayaran)
            @php
                $tagihan = $pembayaran->tagihan;
                // Status lunas/belum lunas dihitung dari sisa tagihan saat ini
                // (nominal_awal - potongan - total seluruh pembayaran pada tagihan tsb).
                // Jika metode bayar cicil, tagihan otomatis belum lunas selama masih ada sisa.
                $sisa = $tagihan ? $tagihan->sisaTagihan() : null;
                $isLunas = $sisa !== null ? $sisa <= 0 : null;
            @endphp
            <tr class="item-row">
                <td>{{ $index + 1 }}</td>
                <td>
                    <div class="item-head">
                        <span class="item-name">
                            {{ optional($tagihan)->itemPembayaran->nama_item ?? '-' }}
                            @if(optional($tagihan)->periode_label !== '-')
                                ({{ $tagihan->periode_label }})
                            @endif
                        </span>
                        @if($isLunas !== null)
                            <span class="status-badge {{ $isLunas ? 'status-lunas' : 'status-belum' }}">
                                {{ $isLunas ? 'LUNAS' : 'BELUM LUNAS' }}
                            </span>
                        @endif
                    </div>
                    <span class="item-metode">
                        {{ strtoupper($pembayaran->metode_bayar ?? '-') }}@if($pembayaran->metode_bayar === 'transfer' && $pembayaran->nama_bank) - {{ $pembayaran->nama_bank }}@endif
                    </span>
                </td>
                <td class="text-right">
                    {{ number_format((float) $pembayaran->nominal_bayar, 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <div class="total-section">
        <table>
            <tr>
                <td>Total</td>
                <td class="text-right">
                    {{ number_format($totalBayar, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    <div class="line"></div>

    <table class="metode-table">
        @foreach($rekapMetode as $rekap)
            <tr>
                <td>
                    {{ $metodeLabel[$rekap['metode']] ?? ucfirst((string) $rekap['metode']) }}
                    @if($rekap['metode'] === 'transfer' && $rekap['nama_bank'])
                        ({{ $rekap['nama_bank'] }})
                    @endif
                </td>
                <td class="text-right">{{ number_format((float) $rekap['total'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="footer">
        Indonesia, {{ now()->format('d-m-Y') }} <br><br>
        Admin
    </div>

</div>
</body>
</html>
