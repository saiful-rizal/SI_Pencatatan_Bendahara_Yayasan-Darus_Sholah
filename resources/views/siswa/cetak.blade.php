<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Siswa - {{ $siswa->nama }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 12px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 12px;
        }

        th {
            background: #f5f5f5;
            text-align: left;
        }
    </style>
</head>

<body onload="window.print()">
    <h3>Data Siswa</h3>
    <p><strong>NIS:</strong> {{ $siswa->nis }}<br>
        <strong>Nama:</strong> {{ $siswa->nama }}<br>
        <strong>Kelas:</strong> {{ $siswa->kelas }}<br>
        <strong>Kategori:</strong> {{ $siswa->kategori }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Kelas Tagihan</th>
                <th>Periode</th>
                <th>Nominal Awal</th>
                <th>Potongan</th>
                <th>Keterangan Potongan</th>
                <th>Pembayaran</th>
                <th>Sisa</th>
            </tr>
        </thead>
        <tbody>
            @forelse($siswa->tagihans as $tagihan)
                @php
                    $potongan = (float) ($tagihan->total_potongan ?? 0);
                    $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
                    $sisa = max(0, (float) $tagihan->nominal_awal - $potongan - $pembayaran);
                @endphp
                <tr>
                    <td>{{ $tagihan->itemPembayaran->nama_item ?? '-' }}</td>
                    <td>{{ $tagihan->kelas ?? '-' }}</td>
                    <td>{{ $tagihan->periode_label }}
                    </td>
                    <td>Rp {{ number_format($tagihan->nominal_awal, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($potongan, 0, ',', '.') }}</td>
                    <td>
                        @if (($tagihan->potongans ?? collect())->count() > 0)
                            @foreach ($tagihan->potongans as $potonganItem)
                                <div>- {{ $potonganItem->keterangan }} (Rp
                                    {{ number_format((float) $potonganItem->nominal_potongan, 0, ',', '.') }})
                                </div>
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                    <td>Rp {{ number_format($pembayaran, 0, ',', '.') }}</td>
                    <td>
                        @if($sisa <= 0)
                            LUNAS
                        @else
                            Rp {{ number_format($sisa, 0, ',', '.') }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;">Belum ada tagihan</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
