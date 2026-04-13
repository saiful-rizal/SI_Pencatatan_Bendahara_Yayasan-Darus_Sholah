<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('siswas')) {
            return;
        }

        Schema::create('siswas', function (Blueprint $table) {
            $table->id();
            $table->string('nis')->unique();
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('kelas');
            $table->string('angkatan');
            $table->enum('kategori', ['mondok', 'non_mondok'])->default('non_mondok');
            $table->enum('status', ['aktif', 'lulus'])->default('aktif');
            $table->timestamps();
        });

        if (Schema::hasTable('tagihans')) {
            $hasConstraint = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tagihans' AND COLUMN_NAME = 'siswa_id' AND REFERENCED_TABLE_NAME = 'siswas'"))->isNotEmpty();

            if (!$hasConstraint) {
                Schema::table('tagihans', function (Blueprint $table) {
                    $table->foreign('siswa_id')->references('id')->on('siswas')->cascadeOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};
