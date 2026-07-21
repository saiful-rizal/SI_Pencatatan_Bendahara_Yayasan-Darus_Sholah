<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah kolom 'tanggal' pada tabel transaksis dari DATE menjadi DATETIME
        if (Schema::hasColumn('transaksis', 'tanggal')) {
            DB::statement('ALTER TABLE transaksis MODIFY tanggal DATETIME NOT NULL');
        }

        // Ubah kolom 'tanggal_bayar' pada tabel pembayaran_tagihans dari DATE menjadi DATETIME
        if (Schema::hasColumn('pembayaran_tagihans', 'tanggal_bayar')) {
            DB::statement('ALTER TABLE pembayaran_tagihans MODIFY tanggal_bayar DATETIME NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transaksis', 'tanggal')) {
            DB::statement('ALTER TABLE transaksis MODIFY tanggal DATE NOT NULL');
        }

        if (Schema::hasColumn('pembayaran_tagihans', 'tanggal_bayar')) {
            DB::statement('ALTER TABLE pembayaran_tagihans MODIFY tanggal_bayar DATE NOT NULL');
        }
    }
};
