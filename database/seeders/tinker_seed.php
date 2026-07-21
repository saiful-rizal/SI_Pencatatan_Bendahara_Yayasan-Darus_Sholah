<?php

use App\Models\Siswa;
use App\Models\ItemPembayaran;
use App\Models\Tagihan;
use App\Models\TagihanPotongan;
use App\Models\PembayaranTagihan;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// ─────────────────────────────────────────────────────────
// 1. ITEM PEMBAYARAN
// ─────────────────────────────────────────────────────────
echo "Memasukkan Item Pembayaran...\n";

$items = [
    ['kode' => 'SPP-001',  'nama_item' => 'SPP Bulanan',           'nominal' => 250000,  'jenis_item' => 'tetap',     'khusus_mondok' => false, 'pengelola' => 'sekolah',  'aktif' => true],
    ['kode' => 'SPP-002',  'nama_item' => 'SPP Mondok',            'nominal' => 150000,  'jenis_item' => 'tetap',     'khusus_mondok' => true,  'pengelola' => 'yayasan',  'aktif' => true],
    ['kode' => 'BOS-001',  'nama_item' => 'Dana BOS',              'nominal' => 0,       'jenis_item' => 'fleksibel', 'khusus_mondok' => false, 'pengelola' => 'sekolah',  'aktif' => true],
    ['kode' => 'OPR-001',  'nama_item' => 'Biaya Operasional',     'nominal' => 100000,  'jenis_item' => 'tetap',     'khusus_mondok' => false, 'pengelola' => 'sekolah',  'aktif' => true],
    ['kode' => 'EKSKUL-001','nama_item' => 'Kegiatan Ekstrakurikuler','nominal' => 75000, 'jenis_item' => 'tetap',    'khusus_mondok' => false, 'pengelola' => 'sekolah',  'aktif' => true],
    ['kode' => 'MKN-001',  'nama_item' => 'Biaya Makan Mondok',    'nominal' => 500000,  'jenis_item' => 'tetap',     'khusus_mondok' => true,  'pengelola' => 'yayasan',  'aktif' => true],
    ['kode' => 'INFAQ-001','nama_item' => 'Infaq Yayasan',         'nominal' => 50000,   'jenis_item' => 'tetap',     'khusus_mondok' => false, 'pengelola' => 'yayasan',  'aktif' => true],
    ['kode' => 'UJIAN-001','nama_item' => 'Biaya Ujian Semester',  'nominal' => 200000,  'jenis_item' => 'fleksibel', 'khusus_mondok' => false, 'pengelola' => 'sekolah',  'aktif' => true],
];

foreach ($items as $item) {
    ItemPembayaran::updateOrCreate(['kode' => $item['kode']], $item);
}
echo "  ✓ " . count($items) . " item pembayaran ditambahkan.\n";

// ─────────────────────────────────────────────────────────
// 2. SISWA
// ─────────────────────────────────────────────────────────
echo "Memasukkan data Siswa...\n";

$kelasList  = ['7A', '7B', '7C', '8A', '8B', '8C', '9A', '9B', '9C'];
$angkatanList = ['2022', '2023', '2024'];

$namaLaki = [
    'Ahmad Fauzi', 'Muhammad Rizki', 'Hendra Wijaya', 'Fajar Nugroho', 'Bagas Pratama',
    'Dian Saputra', 'Eko Santoso', 'Firmansyah', 'Galih Permana', 'Hafidz Rahman',
    'Ilham Maulana', 'Joko Susilo', 'Kevin Aldrian', 'Lutfi Hakim', 'Mahfud Sidiq',
    'Naufal Arif', 'Oky Prasetyo', 'Putra Ramadhan', 'Qodir Mahfuz', 'Rizal Fathoni',
    'Syahrul Gunawan', 'Taufik Hidayat', 'Umar Bashir', 'Vino Pratama', 'Wahyu Setiawan',
    'Yoga Aditya', 'Zulkifli Hasan', 'Arif Budiman', 'Budi Hartono', 'Cahyo Nugroho',
];
$namaPerempuan = [
    'Siti Aisyah', 'Nur Fatimah', 'Dewi Rahayu', 'Rina Wulandari', 'Ayu Lestari',
    'Fitri Handayani', 'Hana Kusuma', 'Indah Permata', 'Jihan Salsabila', 'Khoiriyah',
    'Laila Maghfira', 'Maulida Zahra', 'Nadia Putri', 'Olivia Santika', 'Putri Anggraeni',
    'Qonita Azizah', 'Rahma Yunita', 'Safira Nurul', 'Tari Anggraini', 'Ummu Kultsum',
    'Vira Agustina', 'Wulan Dari', 'Yasmin Arafah', 'Zahra Nabila', 'Amelia Susanti',
    'Bunga Citra', 'Cinta Meizara', 'Dinda Syahrani', 'Elsa Fitriani', 'Fanny Oktavia',
];

