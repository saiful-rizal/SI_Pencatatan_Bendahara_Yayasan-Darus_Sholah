<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran_tagihans', function (Blueprint $table) {
            if (!Schema::hasColumn('pembayaran_tagihans', 'jumlah_tunai')) {
                $table->decimal('jumlah_tunai', 15, 2)->nullable()->after('metode_bayar');
            }
            if (!Schema::hasColumn('pembayaran_tagihans', 'kembalian')) {
                $table->decimal('kembalian', 15, 2)->nullable()->after('jumlah_tunai');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran_tagihans', function (Blueprint $table) {
            if (Schema::hasColumn('pembayaran_tagihans', 'kembalian')) {
                $table->dropColumn('kembalian');
            }
            if (Schema::hasColumn('pembayaran_tagihans', 'jumlah_tunai')) {
                $table->dropColumn('jumlah_tunai');
            }
        });
    }
};
