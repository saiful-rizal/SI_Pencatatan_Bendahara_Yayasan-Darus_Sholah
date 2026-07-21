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

        .item-row-plain {
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
    $pembayaranTagihan = $transaksi->pembayaranTagihan;
    $metodeBayar = $pembayaranTagihan->metode_bayar ?? 'tunai';
    $namaBank = $pembayaranTagihan->nama_bank ?? null;
    $siswa = $transaksi->siswa;
    $isKeluar = $transaksi->jenis === 'Keluar';

    // Untuk nota Masuk: kategori memakai label siswa (mondok/non_mondok/alumni/non_alumni).
    // Untuk nota Keluar: tidak ada siswa, kategori langsung diambil dari kolom `kategori`
    // pada tabel transaksis (mis. "Pramuka", "Idul Adha").
    if ($isKeluar) {
        $kategoriLabel = $transaksi->kategori ?? '-';
    } else {
        $kategoriLabel = [
            'mondok' => 'Mondok',
            'non_mondok' => 'Non Mondok',
            'alumni' => 'Alumni',
            'non_alumni' => 'Non Alumni',
        ][$siswa->kategori ?? ''] ?? ($siswa->kategori ?? null ? ucfirst(str_replace('_', ' ', $siswa->kategori)) : '-');
    }
@endphp
<div class="nota">
    <div class="header">
        <img src="{{ asset('image/logo_pondok.jpeg') }}" class="logo" alt="Logo">
        <h2 style="margin:0;">SMA UNGGULAN BPPT DARUS SHOLAH</h2>
        <p style="margin:0;">Alamat Sekolah</p>
    </div>

    <div class="line"></div>

    <h3 style="text-align:center; margin:10px 0;">{{ $isKeluar ? 'BUKTI PENGELUARAN' : 'BUKTI PEMBAYARAN' }}</h3>

    <div class="line"></div>

    <table class="info-table">
        <tr>
            <td class="info-label">No Transaksi</td>
            <td class="info-sep">:</td>
            <td class="info-value">TRX{{ $transaksi->tanggal->format('ymd') }}{{ str_pad($transaksi->id, 4, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <td class="info-label">Tanggal</td>
            <td class="info-sep">:</td>
            <td class="info-value">{{ now()->format('d-m-Y H:i:s') }}</td>
        </tr>
        @unless($isKeluar)
        <tr>
            <td class="info-label">No Induk</td>
            <td class="info-sep">:</td>
            <td class="info-value">{{ $siswa->nis ?? $transaksi->no_induk ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Kelas</td>
            <td class="info-sep">:</td>
            <td class="info-value">{{ $transaksi->kelas ?? '-' }}</td>
        </tr>
        @endunless
        <tr>
            <td class="info-label">{{ $isKeluar ? 'Penerima/Pihak Dibayar' : 'Nama' }}</td>
            <td class="info-sep">:</td>
            <td class="info-value">{{ $transaksi->nama_siswa ?? '-' }}</td>
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
            @foreach($transaksi->details as $index => $item)
            @php
                $itemPembayaranTagihan = optional($item->transaksi)->pembayaranTagihan;
                $itemMetode = $itemPembayaranTagihan->metode_bayar ?? $metodeBayar;
                $itemBank = $itemPembayaranTagihan->nama_bank ?? $namaBank;
                // Status lunas/belum lunas dihitung dari sisa tagihan terkait item ini
                // (nominal_awal - potongan - total seluruh pembayaran pada tagihan tsb),
                // bukan dari metode bayar semata. Cicil yang belum genap otomatis "belum lunas".
                $itemTagihan = optional($itemPembayaranTagihan)->tagihan;
                $itemSisa = $itemTagihan ? $itemTagihan->sisaTagihan() : null;
                $itemIsLunas = $itemSisa !== null ? $itemSisa <= 0 : null;
            @endphp
            <tr class="item-row {{ $isKeluar ? 'item-row-plain' : '' }}">
                <td>{{ $index + 1 }}</td>
                <td>
                    <div class="item-head">
                        <span class="item-name">{{ $item->nama_item }}</span>
                        @unless($isKeluar)
                            @if($itemIsLunas !== null)
                                <span class="status-badge {{ $itemIsLunas ? 'status-lunas' : 'status-belum' }}">
                                    {{ $itemIsLunas ? 'LUNAS' : 'BELUM LUNAS' }}
                                </span>
                            @endif
                        @endunless
                    </div>
                    @unless($isKeluar)
                        <span class="item-metode">
                            {{ strtoupper($itemMetode ?? '-') }}@if($itemMetode === 'transfer' && $itemBank) - {{ $itemBank }}@endif
                        </span>
                    @endunless
                </td>
                <td class="text-right">
                    {{ number_format($item->subtotal, 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    @php
        $metodeLabel = [
            'cash' => 'Cash',
            'tunai' => 'Cash',
            'cicil' => 'Cicil',
            'transfer' => 'Transfer',
        ];

        if (!$isKeluar) {
            $rekapMetode = $transaksi->details
                ->groupBy(function ($item) use ($metodeBayar, $namaBank) {
                    $pt = optional($item->transaksi)->pembayaranTagihan;
                    $metode = $pt->metode_bayar ?? $metodeBayar;
                    $bank = $pt->nama_bank ?? $namaBank;
                    return $metode . '|' . $bank;
                })
                ->map(function ($group) use ($metodeBayar, $namaBank) {
                    $pt = optional($group->first()->transaksi)->pembayaranTagihan;
                    return [
                        'metode' => $pt->metode_bayar ?? $metodeBayar,
                        'nama_bank' => $pt->nama_bank ?? $namaBank,
                        'total' => $group->sum('subtotal'),
                    ];
                })
                ->values();
        }
    @endphp

    <div class="total-section">
        <table>
            <tr>
                <td>Total</td>
                <td class="text-right">
                    {{ number_format($transaksi->total_bayar, 0, ',', '.') }}
                </td>
            </tr>
            @if($isKeluar)
            <tr>
                <td>Metode</td>
                <td class="text-right">{{ strtoupper($metodeBayar) }}</td>
            </tr>
            @if($metodeBayar === 'transfer' && $namaBank)
            <tr>
                <td>Nama Bank</td>
                <td class="text-right">{{ $namaBank }}</td>
            </tr>
            @endif
            @endif
        </table>
    </div>

    <div style="clear: both;"></div>

    @unless($isKeluar)
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
    @endunless

    <div class="footer">
        Indonesia, {{ now()->format('d-m-Y') }} <br><br>
        Admin
    </div>

</div>
</body>
</html>
