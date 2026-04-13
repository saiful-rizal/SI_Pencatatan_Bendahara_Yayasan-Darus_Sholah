<?php

use App\Models\PembayaranTagihan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PembayaranTagihan::query()
            ->with(['tagihan.siswa', 'tagihan.itemPembayaran'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $pembayaranTagihan) {
                    $pembayaranTagihan->sinkronkanTransaksi();
                }
            });
    }

    public function down(): void
    {
        // No-op: data synchronization migration.
    }
};
