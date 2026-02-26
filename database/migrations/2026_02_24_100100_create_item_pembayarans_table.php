<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('item_pembayarans')) {
            return;
        }

        Schema::create('item_pembayarans', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama_item');
            $table->enum('jenis_item', ['tetap', 'fleksibel'])->default('tetap');
            $table->boolean('khusus_mondok')->default(false);
            $table->enum('pengelola', ['yayasan', 'sekolah'])->default('sekolah');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('item_pembayarans')) {
            return;
        }

        Schema::dropIfExists('item_pembayarans');
    }
};
