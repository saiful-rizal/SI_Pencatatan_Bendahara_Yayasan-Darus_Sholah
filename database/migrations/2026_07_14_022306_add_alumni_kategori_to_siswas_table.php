<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan pilihan 'alumni' dan 'non_alumni' ke ENUM kolom `kategori`
     * pada tabel `siswas`, agar bisa dipilih di form tambah/edit siswa.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('siswas', 'kategori')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE siswas MODIFY COLUMN kategori ENUM('mondok', 'non_mondok', 'alumni', 'non_alumni') NOT NULL DEFAULT 'non_mondok'");
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('siswas', 'kategori')) {
            return;
        }

        // Kembalikan siswa 'alumni'/'non_alumni' ke 'non_mondok' dulu supaya
        // tidak ada data yang tertinggal dengan nilai yang tidak lagi didukung ENUM lama.
        DB::table('siswas')
            ->whereIn('kategori', ['alumni', 'non_alumni'])
            ->update(['kategori' => 'non_mondok']);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE siswas MODIFY COLUMN kategori ENUM('mondok', 'non_mondok') NOT NULL DEFAULT 'non_mondok'");
        }
    }
};