$siswaData = [];
$nisCounter = 1001;

foreach ($kelasList as $kelas) {
    $tingkat = substr($kelas, 0, 1); // 7, 8, or 9
    $angkatan = $angkatanList[array_search($tingkat, ['7', '8', '9'])];
    $jumlahSiswa = rand(25, 30);

    for ($i = 0; $i < $jumlahSiswa; $i++) {
        $jk = ($i % 2 === 0) ? 'L' : 'P';
        $nama = ($jk === 'L')
            ? $namaLaki[array_rand($namaLaki)]
            : $namaPerempuan[array_rand($namaPerempuan)];
        $kategori = (rand(1, 10) <= 3) ? 'mondok' : 'non_mondok'; // 30% mondok

        $siswaData[] = [
            'nis'          => (string)$nisCounter,
            'nama'         => $nama . ' ' . $nisCounter,
            'jenis_kelamin'=> $jk,
            'kelas'        => $kelas,
            'angkatan'     => $angkatan,
            'kategori'     => $kategori,
            'status'       => 'aktif',
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
        $nisCounter++;
    }
}

// Insert in chunks for performance
foreach (array_chunk($siswaData, 50) as $chunk) {
    DB::table('siswas')->insertOrIgnore($chunk);
}
$totalSiswa = DB::table('siswas')->count();
echo "  ✓ {$totalSiswa} siswa ditambahkan.\n";

// ─────────────────────────────────────────────────────────
// 3. TAGIHAN
// ─────────────────────────────────────────────────────────
echo "Memasukkan data Tagihan...\n";

$allSiswa = Siswa::all();
$allItems = ItemPembayaran::all();
$sppItem  = ItemPembayaran::where('kode', 'SPP-001')->first();
$sppMondokItem = ItemPembayaran::where('kode', 'SPP-002')->first();
$infaqItem = ItemPembayaran::where('kode', 'INFAQ-001')->first();
$mknItem   = ItemPembayaran::where('kode', 'MKN-001')->first();

$tagihanData = [];
$bulanList = [1, 2, 3, 4, 5, 6]; // Jan-Jun 2026
$tahun = 2026;

foreach ($allSiswa as $siswa) {
    foreach ($bulanList as $bulan) {
        $jatuhTempo = Carbon::create($tahun, $bulan, 10);
        $statusOptions = ['belum_lunas', 'sebagian', 'lunas'];
        $statusWeights = [30, 20, 50]; // lebih banyak yang lunas

        // SPP Bulanan untuk semua siswa
        $tagihanData[] = [
            'siswa_id'          => $siswa->id,
            'item_pembayaran_id'=> $sppItem->id,
            'kelas'             => $siswa->kelas,
            'periode_bulan'     => $bulan,
            'periode_tahun'     => $tahun,
            'nominal_awal'      => $sppItem->nominal,
            'jatuh_tempo'       => $jatuhTempo->toDateString(),
            'status'            => $statusOptions[array_rand($statusOptions)],
            'catatan'           => null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ];

        // SPP Mondok khusus siswa mondok
        if ($siswa->kategori === 'mondok' && $sppMondokItem) {
            $tagihanData[] = [
                'siswa_id'          => $siswa->id,
                'item_pembayaran_id'=> $sppMondokItem->id,
                'kelas'             => $siswa->kelas,
                'periode_bulan'     => $bulan,
                'periode_tahun'     => $tahun,
                'nominal_awal'      => $sppMondokItem->nominal,
                'jatuh_tempo'       => $jatuhTempo->toDateString(),
                'status'            => $statusOptions[array_rand($statusOptions)],
                'catatan'           => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }
    }

    // Infaq tahunan untuk semua siswa
    if ($infaqItem) {
        $tagihanData[] = [
            'siswa_id'          => $siswa->id,
            'item_pembayaran_id'=> $infaqItem->id,
            'kelas'             => $siswa->kelas,
            'periode_bulan'     => null,
            'periode_tahun'     => $tahun,
            'nominal_awal'      => $infaqItem->nominal * 12,
            'jatuh_tempo'       => Carbon::create($tahun, 1, 15)->toDateString(),
            'status'            => ['belum_lunas', 'lunas'][array_rand(['belum_lunas', 'lunas'])],
            'catatan'           => 'Infaq tahunan 2026',
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
    }

    // Biaya Makan khusus siswa mondok
    if ($siswa->kategori === 'mondok' && $mknItem) {
        foreach ($bulanList as $bulan) {
            $tagihanData[] = [
                'siswa_id'          => $siswa->id,
                'item_pembayaran_id'=> $mknItem->id,
                'kelas'             => $siswa->kelas,
                'periode_bulan'     => $bulan,
                'periode_tahun'     => $tahun,
                'nominal_awal'      => $mknItem->nominal,
                'jatuh_tempo'       => Carbon::create($tahun, $bulan, 5)->toDateString(),
                'status'            => ['belum_lunas', 'sebagian', 'lunas'][array_rand(['belum_lunas', 'sebagian', 'lunas'])],
                'catatan'           => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }
    }
}

foreach (array_chunk($tagihanData, 100) as $chunk) {
    DB::table('tagihans')->insert($chunk);
}
$totalTagihan = DB::table('tagihans')->count();
echo "  ✓ {$totalTagihan} tagihan ditambahkan.\n";

// ─────────────────────────────────────────────────────────
// 4. PEMBAYARAN TAGIHAN & TRANSAKSI
// ─────────────────────────────────────────────────────────
echo "Memasukkan Pembayaran Tagihan & Transaksi...\n";

$superAdmin = User::where('role', 'super_admin')->first();
$lunasSebagian = Tagihan::whereIn('status', ['lunas', 'sebagian'])->with('siswa', 'itemPembayaran')->get();

$metodeBayar = ['cash', 'transfer', 'cash', 'cash']; // lebih banyak cash

foreach ($lunasSebagian as $tagihan) {
    if (!$tagihan->siswa) continue;

    $nominalBayar = ($tagihan->status === 'lunas')
        ? $tagihan->nominal_awal
        : round($tagihan->nominal_awal * (rand(30, 80) / 100), -3); // 30-80% dari nominal

    $tanggalBayar = Carbon::create(2026, $tagihan->periode_bulan ?? 3, rand(5, 25));
    $metode = $metodeBayar[array_rand($metodeBayar)];

    // Insert pembayaran_tagihan
    $pembayaranId = DB::table('pembayaran_tagihans')->insertGetId([
        'tagihan_id'    => $tagihan->id,
        'tanggal_bayar' => $tanggalBayar->toDateString(),
        'nominal_bayar' => $nominalBayar,
        'metode_bayar'  => $metode,
        'catatan'       => null,
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    // Insert transaksi
    $transaksiId = DB::table('transaksis')->insertGetId([
        'jenis'                 => 'Masuk',
        'kategori'              => $tagihan->itemPembayaran->nama_item ?? 'SPP',
        'nama_siswa'            => $tagihan->siswa->nama,
        'kelas'                 => $tagihan->siswa->kelas,
        'total_bayar'           => $nominalBayar,
        'tanggal'               => $tanggalBayar->toDateString(),
        'catatan'               => 'Pembayaran ' . ($tagihan->itemPembayaran->nama_item ?? '') . ' bulan ' . ($tagihan->periode_bulan ?? '-'),
        'siswa_id'              => $tagihan->siswa_id,
        'pembayaran_tagihan_id' => $pembayaranId,
        'created_by'            => $superAdmin?->id,
        'updated_by'            => $superAdmin?->id,
        'created_at'            => now(),
        'updated_at'            => now(),
    ]);

    // Insert detail_transaksi
    DB::table('detail_transaksis')->insert([
        'transaksi_id'       => $transaksiId,
        'item_pembayaran_id' => $tagihan->item_pembayaran_id,
        'nama_item'          => $tagihan->itemPembayaran->nama_item ?? 'Pembayaran',
        'harga'              => $nominalBayar,
        'jumlah'             => 1,
        'subtotal'           => $nominalBayar,
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);
}

// ─────────────────────────────────────────────────────────
// 5. TRANSAKSI KELUAR (Operasional)
// ─────────────────────────────────────────────────────────
echo "Memasukkan Transaksi Keluar (Operasional)...\n";

$transaksiKeluar = [
    ['kategori' => 'Operasional', 'catatan' => 'Pembelian ATK Kantor',            'nominal' => 350000],
    ['kategori' => 'Operasional', 'catatan' => 'Pembelian Tinta Printer',          'nominal' => 120000],
    ['kategori' => 'Operasional', 'catatan' => 'Bayar Listrik Januari',            'nominal' => 850000],
    ['kategori' => 'Operasional', 'catatan' => 'Bayar Air PDAM Januari',           'nominal' => 200000],
    ['kategori' => 'Operasional', 'catatan' => 'Bayar Internet Bulanan',           'nominal' => 500000],
    ['kategori' => 'Operasional', 'catatan' => 'Biaya Kebersihan',                 'nominal' => 150000],
    ['kategori' => 'Operasional', 'catatan' => 'Perbaikan Meja Kelas 8A',         'nominal' => 250000],
    ['kategori' => 'Operasional', 'catatan' => 'Pembelian Kapur & Spidol',        'nominal' => 80000],
    ['kategori' => 'Operasional', 'catatan' => 'Bayar Listrik Februari',           'nominal' => 920000],
    ['kategori' => 'Operasional', 'catatan' => 'Pembelian Kertas HVS',             'nominal' => 175000],
    ['kategori' => 'Operasional', 'catatan' => 'Biaya Rapat Guru Bulanan',         'nominal' => 300000],
    ['kategori' => 'Operasional', 'catatan' => 'Perbaikan Proyektor',              'nominal' => 400000],
    ['kategori' => 'BOS',         'catatan' => 'Dana BOS untuk Buku Pelajaran',    'nominal' => 5000000],
    ['kategori' => 'BOS',         'catatan' => 'Dana BOS untuk Seragam',           'nominal' => 3500000],
    ['kategori' => 'Operasional', 'catatan' => 'Bayar Air PDAM Februari',          'nominal' => 210000],
    ['kategori' => 'Operasional', 'catatan' => 'Biaya Upacara Bendera',            'nominal' => 100000],
    ['kategori' => 'Operasional', 'catatan' => 'Pembelian Sabun & Pembersih',      'nominal' => 95000],
    ['kategori' => 'Operasional', 'catatan' => 'Bayar Honor Guru Ekstrakurikuler', 'nominal' => 750000],
    ['kategori' => 'Operasional', 'catatan' => 'Bayar Listrik Maret',              'nominal' => 880000],
    ['kategori' => 'Operasional', 'catatan' => 'Biaya Pemeliharaan Gedung',        'nominal' => 600000],
];

foreach ($transaksiKeluar as $idx => $trx) {
    $bulan = ($idx % 6) + 1;
    $tgl   = Carbon::create(2026, $bulan, rand(5, 28));

    $trxId = DB::table('transaksis')->insertGetId([
        'jenis'      => 'Keluar',
        'kategori'   => $trx['kategori'],
        'nama_siswa' => null,
        'kelas'      => null,
        'total_bayar'=> $trx['nominal'],
        'tanggal'    => $tgl->toDateString(),
        'catatan'    => $trx['catatan'],
        'created_by' => $superAdmin?->id,
        'updated_by' => $superAdmin?->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('detail_transaksis')->insert([
        'transaksi_id'       => $trxId,
        'item_pembayaran_id' => null,
        'nama_item'          => $trx['catatan'],
        'harga'              => $trx['nominal'],
        'jumlah'             => 1,
        'subtotal'           => $trx['nominal'],
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);
}

// ─────────────────────────────────────────────────────────
// 6. POTONGAN TAGIHAN
// ─────────────────────────────────────────────────────────
echo "Memasukkan Tagihan Potongan...\n";

$sampleTagihans = Tagihan::whereIn('status', ['belum_lunas', 'sebagian'])
    ->inRandomOrder()->limit(30)->get();

$keteranganPotongan = [
    'Beasiswa Prestasi', 'Beasiswa Tidak Mampu', 'Potongan Yatim/Piatu',
    'Potongan Anak Guru', 'Diskon Lunas Awal', 'Bantuan Pemerintah',
];

foreach ($sampleTagihans as $tagihan) {
    DB::table('tagihan_potongans')->insert([
        'tagihan_id'       => $tagihan->id,
        'tanggal_potongan' => Carbon::create(2026, rand(1, 6), rand(1, 28))->toDateString(),
        'keterangan'       => $keteranganPotongan[array_rand($keteranganPotongan)],
        'nominal_potongan' => [25000, 50000, 75000, 100000, 125000][array_rand([25000, 50000, 75000, 100000, 125000])],
        'created_at'       => now(),
        'updated_at'       => now(),
    ]);
}

// ─────────────────────────────────────────────────────────
// RINGKASAN
// ─────────────────────────────────────────────────────────
echo "\n=== SELESAI ===\n";
echo "Siswa             : " . DB::table('siswas')->count() . "\n";
echo "Item Pembayaran   : " . DB::table('item_pembayarans')->count() . "\n";
echo "Tagihan           : " . DB::table('tagihans')->count() . "\n";
echo "Pembayaran Tagihan: " . DB::table('pembayaran_tagihans')->count() . "\n";
echo "Transaksi         : " . DB::table('transaksis')->count() . "\n";
echo "Detail Transaksi  : " . DB::table('detail_transaksis')->count() . "\n";
echo "Tagihan Potongan  : " . DB::table('tagihan_potongans')->count() . "\n";
