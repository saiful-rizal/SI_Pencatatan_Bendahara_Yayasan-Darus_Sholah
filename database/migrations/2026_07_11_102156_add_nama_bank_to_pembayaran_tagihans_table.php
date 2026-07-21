<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran_tagihans', function (Blueprint $table) {
            $table->string('nama_bank', 100)->nullable()->after('metode_bayar');
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran_tagihans', function (Blueprint $table) {
            $table->dropColumn('nama_bank');
        });
    }
};
