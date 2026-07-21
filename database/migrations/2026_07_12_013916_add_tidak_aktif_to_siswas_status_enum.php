<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration awal (2026_02_25_010000_create_siswas_table) mendefinisikan kolom
     * `status` sebagai ENUM('aktif','lulus') saja — sehingga fitur "Tidak Aktif"
     * di form edit siswa selalu gagal disimpan dengan error:
     * "Data truncated for column 'status'".
     *
     * Migration ini menambahkan nilai 'tidak_aktif' ke ENUM tersebut.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('siswas', 'status')) {
            return;
        }

        DB::statement("ALTER TABLE siswas MODIFY COLUMN status ENUM('aktif', 'tidak_aktif', 'lulus') NOT NULL DEFAULT 'aktif'");
    }

    public function down(): void
    {
        if (!Schema::hasColumn('siswas', 'status')) {
            return;
        }

        // Kembalikan siswa 'tidak_aktif' ke 'aktif' dulu supaya tidak ada data
        // yang tertinggal dengan nilai yang tidak lagi didukung ENUM lama.
        DB::table('siswas')->where('status', 'tidak_aktif')->update(['status' => 'aktif']);

        DB::statement("ALTER TABLE siswas MODIFY COLUMN status ENUM('aktif', 'lulus') NOT NULL DEFAULT 'aktif'");
    }
};
