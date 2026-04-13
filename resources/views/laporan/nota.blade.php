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

<body onload="window.print(); window.onafterprint = function(){ window.close(); };">

<div class="nota">

    <!-- HEADER -->
    <div class="header">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:90px; vertical-align:top; text-align:left;">
                    <img src="{{ asset('image/logo_pondok.jpeg') }}" class="logo" style="margin:0;">
                </td>
                <td style="text-align:center;">
                    <h2 style="margin:0;">SMA UNGGULAN BPPT DARUS SHOLAH</h2>
                    <p style="margin:0;">Alamat Sekolah</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="line"></div>

    <h3 style="text-align:center; margin:10px 0;">BUKTI PEMBAYARAN</h3>

    <div class="line"></div>

    <!-- DATA -->
    <div class="flex">
        <div>
            No Transaksi : {{ str_pad($transaksi->id, 10, '0', STR_PAD_LEFT) }} <br>
            No Induk     : {{ $transaksi->no_induk ?? '-' }} <br>
            Nama         : {{ $transaksi->nama_siswa ?? '-' }}
        </div>

        <div style="text-align:right;">
            Tanggal : {{ $transaksi->tanggal->format('d-m-Y H:i:s') }} <br>
            Kelas   : {{ $transaksi->kelas ?? '-' }}
        </div>
    </div>

    <div class="line"></div>

    <!-- TABEL ITEM -->
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Pembayaran</th>
                <th width="20%" class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaksi->details as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->nama_item }}</td>
                <td class="text-right">
                    {{ number_format($item->subtotal, 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <!-- TOTAL -->
    <div class="total-section">
        <table>
            <tr>
                <td>Total</td>
                <td class="text-right">
                    {{ number_format($transaksi->total_bayar, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Tunai</td>
                <td class="text-right">
                    {{ number_format($transaksi->total_bayar, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Kembali</td>
                <td class="text-right">0</td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    <!-- FOOTER -->
    <div class="footer">
        Indonesia, {{ now()->format('d-m-Y') }} <br><br>
        Admin
    </div>

</div>
</body>
</html>
