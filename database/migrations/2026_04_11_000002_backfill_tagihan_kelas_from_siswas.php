<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tagihans') || !Schema::hasTable('siswas')) {
            return;
        }

        if (!Schema::hasColumn('tagihans', 'kelas') || !Schema::hasColumn('tagihans', 'siswa_id')) {
            return;
        }

        if (!Schema::hasColumn('siswas', 'id') || !Schema::hasColumn('siswas', 'kelas')) {
            return;
        }

        DB::table('tagihans')
            ->whereNull('kelas')
            ->orderBy('id')
            ->chunkById(500, function ($tagihans): void {
                $siswaIds = $tagihans->pluck('siswa_id')->filter()->unique()->values();

                if ($siswaIds->isEmpty()) {
                    return;
                }

                $kelasBySiswa = DB::table('siswas')
                    ->whereIn('id', $siswaIds)
                    ->pluck('kelas', 'id');

                foreach ($tagihans as $tagihan) {
                    $kelas = $kelasBySiswa[$tagihan->siswa_id] ?? null;
                    $kelas = is_string($kelas) ? trim($kelas) : null;

                    if ($kelas === null || $kelas === '') {
                        continue;
                    }

                    DB::table('tagihans')
                        ->where('id', $tagihan->id)
                        ->whereNull('kelas')
                        ->update(['kelas' => $kelas]);
                }
            });
    }

    public function down(): void
    {
        // No-op: this migration backfills data only.
    }
};
