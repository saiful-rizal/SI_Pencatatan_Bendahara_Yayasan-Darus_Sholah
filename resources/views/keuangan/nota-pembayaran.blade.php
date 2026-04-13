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
        }

        .line {
            border-top: 1px dashed #000;
            margin: 15px 0;
        }

        .flex {
            display: flex;
            justify-content: space-between;
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

        @media print {
            body {
                background: #fff;
            }

            .nota {
                border: none;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>

<body onload="window.print(); window.onafterprint = function(){ if (window.opener && !window.opener.closed) { window.opener.location.reload(); } window.close(); };">
@php
    $siswa = optional(optional($pembayarans->first())->tagihan)->siswa;
    $tanggalCetak = optional($pembayarans->max('tanggal_bayar'));
    $totalBayar = (float) $pembayarans->sum('nominal_bayar');
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

    <div class="flex">
        <div>
            No Transaksi : {{ $pembayarans->pluck('id')->map(fn ($id) => str_pad((string) $id, 10, '0', STR_PAD_LEFT))->join(', ') }} <br>
            No Induk     : {{ $siswa->nis ?? '-' }} <br>
            Nama         : {{ $siswa->nama ?? '-' }}
        </div>

        <div style="text-align:right;">
            Tanggal : {{ $tanggalCetak ? $tanggalCetak->format('d-m-Y') : '-' }} <br>
            Kelas   : {{ $siswa->kelas ?? '-' }}
        </div>
    </div>

    <div class="line"></div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Pembayaran</th>
                <th width="20%" class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pembayarans as $index => $pembayaran)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ optional(optional($pembayaran->tagihan)->itemPembayaran)->nama_item ?? '-' }}
                        @if(optional($pembayaran->tagihan)->periode_label !== '-')
                            ({{ $pembayaran->tagihan->periode_label }})
                        @endif
                    </td>
                    <td class="text-right">{{ number_format((float) $pembayaran->nominal_bayar, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <div class="total-section">
        <table>
            <tr>
                <td>Total</td>
                <td class="text-right">{{ number_format($totalBayar, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tunai</td>
                <td class="text-right">{{ number_format($totalBayar, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Kembali</td>
                <td class="text-right">0</td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    <div class="footer">
        Indonesia, {{ now()->format('d-m-Y') }} <br><br>
        Admin
    </div>
</div>
</body>
</html>
